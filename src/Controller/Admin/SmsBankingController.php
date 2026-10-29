<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Sms;
use App\Enum\ProviderType;
use App\Enum\SmsStatus;
use App\Form\SmsType;
use App\Repository\SmsProviderRepository;
use App\Repository\SmsRepository;
use App\Service\AdminActivityLogger;
use App\Service\BrevoSmsService;
use App\Service\UrlShortenerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/sms')]
class SmsBankingController extends AbstractController
{
   public function __construct(
    private readonly SmsRepository $repo, 
    private readonly EntityManagerInterface $em,
    private readonly AdminActivityLogger $logger, private BrevoSmsService $brevoSmsService,
    private readonly UrlShortenerService $urlShortener,
    private SmsProviderRepository $smsProviderRepository ) {}

    #[Route('', name: 'admin_sms_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/sms/index.html.twig', [
            'smsList' => $this->repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_sms_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new Sms(), $request, isNew: true);
    }

    #[Route('/{id}/delete', name: 'admin_sms_delete', methods: ['POST'])]
    public function delete(Sms $sms, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('sms_delete_'.$sms->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_sms_index');
        }
        $id = $sms->getId();
        $this->em->remove($sms);
        $this->em->flush();
        $this->logger->log('sms.delete', 'Sms', $id);
        $this->addFlash('success', 'Sms supprime.');
        return $this->redirectToRoute('admin_sms_index');
    }


    private function handleForm(Sms $sms, Request $request, bool $isNew,
    ): Response
    {
        $form = $this->createForm(SmsType::class, $sms);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $message = $form->get('message')->getData();
            $url =  $form->get('url')->getData();
            //$fullname =  $form->get('name')->getData();
            $numero =  $form->get('numero')->getData();
            $expediteur =  $form->get('expediteur')->getData();
           // $reservation =  $form->get('reservation')->getData();
           // $vehicule =  $form->get('vehicle')->getData();
            $referenceLbc =  $form->get('referenceLbc')->getData();
            $mailTo =  $form->get('mailTo')->getData();

        //    $price = $vim = $immat = $reference = $reference_paiement_lbc = $car = "";
        //    if(empty($fullname) && !empty($reservation)){
        //         $fullname = $reservation->getCustomerName();
        //     }
        //     if(!empty($vehicule)){
        //         $price = $vehicule->getPrice(). '€';
        //         $immat =  $vehicule->getImmatriculation() ?? '';
        //         $vim =  $vehicule->getVim() ?? '';
        //         $car =  sprintf('%s %s', $vehicule->getBrand() ?? '', $vehicule->getYear() ?? '');
        //     }
        //     if(!empty($reservation)){
        //         $reference = $reservation->getReservationNumber();
        //     }
        //     if(!empty($referenceLbc)){
        //         $reference_paiement_lbc = $referenceLbc;
        //     }
        //     // if(!empty($url)){
        //     //     $url = $this->urlShortener->shorten($url);
        //     // }
        //     $replacements = [
        //         '{{url}}' =>  $url ?? '',
        //         '{{name}}' => $fullname,
        //         '{{car}}' => $car,
        //         '{{vim}}' => $vim,
        //         '{{price}}' => $price,
        //         '{{immat}}' => $immat,
        //         '{{reference}}' => $reference,
        //         '{{reference_paiement_lbc}}' => $reference_paiement_lbc
        //     ];
        //     foreach($replacements as $key => $value){
        //         $message = str_replace((string)$key,(string)$value, (string)$message);
        //     }
            try{
               $response =  $this->brevoSmsService->send((string)$numero, $expediteur->getName(), $message);
               $messageId = null;
               if(!empty($response)){
                  $messageId = $response->messageId;
               }
                $sms->setReferenceSms((string)$messageId);
                $sms->setStatus(SmsStatus::PROCESSING);
                $sms->setMessage($message);
                $this->em->persist($sms);
                $this->em->flush();
                // MAJ Credits
                $findCredit = $this->smsProviderRepository->findOneBy(['provider' => ProviderType::BREVO]);
                if(!empty($findCredit)){
                    $restCredit  = $this->brevoSmsService->getSmsCredits();
                    $findCredit->setCredit((string)$restCredit);
                    $this->em->persist($findCredit);
                    $this->em->flush();
                }
                $this->logger->log($isNew ? 'sms.create' : 'sms.update', 'Sms', $sms->getId());
                $this->addFlash('success','Sms envoyé.');
            }catch(\Exception $e){
                dd($e);
                $this->addFlash('error','Sms non envoyé.');
                return $this->render('admin/sms/form.html.twig', ['form' => $form, 'sms' => $sms, 'is_new' => true]);
            }
            return $this->redirectToRoute('admin_sms_index');
        }
        //dd($form);
        return $this->render('admin/sms/form.html.twig', ['form' => $form, 'sms' => $sms, 'is_new' => true]);
    }
}
