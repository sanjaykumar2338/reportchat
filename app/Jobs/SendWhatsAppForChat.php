<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsAppForChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $chatId;

    public function __construct(int $chatId)
    {
        $this->onQueue('whatsapp');
        $this->chatId = $chatId;
    }

    public function handle(): void
    {
        $chat = Chat::with('user')->find($this->chatId);
        if (!$chat) return;

        $key = $this->categoryKeyFromTitle($chat->title);
        if (!$key) return;

        // Eligible admins
        $admins = User::query()
            ->where('role', 'admin')
            ->where('whatsapp_notifications', 1)
            ->whereNotNull('phone')
            ->whereJsonContains('report_categories', $key)
            ->get();

        if ($admins->isEmpty()) return;

        $token = config('services.whapi.token') ?: env('WHAPI_TOKEN');
        if (!$token) return;

        $body = $this->buildMessage($chat);

        foreach ($admins as $admin) {
            $to = preg_replace('/\D+/', '', (string) $admin->phone); // digits only
            if (!$to) continue;

            Http::withToken($token)
                ->asJson()
                ->post('https://gate.whapi.cloud/messages/text', [
                    'to'   => $to,
                    'body' => $body,
                ])
                ->throw(); // let the job retry if it fails
        }
    }

    private function buildMessage(Chat $chat): string
    {
        $lines = ["🔔 Nuevo reporte"];

        $add = function(string $label, $value) use (&$lines) {
            if (!empty($value)) $lines[] = "$label: $value";
        };

        $add('Categoría',     $chat->title);        // full label from UI
        $add('Subcategoría',  $chat->sub_type);
        $add('Lugar',         $chat->location);
        $add('Descripción',   $chat->description);

        // reporter (user who sent it)
        $senderName  = optional($chat->user)->name;
        $senderEmail = optional($chat->user)->email;
        $senderPhone = $chat->phone ?: optional($chat->user)->phone;

        $reportado = trim(($senderName ?: '') .
                          (($senderEmail || $senderPhone) ? ' (' : '') .
                          ($senderEmail ?: '') .
                          (($senderEmail && $senderPhone) ? ' / ' : '') .
                          ($senderPhone ?: '') .
                          (($senderEmail || $senderPhone) ? ')' : ''));

        $add('Reportado por', $reportado);

        return implode("\n", $lines);
    }

    private function categoryKeyFromTitle(string $title): ?string
    {
        $map = [
            'Orden de Mantenimiento'                   => 'mantenimiento',
            'Orden de Limpieza'                        => 'limpieza',
            'Servicio de Mantenimiento de TI'          => 'ti',
            'Quejas y Sugerencias de los Restaurantes' => 'quejas_rest',
            'Servicio Médico'                          => 'medico',
            'Incendio/Humo'                            => 'incendio_humo',
            'Seguridad'                                => 'seguridad',
        ];

        if (isset($map[$title])) return $map[$title];

        // tolerant match (accents/case)
        $norm = function ($s) {
            $s = mb_strtolower(trim($s), 'UTF-8');
            return strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
        };

        $needle = $norm($title);
        foreach ($map as $label => $key) {
            if ($norm($label) === $needle) return $key;
        }
        return null;
    }
}