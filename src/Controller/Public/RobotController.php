<?php

namespace App\Controller\Public;

use App\Entity\Sms;
use App\Entity\UrlShorter;
use App\Enum\ProviderType;
use App\Enum\ReservationStatus;
use App\Enum\SmsStatus;
use App\Repository\ExpediteurRepository;
use App\Repository\ReservationRepository;
use App\Repository\SmsProviderRepository;
use App\Service\BrevoSmsService;
use App\Service\MailService;
use App\Service\ReservationService;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RobotController extends AbstractController
{
    #[Route(
        '/cron/reservations/validate/{token}',
        name: 'cron_reservations_validate',
        methods: ['GET']
    )]
    public function validateReservations(
        string $token,
        EntityManagerInterface $em,
        ReservationService $reservationService,
        ReservationRepository $reservationRepository,
        BrevoSmsService $brevoSmsService,
        SettingService $settingService,
        MailService $mailService,
        ExpediteurRepository $expediteurRepository,
        UrlGeneratorInterface $urlGenerator,
        SmsProviderRepository $smsProviderRepository
    ): JsonResponse {
      
        if ($token !== $_ENV['ROBOT_TOKEN']) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        $count = 0;
        $reservations = $reservationRepository->findBy(['status' =>ReservationStatus::PENDING_PAYMENT]);

        $defaultExpediteur = $settingService->get(SettingService::EXPEDITEUR_PAR_DEFAULT,'AutoPro');
        $activeSms = (bool)$settingService->get(SettingService::ACTIVE_SMS,false);
  
        foreach($reservations as $reservation){
          $em->getConnection()->beginTransaction();
           try{
                $reservationService->confirm($reservation);
                $mailService->sendReservationConfirmation($reservation);
                if(!empty($defaultExpediteur) && null != ($formtPhone = $this->formatPhoneNumber($reservation->getCustomerPhone())) && $activeSms){
                    $url = $this->generateUrl('reservation_payment',['uuid' => $reservation->getUuid() ],  UrlGeneratorInterface::ABSOLUTE_URL);
                    // Création du url shorter
                    $urlShorter = new UrlShorter();
                    $urlShorter->setUrl($url);
                    $em->persist($urlShorter);
                    $em->flush();
                    $url = $urlGenerator->generate('launch_url',['code' => $urlShorter->getCode()],UrlGeneratorInterface::ABSOLUTE_URL);
                    $message = sprintf(
                        'Bonjour %s, réservation confirmée. Réf. à présenter au garage: %s',
                        $reservation->getCustomerName(),$reservation->getReservationNumber()
                    );
                    $response = $brevoSmsService->send($formtPhone,$defaultExpediteur,$message);
                    if(!empty($response)){
                        $expediteur = $expediteurRepository->findOneBy(['name' => $defaultExpediteur]);
                        $sms = new Sms();
                        $sms->setReservation($reservation);
                        $sms->setNumero($formtPhone);
                        $sms->setReferenceSms((string)$response->messageId);
                        $sms->setVehicle($reservation->getVehicle());
                        $sms->setMessage($message);
                        $sms->setStatus(SmsStatus::PROCESSING);
                        $sms->setExpediteur($expediteur);
                        $em->persist($sms);
                        $em->flush();
                        $credit = $brevoSmsService->getSmsCredits();
                        $smsProvider = $smsProviderRepository->findOneBy(['provider' => ProviderType::BREVO]);
                        if(!empty($smsProvider)){
                            $smsProvider->setCredit($credit);
                            $em->persist($sms);
                            $em->flush();
                        }
                    }
                }
                $mailService->sendRobotValidateReservation($reservation);
                $count++;
                $em->getConnection()->commit();
           }catch(\Exception $e){
                $em->getConnection()->rollBack();
                throw $e;
           }
        }
        return $this->json([
            'success' => true,
            'validated' => $count,
            'executedAt' => date('Y-m-d H:i:s'),
        ]);
    }

    function formatPhoneNumber(string $phone): ?string
    {
        // Supprime tous les espaces
        $phone = preg_replace('/[\s.]+/', '', trim($phone));
        // Obligatoirement :
        // - 10 chiffres
        // - commence par 0
        if (!preg_match('/^0\d{9}$/', $phone)) {
            return null;
        }
        // 0644695870 => 0033644695870
        return '33' . substr($phone, 1);
    }
}