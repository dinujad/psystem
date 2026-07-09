<?php

namespace App\Http\Controllers;

use App\WhatsappContact;
use App\WhatsappLabel;
use Illuminate\Http\Request;

class WhatsappLabelController extends Controller
{
    private function checkAccess()
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /** List all labels (admin management page). */
    public function index()
    {
        $this->checkAccess();
        $labels = WhatsappLabel::withCount('contacts')->orderBy('name')->get();
        return view('whatsapp.labels.index', compact('labels'));
    }

    /** Create a new label. */
    public function store(Request $request)
    {
        $this->checkAccess();
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:80', 'unique:whatsapp_labels,name'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $label = WhatsappLabel::create($data);
        return response()->json(['success' => true, 'label' => $label]);
    }

    /** Update a label. */
    public function update(Request $request, WhatsappLabel $label)
    {
        $this->checkAccess();
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:80', 'unique:whatsapp_labels,name,' . $label->id],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $label->update($data);
        return response()->json(['success' => true, 'label' => $label]);
    }

    /** Delete a label (detaches from all contacts automatically via cascade). */
    public function destroy(WhatsappLabel $label)
    {
        $this->checkAccess();
        $label->delete();
        return response()->json(['success' => true]);
    }

    /** Return all labels (for inline picker in inbox). */
    public function all()
    {
        $this->checkAccess();
        return response()->json(['labels' => WhatsappLabel::orderBy('name')->get()]);
    }

    /** Assign a label to a contact (creates contact record if missing). */
    public function assign(Request $request, WhatsappLabel $label)
    {
        $this->checkAccess();
        $data = $request->validate(['phone' => ['required', 'string']]);

        $contact = WhatsappContact::firstOrCreate(['phone_number' => $data['phone']]);
        $contact->labels()->syncWithoutDetaching([$label->id]);

        return response()->json(['success' => true]);
    }

    /** Remove a label from a contact. */
    public function remove(Request $request, WhatsappLabel $label)
    {
        $this->checkAccess();
        $data = $request->validate(['phone' => ['required', 'string']]);

        $contact = WhatsappContact::where('phone_number', $data['phone'])->first();
        if ($contact) {
            $contact->labels()->detach($label->id);
        }

        return response()->json(['success' => true]);
    }
}
