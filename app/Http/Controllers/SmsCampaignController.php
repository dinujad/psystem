<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;

class SmsCampaignController extends Controller
{
    /**
     * @var \App\Utils\BusinessUtil
     */
    protected $businessUtil;

    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;
    }

    public function index()
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile']);

        return view('sms_campaigns.index', compact('customers'));
    }

    public function send(Request $request)
    {
        if (! auth()->user()->can('send_notifications')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'sms_body' => 'required|string|max:1000',
            'send_to_all' => 'nullable|boolean',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'nullable|integer',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $sms_settings = $request->session()->get('business.sms_settings', []);

        $contacts_query = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '');

        if (! $request->boolean('send_to_all')) {
            $contact_ids = $request->input('contact_ids', []);
            if (empty($contact_ids)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.invalid_data'),
                ];

                return redirect()->back()->with('status', $output)->withInput();
            }
            $contacts_query->whereIn('id', $contact_ids);
        }

        $mobiles = $contacts_query->pluck('mobile')
            ->filter()
            ->map(function ($mobile) {
                return trim((string) $mobile);
            })
            ->filter()
            ->unique()
            ->values();

        if ($mobiles->isEmpty()) {
            $output = [
                'success' => 0,
                'msg' => __('lang_v1.no_data'),
            ];

            return redirect()->back()->with('status', $output)->withInput();
        }

        try {
            $this->businessUtil->sendSms([
                'sms_settings' => $sms_settings,
                'mobile_number' => $mobiles->implode(','),
                'sms_body' => $request->input('sms_body'),
            ]);

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.notification_sent_successfully').' ('.$mobiles->count().' recipients)',
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with('status', $output);
    }
}
