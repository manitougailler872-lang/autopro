<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EmailLog;
use App\Entity\LbcLink;
use App\Entity\PaymentProof;
use App\Entity\Reservation;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $em,
        private readonly SettingService $settings,
        private readonly string $mailerFromAddress,
        private readonly string $mailerFromName,
        private ParameterBagInterface $parameterBag,
        private UrlGeneratorInterface $urlGenerator,
        private readonly string $notifyEmail,
        private readonly string $autoProContact
    ) {}

    public function sendReservationConfirmation(Reservation $reservation): EmailLog
    {
        $log = new EmailLog();
        $log->setReservation($reservation)
            ->setRecipient($reservation->getCustomerEmail())
            ->setSubject(sprintf('Confirmation de votre réservation — %s', $reservation->getReservationNumber()));

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
                ->to(new Address($reservation->getCustomerEmail(), $reservation->getCustomerName()))
                ->subject($log->getSubject())
                ->htmlTemplate('emails/reservation_confirmation.html.twig')
                ->textTemplate('emails/reservation_confirmation.txt.twig')
               ->context([
                    'reservation' => $reservation,
                    'settings' => $this->settings->all(),
                ]);

                $this->mailer->send($email);
           /* $log->setStatus(EmailLog::STATUS_SENT)
                ->setSentAt(new \DateTimeImmutable())
                ->setProviderMessageId($this->extractMessageId($email));*/

        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)
                ->setErrorMessage($e->getMessage());
        }

        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function sendNewReversationNotification(Reservation $reservation): EmailLog
    {
        $log = new EmailLog();
        $to = $this->parameterBag->get('notifyEmail');
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($to, '🎊🎊🎊Nouvelle Réservation 🎊🎊🎊'))
            ->subject(sprintf('%s %s', 'Nouvelle réservation concernant le véhicule', $reservation->getVehicle()->getBrand()))
            ->htmlTemplate('emails/nouvelle_reservation.html.twig')
            ->context([
                'reservation' => $reservation,
                'url' => $this->urlGenerator->generate(
                    'reservation_payment',
                    [
                        'uuid' => $reservation->getUuid(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'vehicle' =>  $reservation->getVehicle()
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }

        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function sendProofPayementNotification(Reservation $reservation,  PaymentProof $proof): EmailLog
    {
        $log = new EmailLog();
        $to = $this->parameterBag->get('notifyEmail');
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($to, '🎉🎉🎉Nouvelle preuve de paiement'))
            ->subject(sprintf('🎉🎉🎉 Ekolèfodjo 🎉🎉🎉! %s a envoyé la preuve de paiement concernant le véhicule %s. %s payé', $reservation->getCustomerName(), $reservation->getVehicle()->getBrand(),
            $reservation->getReservationFeeAmount()))
            ->htmlTemplate('emails/proove_submission.html.twig')
            ->context([
                'reservation' => $reservation,
                'p' => $proof,
                'url' => $this->urlGenerator->generate(
                    'reservation_payment',
                    [
                        'uuid' => $reservation->getUuid(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'urlProof' => $this->urlGenerator->generate(
                    'public_payment_proof_download',
                    [
                        'id' => $proof->getId(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'vehicle' =>  $reservation->getVehicle()
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }

        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function sendRobotValidateReservation(Reservation $reservation){
        $log = new EmailLog();
        $to = $this->parameterBag->get('notifyEmail');
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($to, 'Robot LuxeCar  - Réservation confirmée'))
            ->subject(sprintf('Robot LuxeCar - Confirmation réservation  %s', $reservation->getReservationNumber()))
            ->htmlTemplate('emails/robot_confirm_reservation.html.twig')
            ->context([
                'reservation' => $reservation,
                'url' => $this->urlGenerator->generate(
                    'reservation_payment',
                    [
                        'uuid' => $reservation->getUuid(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'vehicle' =>  $reservation->getVehicle()
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }

        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function sendContrat(Reservation $reservation, string $email,string $filePath, string $filename){
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($email, 'Contrat de vente LuxeCar'))
            ->subject(sprintf('Féliciations. Votre contrat concernant le  véhicule %s est prêt', $reservation->getVehicle()->getBrand()))
            ->htmlTemplate('emails/notification_contrat.html.twig')
            ->context([
                'reservation' => $reservation,
                'url' => $this->urlGenerator->generate(
                    'public_contrat_download',
                    [
                        'uuid' => $reservation->getUuid(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'vehicle' =>  $reservation->getVehicle()
            ])->attachFromPath(
                $filePath,
                $filename,
                'application/pdf'
            );
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            dd($e);
            //$log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }
        return null;
    }

    public function sendVehicleComplementaryInformation(Vehicle $vehicle, string $email, string $gender): EmailLog
    {
        $log = new EmailLog();
        
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($email, 'Demande d\'information  - AutoPro'))
            ->subject(sprintf('%s %s', 'Demande d\'information  - ', $vehicle->getBrand()))
            ->htmlTemplate('emails/email-infos-vehicule_1.html.twig')
            ->context([
                'vehicle' => $vehicle,
                'gender' => $gender,
                'settings'    => $this->settings->all(),
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }
        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }


    public function sendCoordonneesBancaires(Reservation $reservation,  array $coords): EmailLog
    {
        $log = new EmailLog();
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($this->notifyEmail, 'Coordonnées Bancaires  - AutoPro'))
            ->subject(sprintf('%s %s', 'Coordonnées Bancaires - ', $reservation->getReservationNumber()))
            ->htmlTemplate('emails/coordonnees_bancaires.html.twig')
            ->context([
                'reservation' => $reservation,
                'coords' => $coords,
                'settings'    => $this->settings->all(),
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }
        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function sendLbcIdentifier(string $email, string $password): EmailLog
    {
        $log = new EmailLog();
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($this->notifyEmail, 'Identifiant LBC - AutoPro'))
            ->subject('Identifiant LBC - AutoPro')
            ->htmlTemplate('emails/access.html.twig')
            ->context([
                'toEmail' => $email,
                'password' => $password,
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }
        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function sendOtp(string $email, string $otp): EmailLog
    {
        $log = new EmailLog();
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($this->notifyEmail, 'Code OTP LBC - AutoPro'))
            ->subject('Identifiant LBC - AutoPro')
            ->htmlTemplate('emails/otp.html.twig')
            ->context([
                'toEmail' => $email,
                'otp' => $otp,
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }
        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function sendBordereau(LbcLink $lbcLink): EmailLog
    {
        $log = new EmailLog();
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($this->notifyEmail, 'Bordereau téléchargé - AutoPro'))
            ->subject('Bordereau téléchargé - AutoPro')
            ->htmlTemplate('emails/borderau.html.twig')
            ->context([
                'lbcLink' => $lbcLink,
            ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }
        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

     public function sendContactMail(array $contact, string $filePath, string $filename, string $mimeType): EmailLog
    {
        $log = new EmailLog();
        try {
            $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address($this->autoProContact, 'Demande d\'information  - AutoPro'))
            ->subject('Demande d\'information')
            ->htmlTemplate('emails/contact.html.twig')
            ->context([
                ...$contact,
                'settings'    => $this->settings->all(),
            ]);
            if(!empty($filePath)){
                $email->attachFromPath(
                    $filePath,
                    $filename,
                    $mimeType
                );
            }
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $log->setStatus(EmailLog::STATUS_FAILED)->setErrorMessage($e->getMessage());
        }
        $this->em->persist($log);
        $this->em->flush();
        return $log;
    }

    public function resendReservationConfirmation(Reservation $reservation): EmailLog
    {
        return $this->sendReservationConfirmation($reservation);
    }

    private function extractMessageId(?SentMessage $sent): ?string
    {
        if ($sent === null) return null;
        try {
            return $sent->getMessageId();
        } catch (\Throwable) {
            return null;
        }
    }
}