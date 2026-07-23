<?php

namespace App\Http\Controllers\Concerns;

trait WhatsappAccess
{
    /**
     * Full WhatsApp module access (Inbox admin, QR, bots, labels, agents…).
     * Admin# role still passes via Gate::before.
     * Legacy send_notifications is kept for existing admin setups.
     */
    protected function isWhatsappAdmin(): bool
    {
        $user = auth()->user();

        return $user->can('whatsapp.access')
            || $user->can('send_notifications');
    }

    /**
     * Limited agent inbox access.
     */
    protected function isWhatsappAgent(): bool
    {
        return auth()->user()->can('whatsapp.agent');
    }

    /**
     * Can assign / reassign chats to agents (admin, or role tick "Assign to Agent").
     */
    protected function canAssignWhatsappChats(): bool
    {
        $user = auth()->user();

        return $this->isWhatsappAdmin()
            || $user->can('whatsapp.assign');
    }

    /**
     * Any WhatsApp UI access (full or agent).
     */
    protected function canAccessWhatsapp(): bool
    {
        return $this->isWhatsappAdmin() || $this->isWhatsappAgent();
    }

    protected function requireWhatsappAdmin(): void
    {
        if (! $this->isWhatsappAdmin()) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function requireWhatsappAccess(): void
    {
        if (! $this->canAccessWhatsapp()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
