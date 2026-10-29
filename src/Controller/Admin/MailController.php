<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Mail;
use App\Form\SendMailType;
use App\Repository\MailRepository;
use App\Repository\ReservationRepository;
use App\Service\AdminActivityLogger;
use App\Service\FileUploadService;
use App\Service\MailService;
use App\Service\SettingService;
use App\Service\VehicleImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/mail')]
class MailController extends AbstractController
{
  public function __construct(
    private readonly MailRepository $repo, 
    private readonly EntityManagerInterface $em,
    private readonly ReservationRepository $reservationRepo,
    private readonly AdminActivityLogger $logger,
    private  VehicleImageService $imageService,
    private string $uploadDirContrat,
    private readonly FileUploadService $uploader,
    private readonly MailService $mailService,
    private SettingService $settingService ) {}

     #[Route('', name: 'admin_mail_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/mail/index.html.twig', [
            'mails' => $this->repo->findBy([], []),
        ]);
    }

    #[Route('/new', name: 'admin_mail_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm(new Mail(), $request, isNew: true);
    }

    #[Route('/{id}/delete', name: 'admin_mail_delete', methods: ['POST'])]
    public function delete(Mail $sms, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('mail_delete_'.$sms->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_mail_index');
        }
        $id = $sms->getId();
        $this->em->remove($sms);
        $this->em->flush();
        $this->logger->log('mail.delete', 'Mail', $id);
        $this->addFlash('success', 'Mail supprime.');
        return $this->redirectToRoute('admin_mail_index');
    }

    private function handleForm(Mail $sms, Request $request, bool $isNew): Response
    {
        $form = $this->createForm(SendMailType::class, $sms);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $reservation =  $form->get('reservation')->getData();
            $orderNumber =  $form->get('orderNumber')->getData();
            $reservation = $this->reservationRepo->findOneBy(['reservationNumber' => $orderNumber]);
            if(!empty($reservation)){
                $dateEtablissementContrat =  $form->get('dateEtablissementContrat')->getData();
                $heureContrat =  $form->get('heureContrat')->getData();
                $minuteContrat =  $form->get('minuteContrat')->getData();
                $cardIdNumber =  $form->get('cardIdNumber')->getData();
                return $this->render('emails/email-contrat-vente.html.twig',[
                    'reservation' => $reservation,
                    'dateEtablissementContrat' => $dateEtablissementContrat,
                    'heureContrat' => $heureContrat,
                    'minuteContrat' => $minuteContrat,
                    'settings' => $this->settingService->all(),
                    'cardIdNumber' => $cardIdNumber,
                ]);
            }else{
                $form->addError(new FormError('Le contrat est introuvable.'));
            }
        }
        return $this->render('admin/mail/form.html.twig', ['form' => $form, 'mail' => $sms, 'is_new' => true]);
    }

    #[Route('/send-contrat', name: 'admin_mail_send_contrat', methods: ['GET','POST'])]
    public function send(Request $request): Response
    {
            $form = $this->createForm(SendMailType::class, new Mail());
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $email =  $form->get('email')->getData();
                $contrat =  $form->get('contrat')->getData();
                $orderNumber =  $form->get('orderNumber')->getData();
                $reservation = $this->reservationRepo->findOneBy(['reservationNumber' => $orderNumber]);
                if(!empty($reservation)){
                    $result = $this->uploader->upload(
                        $contrat,
                        $this->uploadDirContrat,
                        FileUploadService::PROOF_MIME_TYPES,
                        FileUploadService::PROOF_MAX_SIZE,
                    );
                    $reservation->setContrat($result['filename']);
                    $reservation->setContratMimeType($result['mimeType']);
                    $filePath = $this->getParameter('app.upload_dir_contrat') .'/'.$result['filename'];
                    $this->em->persist($reservation);
                    $this->em->flush();
                    $form->getData()->setVehicle($reservation->getVehicle());
                    $this->em->persist($form->getData());
                    $this->em->flush();
                    $this->mailService->sendContrat($reservation,$email, $filePath, $result['filename']);
                    $this->addFlash('success','Contrat envoyé avec succès.');
                    return $this->redirectToRoute('admin_mail_index');
                }else{
                    $form->addError(new FormError('Le contrat est introuvable.'));
                }
            }
            return $this->render('admin/mail/send.html.twig', ['form' => $form, 'mail' => new Mail(), 'is_new' => true]);
    }
    #[Route('/ct', name: 'admin_generate_ct', methods: ['GET','POST'])]
    public function ct(Request $request): Response
    {
            $form = $this->createForm(SendMailType::class, new Mail());
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $vehicle =  $form->get('vehicle')->getData();
                return $this->render('emails/ct.html.twig', [
                    'vehicle' => $vehicle
                ]);
            }
            return $this->render('admin/mail/ct.html.twig', ['form' => $form, 'mail' => new Mail(), 'is_new' => true]);
    }
    #[Route('/rapport', name: 'admin_generate_rapport', methods: ['GET','POST'])]
    public function rapport(Request $request): Response
    {
            $form = $this->createForm(SendMailType::class, new Mail());
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $vehicle =  $form->get('vehicle')->getData();
                return $this->render('emails/fiche_dentretien.html.twig', [
                    'vehicle' => $vehicle
                ]);
            }
            return $this->render('admin/mail/rapport.html.twig', ['form' => $form, 'mail' => new Mail(), 'is_new' => true]);
    }

    #[Route('/infos', name: 'admin_generate_infos', methods: ['GET','POST'])]
    public function infos(Request $request): Response
    {
            $form = $this->createForm(SendMailType::class, new Mail());
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $gender =  $form->get('gender')->getData();
                $email =  $form->get('email')->getData();
                $vehicle =  $form->get('vehicle')->getData();
                $this->mailService->sendVehicleComplementaryInformation($vehicle,$email, $gender->label());
                $this->addFlash('success','Infos envoyé avec succès.');
            }
            return $this->render('admin/mail/infos.html.twig', ['form' => $form, 'mail' => new Mail(), 'is_new' => true]);
    }
}
