<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppReportSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $account = $user->whatsappAccount;

        return Inertia::render('settings/whatsapp', [
            'account' => $account === null ? null : [
                'display_number' => $account->display_number,
                'phone_number_id' => $account->phone_number_id,
                'verified_name' => $account->verified_name,
                'template_name' => $account->template_name,
                'connected_at' => $account->connected_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, WhatsAppReportSender $whatsapp): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $data = $request->validate([
            'phone_number_id' => ['required', 'string', 'max:40'],
            'access_token' => ['required', 'string', 'max:500'],
            'template_name' => ['nullable', 'string', 'max:120'],
        ]);

        $whatsapp->connect(
            $user,
            trim($data['phone_number_id']),
            trim($data['access_token']),
            trim((string) ($data['template_name'] ?? '')),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'WhatsApp number connected.']);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->whatsappAccount()?->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'WhatsApp number removed from this account.']);

        return back();
    }
}
