<?php

namespace Database\Seeders;

use App\WhatsappFlow;
use Illuminate\Database\Seeder;

/**
 * Seeds two example flows:
 *  1. A keyword-triggered main menu (hi / hello / menu) with three branches.
 *  2. A default fallback flow for messages that match nothing.
 *
 * Re-running is safe: existing flows with the same name are replaced.
 */
class WhatsappBotFlowSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMainMenuFlow();
        $this->seedFallbackFlow();
    }

    protected function seedMainMenuFlow(): void
    {
        WhatsappFlow::where('name', 'Main Menu')->delete();

        $flow = WhatsappFlow::create([
            'name'                => 'Main Menu',
            'trigger_keywords'    => ['hi', 'hello', 'menu'],
            'is_default_fallback' => false,
            'is_active'           => true,
        ]);

        // First step: the menu.
        $flow->steps()->create([
            'step_key'      => 'main_menu',
            'message_text'  => "Hi! 👋 Welcome to PrintWorks. How can we help you today?",
            'step_type'     => 'menu',
            'is_first_step' => true,
            'sort_order'    => 0,
            'options'       => [
                ['label' => '1. Check Order Status', 'match' => '1', 'next_step_key' => 'ask_order_id'],
                ['label' => '2. Talk to Support',    'match' => '2', 'next_step_key' => 'connect_support'],
                ['label' => '3. Business Hours',      'match' => '3', 'next_step_key' => 'business_hours'],
            ],
        ]);

        // Branch 1: ask for the order id (text_input) → echo it back (final).
        $flow->steps()->create([
            'step_key'      => 'ask_order_id',
            'message_text'  => "Please reply with your *Order ID* and we'll look it up.",
            'step_type'     => 'text_input',
            'save_input_as' => 'order_id',
            'next_step_key' => 'order_result',
            'sort_order'    => 1,
        ]);

        $flow->steps()->create([
            'step_key'      => 'order_result',
            'message_text'  => "Thanks! We're checking the status for order *{{order_id}}*.\n\n(An agent will follow up shortly — order lookup integration coming soon.)",
            'step_type'     => 'final',
            'sort_order'    => 2,
        ]);

        // Branch 2: hand off to a human agent.
        $flow->steps()->create([
            'step_key'                => 'connect_support',
            'message_text'            => "Connecting you to our team… 🧑‍💼 Someone will reply here shortly. Please describe your question in the meantime.",
            'step_type'               => 'final',
            'triggers_human_takeover' => true,
            'sort_order'              => 3,
        ]);

        // Branch 3: static business hours.
        $flow->steps()->create([
            'step_key'     => 'business_hours',
            'message_text' => "🕒 *Business Hours*\nMon–Fri: 9:00 AM – 6:00 PM\nSat: 9:00 AM – 1:00 PM\nSun & holidays: Closed",
            'step_type'    => 'final',
            'sort_order'   => 4,
        ]);
    }

    protected function seedFallbackFlow(): void
    {
        WhatsappFlow::where('name', 'Default Fallback')->delete();

        $flow = WhatsappFlow::create([
            'name'                => 'Default Fallback',
            'trigger_keywords'    => [],
            'is_default_fallback' => true,
            'is_active'           => true,
        ]);

        $flow->steps()->create([
            'step_key'      => 'fallback',
            'message_text'  => "Sorry, I didn't understand that. 🤔\nReply *menu* to see the options.",
            'step_type'     => 'final',
            'is_first_step' => true,
            'sort_order'    => 0,
        ]);
    }
}
