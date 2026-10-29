<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Port : envoi du code à usage unique au signataire.
 *
 * Implémente-le avec ton module SMS/OTP existant (Twilio, SMSFactor, SMS Proxima...), par exemple :
 *
 *   final class SmsOtpSender implements OtpSenderInterface
 *   {
 *       public function __construct(private readonly YourSmsGateway $sms) {}
 *
 *       public function send(string $phone, string $code, string $contractNumber): void
 *       {
 *           $this->sms->send($phone, sprintf('AutoPro : votre code de signature du contrat %s est %s. Il expire dans 10 minutes.', $contractNumber, $code));
 *       }
 *   }
 *
 * Puis, dans config/services.yaml :  App\Signature\OtpSenderInterface: '@App\Signature\SmsOtpSender'
 */
interface OtpSenderInterface
{
    /** @throws \Throwable si le message n'a pas pu être envoyé */
    public function send(string $phone, string $code, string $contractNumber): void;
}
