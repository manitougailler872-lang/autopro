<?php

namespace App\Service;

use App\Entity\Sms;
use Brevo\Brevo;
use Brevo\Exceptions\BrevoApiException;
use Brevo\Exceptions\BrevoException;
use Brevo\Types\SendTransacSms;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BrevoSmsService
{
    private Brevo $brevo;

    public function __construct(
        #[Autowire('%env(BREVO_API_KEY)%')]
        string $apiKey,
    ) {
        $this->brevo = new Brevo(
            apiKey: $apiKey
        );
    }

    public function send(
        string $phone,
        string $sender,
        string $message
    ): mixed {
         $sms = new SendTransacSms([
        'recipient' => $this->normalizePhone($phone),
        'sender' => $sender,
        'content' => $message,
        'type' => 'transactional',
    ]);
        try {
            return $this->brevo
                ->transactionalSms
                ->sendAsyncTransactionalSms($sms);
        } catch (BrevoApiException $e) {
             return $e->getMessage();
        } catch (BrevoException $e) {
            return $e->getMessage();
        }
    }

    public function getSmsCredits(): ?float
    {
        try {
            $account = $this->brevo
                ->account
                ->getAccount();
            foreach ($account->plan ?? [] as $plan) {
                if (($plan->type ?? null) === 'sms') {
                    return (float) ($plan->credits ?? 0);
                }
            }

            return null;

        } catch (BrevoApiException $e) {
            return $e->getMessage();
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // 0756919935 => 33756919935
        if (str_starts_with($phone, '0')) {
            $phone = '33' . substr($phone, 1);
        }

        // +33756919935 => 33756919935
        return ltrim($phone, '+');
    }
}