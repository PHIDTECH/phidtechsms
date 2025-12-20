<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsTransaction;
use App\Services\BeemSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsApiController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required', // string (comma-separated) or array
            'message' => 'required|string|min:1|max:1000',
            'sender_id' => 'nullable|string|max:11',
        ]);

        $user = $request->user();
        if (!$user || !$user->is_active) {
            return response()->json(['success' => false, 'error' => 'Unauthorized or inactive user'], 401);
        }

        $recipients = $this->normalizeRecipients($validated['to']);
        if (empty($recipients)) {
            return response()->json(['success' => false, 'error' => 'No valid recipients provided'], 422);
        }

        // Estimate credits: 1 credit per recipient per message part (simple estimation)
        $parts = $this->estimateParts($validated['message']);
        $requiredCredits = count($recipients) * $parts;

        if (!$user->hasSufficientCredits($requiredCredits)) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient SMS credits',
                'required_credits' => $requiredCredits,
                'available_credits' => $user->sms_credits,
            ], 402);
        }

        $service = new BeemSmsService();
        $result = $service->sendSms($recipients, $validated['message'], $validated['sender_id'] ?? null);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send SMS',
                'provider_code' => $result['error_code'] ?? null,
            ], 502);
        }

        // Deduct credits and log transaction
        $prev = $user->sms_credits;
        $user->deductCredits($requiredCredits);
        SmsTransaction::create([
            'user_id' => $user->id,
            'type' => SmsTransaction::TYPE_DEDUCTION,
            'amount' => $requiredCredits,
            'description' => 'API SMS send',
            'reference_id' => $result['message_id'] ?? null,
            'status' => SmsTransaction::STATUS_COMPLETED,
            'metadata' => [
                'recipients' => $recipients,
                'parts' => $parts,
                'sender_id' => $validated['sender_id'] ?? null,
                'previous_balance' => $prev,
                'new_balance' => $user->sms_credits,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message_id' => $result['message_id'] ?? null,
            'cost_credits' => $requiredCredits,
            'remaining_credits' => $user->sms_credits,
            'provider_response' => $result['response'] ?? null,
        ]);
    }

    private function normalizeRecipients($to): array
    {
        if (is_string($to)) {
            $parts = preg_split('/[\s,;]+/', $to, -1, PREG_SPLIT_NO_EMPTY);
            return array_values(array_unique($parts));
        }
        if (is_array($to)) {
            return array_values(array_unique(array_filter($to)));
        }
        return [];
    }

    private function estimateParts(string $message): int
    {
        $len = mb_strlen($message, 'UTF-8');
        $isUnicode = $this->containsNonGsm($message);
        $single = $isUnicode ? 70 : 160;
        $concat = $isUnicode ? 67 : 153; // per part when concatenated
        if ($len <= $single) return 1;
        return (int) ceil($len / $concat);
    }

    private function containsNonGsm(string $text): bool
    {
        // Rough check: if any char not in GSM-7 basic table
        $gsm7 = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\u{1B}ÆæßÉ !\"#¤%&'()*+,\-.\/0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($text, $i, 1, 'UTF-8');
            if (!str_contains($gsm7, $ch)) return true;
        }
        return false;
    }
}

