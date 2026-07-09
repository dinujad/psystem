<?php

namespace App\Http\Controllers;

use App\EmwaApiClient;
use Illuminate\Http\Request;

class EmwaApiAdminController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('superadmin') && ! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $clients = EmwaApiClient::orderByDesc('id')->get();
        $baseUrl = url('/emwa-api');
        $waStatus = app(\App\Services\WhatsappService::class)->getStatus();

        return view('emwa-api.index', compact('clients', 'baseUrl', 'waStatus'));
    }

    public function showRegister()
    {
        return view('emwa-api.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:emwa_api_clients,email',
        ]);

        $client = EmwaApiClient::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'api_key' => EmwaApiClient::generateApiKey(),
            'is_active' => false,
            'business_id' => session('user.business_id'),
        ]);

        return redirect()
            ->route('emwa.register')
            ->with('status', [
                'success' => 1,
                'msg' => 'Registration submitted. Your API key will be activated after admin approval.',
                'api_key' => $client->api_key,
                'email' => $client->email,
            ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('superadmin') && ! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:emwa_api_clients,email',
        ]);

        EmwaApiClient::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'api_key' => EmwaApiClient::generateApiKey(),
            'is_active' => true,
            'business_id' => session('user.business_id'),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.whatsapp.emwa.index')
            ->with('status', ['success' => 1, 'msg' => 'E MEDIA API client created and activated.']);
    }

    public function approve($id)
    {
        $this->authorizeAdmin();
        $client = EmwaApiClient::findOrFail($id);
        $client->is_active = true;
        $client->save();

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'API client activated.']);
    }

    public function revoke($id)
    {
        $this->authorizeAdmin();
        $client = EmwaApiClient::findOrFail($id);
        $client->is_active = false;
        $client->save();

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'API client deactivated.']);
    }

    public function regenerateKey($id)
    {
        $this->authorizeAdmin();
        $client = EmwaApiClient::findOrFail($id);
        $client->api_key = EmwaApiClient::generateApiKey();
        $client->save();

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'API key regenerated.']);
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();
        EmwaApiClient::findOrFail($id)->delete();

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'API client deleted.']);
    }

    private function authorizeAdmin(): void
    {
        if (! auth()->user()->can('superadmin') && ! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
    }
}
