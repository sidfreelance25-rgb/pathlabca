<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WhatsAppReportSender
{
    public function __construct(private ReportService $reports, private AuditRecorder $audit) {}

    public function connect(User $user, string $phoneNumberId, string $token, ?string $templateName): WhatsappAccount
    {
        $response = $this->client($token)->get($this->url($phoneNumberId), [
            'fields' => 'display_phone_number,verified_name',
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'access_token' => $this->message($response->json('error.message'), 'Meta did not accept this phone number ID and token.'),
            ]);
        }

        $display = $response->json('display_phone_number');
        $verified = $response->json('verified_name');

        return WhatsappAccount::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone_number_id' => $phoneNumberId,
                'access_token' => $token,
                'display_number' => is_string($display) && $display !== '' ? $display : $phoneNumberId,
                'verified_name' => is_string($verified) ? $verified : null,
                'template_name' => $templateName !== '' ? $templateName : null,
                'connected_at' => now(),
            ],
        );
    }

    public function sendReport(User $sender, Order $order): void
    {
        $account = $sender->whatsappAccount;
        if (! $account instanceof WhatsappAccount) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Connect your WhatsApp number in settings before sending a report.',
            ]);
        }

        $order->loadMissing('patient');
        $to = $this->recipient($order->patient->phone ?? null);
        $report = $this->reports->issueForDownload($order, $sender);
        $path = $report->file_path;
        if ($path === null || ! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages([
                'whatsapp' => 'The report PDF is not ready to send.',
            ]);
        }

        $binary = Storage::disk('local')->get($path);
        if (! is_string($binary) || $binary === '') {
            throw ValidationException::withMessages([
                'whatsapp' => 'The report PDF is empty.',
            ]);
        }

        $filename = $report->report_number.'.pdf';
        $uploaded = $this->client($account->access_token)
            ->attach('file', $binary, $filename, ['Content-Type' => 'application/pdf'])
            ->post($this->url($account->phone_number_id.'/media'), [
                'messaging_product' => 'whatsapp',
                'type' => 'application/pdf',
            ]);

        if ($uploaded->failed()) {
            throw ValidationException::withMessages([
                'whatsapp' => $this->message($uploaded->json('error.message'), 'WhatsApp rejected the report file.'),
            ]);
        }

        $mediaId = $uploaded->json('id');
        if (! is_string($mediaId) || $mediaId === '') {
            throw ValidationException::withMessages([
                'whatsapp' => 'WhatsApp did not return a file id.',
            ]);
        }

        $sent = $this->client($account->access_token)->post(
            $this->url($account->phone_number_id.'/messages'),
            $this->payload($account, $to, $mediaId, $filename, $order->patient->name, $report->report_number),
        );

        if ($sent->failed()) {
            throw ValidationException::withMessages([
                'whatsapp' => $this->message($sent->json('error.message'), 'WhatsApp did not send the report.'),
            ]);
        }

        $this->audit->record($sender, 'report.whatsapp_sent', $report, null, [
            'to' => $to,
            'from' => $account->display_number,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(WhatsappAccount $account, string $to, string $mediaId, string $filename, string $patientName, string $reportNumber): array
    {
        if (is_string($account->template_name) && $account->template_name !== '') {
            return [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $account->template_name,
                    'language' => ['code' => 'en'],
                    'components' => [[
                        'type' => 'header',
                        'parameters' => [[
                            'type' => 'document',
                            'document' => [
                                'id' => $mediaId,
                                'filename' => $filename,
                            ],
                        ]],
                    ]],
                ],
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'document',
            'document' => [
                'id' => $mediaId,
                'filename' => $filename,
                'caption' => 'Laboratory report '.$reportNumber.' for '.$patientName,
            ],
        ];
    }

    private function recipient(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        if (strlen($digits) < 11) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Add the patient phone with country code before sending on WhatsApp.',
            ]);
        }

        return $digits;
    }

    private function client(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20);
    }

    private function url(string $path): string
    {
        $version = config('services.whatsapp.graph_version', 'v27.0');

        return 'https://graph.facebook.com/'.$version.'/'.$path;
    }

    private function message(mixed $remote, string $fallback): string
    {
        return is_string($remote) && $remote !== '' ? $remote : $fallback;
    }
}
