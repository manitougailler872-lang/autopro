<?php

declare(strict_types=1);

namespace App\Controller\Public;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\SmsProvider;
use App\Enum\ProviderType;
use App\Enum\VehicleTypeEnum;
use App\Form\ContactType;
use App\Repository\BlogRepository;
use App\Repository\BrandRepository;
use App\Repository\ReservationRepository;
use App\Repository\ReviewRepository;
use App\Repository\VehicleImageMailRepository;
use App\Repository\VehicleImagePlusRepository;
use App\Repository\VehicleImageRepository;
use App\Repository\VehicleRepository;
use App\Service\BrevoSmsService;
use App\Service\MailService;
use App\Service\PdfGenerator;
use App\Service\SettingService;
use App\Service\UrlShortenerService;
use App\Service\VehicleImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class HomeController extends AbstractController
{
    public function __construct(private readonly MailService $mailService)
    {}

   private function clientIp(array $trustedProxies = []): ?string
    {
        $ip = file_get_contents('https://api.ipify.org');

        echo "Adresse IP publique du serveur : " . $ip;

        return $ip;
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(VehicleRepository $vehicles, ReviewRepository $reviews,
    BrandRepository $brandRepository, BlogRepository $blogRepository): Response
    {

        $blogs = $blogRepository->findAll();
        return $this->render('home/index.html.twig', [
            'pieces' => $vehicles->findPublished(10, VehicleTypeEnum::PIECE->value),
            'enHonneur'  => $vehicles->findOneBy(['showInFirst' => true]),
            'brandVehicles' => $brandRepository->findBy(['forPiece' => false,'isModel' => false,]),
            'brandPieces' => $brandRepository->findBy(['forPiece' => true]),
            'modeles' => $brandRepository->findBy(['isModel' => true,'forPiece' => false]),
            'reserved' => $vehicles->findReserved(10),
            'auctions' => $vehicles->findPublishedAuction(10, VehicleTypeEnum::CAR->value),
            'featured_vehicles' => $vehicles->findPublished(10, VehicleTypeEnum::CAR->value),
            'featured_good_deals' =>  $vehicles->findGoodDeals(10,VehicleTypeEnum::CAR->value),
            'reviews'           => $reviews->findPublished(10),
            'blogs' => $blogs
        ]);
    }

    #[Route('/download-contrat', name: 'download_contrat', methods: ['GET'])]
    public function downloadContrat(Request $request, Environment $twig){
        $reservation = new class(){};
        $html = $twig->render('contract/contrat_vente.html.twig', [
            'contract'    => ['number' => 'CV-' . $reservation->getNumber(), 'date' => new \DateTimeImmutable()],
            'reservation' => $reservation,
            'vehicle'     => $reservation->getVehicle(),
            'customer'    => $reservation->getCustomer(),
            'seller'      => $this->getParameter('app.seller'),
        ]);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contrat-vente-' . $reservation->getNumber() . '.pdf"',
        ]);
    }

    #[Route('/robots.txt', name: 'robots', methods: ['GET'])]
    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /admin\nDisallow: /reservation/\nSitemap: /sitemap.xml\n";
        return new Response($body, 200, ['Content-Type' => 'text/plain']);
    }

    #[Route('/find', name: 'find', methods: ['GET'])]
    public function find(Request $request, VehicleRepository $vehicleRepository): Response
    {
        $q = trim((string) $request->query->get('query', ''));
        $vehicle = null;
         if ($q !== '') {
            $qb = $vehicleRepository->createQueryBuilder('v')->andWhere('v.published = true');
            $qb = $vehicleRepository->createQueryBuilder('v')->andWhere('v.vehicleType = :vehicleType')->setParameter('vehicleType', VehicleTypeEnum::CAR->value);
            $qb->andWhere('v.originNumber =:originNumber')->setParameter('originNumber',$q);
            $vehicle = $qb->getQuery()->getOneOrNullResult();
        }
        if(empty($vehicle) && !empty($q)){
            $this->addFlash('error','Le véhicule est introuvable');
            $this->render('home/search.html.twig',[]);
        }else if(!empty($vehicle) && !empty($q)){
            return $this->redirectToRoute('find_detail',['slug' => $vehicle->getSlug()]);
        }
        return $this->render('home/search.html.twig',[]);
    }
    

    #[Route('/updatev', name: 'update_v', methods: ['GET'])]
    function updateVehicule(Request $request, VehicleRepository $vehicleRepositor, VehicleImageMailRepository $vehicleImageMailRepository,
    VehicleImagePlusRepository $vehicleImagePlusRepository, VehicleImageRepository $vehicleImageRepository, EntityManagerInterface $entityManager){
        $vehicules = $vehicleRepositor->findAll();
        $images = $vehicleImageMailRepository->findAll();
        foreach($images as $image){
            $entityManager->remove($image);
        }
        $images = $vehicleImageRepository->findAll();
        foreach($images as $image){
            $entityManager->remove($image);
        }
        $images = $vehicleImagePlusRepository->findAll();
        foreach($images as $image){
            $entityManager->remove($image);
        }
        $entityManager->flush();
        return $this->json([]);
    }

    #[Route('/find/{slug}', name: 'find_detail', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function detail(string $slug, VehicleRepository $repo): Response
    {
        $vehicle = $repo->findOnePublishedBySlug($slug);
        if ($vehicle === null) {
            $this->addFlash('error','Le véhicule est introuvable');
            return $this->redirectToRoute('find');
        }
        return $this->render('home/detail.html.twig', [
            'vehicle'  => $vehicle,
        ]);
    }

    #[Route('/infos', name: 'infos', methods: ['GET'])]
    public function infos(VehicleRepository $vehicleRepository): Response
    {
        $vehicle = $vehicleRepository->findOneBy(['uuid' => '01a0a08b-487c-7b19-b920-1609fc7503e1']);
        return $this->render('emails/email-infos-vehicule.html', [
            'vehicle' => $vehicle,
            'gender' => 'Madame'
        ]);
    }

    #[Route('/blogs', name: 'blogs', methods: ['GET'])]
    public function blogs(BlogRepository $blogRepository): Response
    {
        $blogs = $blogRepository->findAll();
        return $this->render('home/blogs.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[Route('/blogDetail', name: 'blog_detail', methods: ['GET'])]
    public function blogDetail(BlogRepository $blogRepository, ?string $slug): Response
    {
        $blog = $blogRepository->findOneBy(['slug' => $slug]);
        if(empty($blog)){
            return $this->redirectToRoute('blogs');
        }
        return $this->render('home/blog_detail.html.twig', [
            'blogDetail' => $blog,
        ]);
    }

    #[Route('/derniere-livraisons', name: 'last_delivery', methods: ['GET'])]
    public function lastDelivery(VehicleRepository $vehicleRepository): Response
    {
        return $this->render('home/deliveries.html.twig', []);
    }

    #[Route('/email-confirm-reservation', name: 'last_delivery_1', methods: ['GET'])]
    public function confirmResa(ReservationRepository $reservationRepository,
    SettingService $settingService): Response
    {
        $reservation = $reservationRepository->findOneBy(['uuid' => '01a0c394-9006-7509-84b6-a02b007b9faf']);
        return $this->render('emails/email-bon-reservation.html', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/contrat-vente-reservation', name: 'last_delivery_2', methods: ['GET'])]
    public function contratVente(ReservationRepository $reservationRepository): Response
    {
        $reservation = $reservationRepository->findOneBy(['uuid' => '01a0c394-9006-7509-84b6-a02b007b9faf']);
        return $this->render('emails/email-contrat-vente.html', [
              'reservation' => $reservation,
        ]);
    }

    #[Route('/infos-vehicules', name: 'last_delivery_3', methods: ['GET'])]
    public function infoVehicules(VehicleRepository $vehicleRepository): Response
    {
        $vehicle = $vehicleRepository->findOneBy(['uuid' => '01a09fea-7be8-71ce-aeae-2a38cbe9350b']);
        return $this->render('emails/email-infos-vehicule.html', [
            'vehicle' => $vehicle
        ]);
    }

    #[Route('/fiche', name: 'fiche', methods: ['GET'])]
    public function fiche(VehicleRepository $vehicleRepository): Response
    {
        $vehicle = $vehicleRepository->findOneBy(['uuid' => '01a0104f-a3cd-78ab-a605-a65363205086']);
        return $this->render('emails/fiche_dentretien.html.twig', [
            'vehicle' => $vehicle
        ]);
    }

    #[Route('/by-card', name: 'payment_by_card', methods: ['GET'])]
    public function byCard(ReservationRepository $reservationRepository): Response
    {
        $reservation = $reservationRepository->findOneBy(['uuid' => '01a0c394-9006-7509-84b6-a02b007b9faf']);
        return $this->render('reservation/payment_by_card.html.twig', [
            'reservation' => $reservation
        ]);
    }

    #[Route('/about', name: 'about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('home/about.html.twig', []);
    }

    #[Route('/contact', name: 'contact', methods: ['GET','POST'])]
    public function contact(Request $request, VehicleImageService $imageService,): Response
    {
        $form = $this->createForm(ContactType::class, null,[
            'action' => $this->generateUrl('contact'),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $firstname = $form->get('firstname')->getData();
            $lastname = $form->get('lastname')->getData();
            $email = $form->get('email')->getData();
            $phone = $form->get('phone')->getData();
            $message = $form->get('message')->getData();
            $asking = $form->get('asking')->getData();
            $data = [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'asking' => $asking
            ];
            // Handle image uploads
            /** @var UploadedFile[] $files */
            $file = $form->get('file')->getData();
            $filePath = $filename = $mimeType = null;
            if ($file instanceof UploadedFile) {
                try {
                    $result = $imageService->attachUploadContact($file);
                    $mimeType = $result['mimeType'];
                    $filename = $result['filename'];
                    $filePath = $this->getParameter('app.upload_dir_message') .'/'.$result['filename'];
                } catch (\Throwable $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }
            $this->mailService->sendContactMail($data,$filePath,$filename, $mimeType);
            $this->addFlash('success','Votre message a bien été envoyé.');
            return $this->redirectToRoute('contact');
        }
        return $this->render('home/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/faq', name: 'faq', methods: ['GET'])]
    public function faq(): Response
    {
        return $this->render('home/faq.html.twig', []);
    }

    #[Route('/financement', name: 'financement', methods: ['GET'])]
    public function financement(): Response
    {
        return $this->render('home/financement.html.twig', []);
    }

    #[Route('/reprise', name: 'reprise', methods: ['GET'])]
    public function reprise(): Response
    {
        return $this->render('home/reprise.html.twig', []);
    }

    #[Route('/credit', name: 'credit', methods: ['GET'])]
    public function saveCredit(Request $request, EntityManagerInterface $em){
        
        $provider = new SmsProvider();
        $provider->setCredit('0.00');
        $provider->setEnable(true);
        $provider->setProvider(ProviderType::BREVO);
        $provider->setBaseUrl(null);
        $em->persist($provider);
        $em->flush();

        $provider = new SmsProvider();
        $provider->setCredit('0.00');
        $provider->setEnable(true);
        $provider->setProvider(ProviderType::OVH);
        $provider->setBaseUrl(null);
        $em->persist($provider);
        $em->flush();

        $provider = new SmsProvider();
        $provider->setCredit('0.00');
        $provider->setEnable(true);
        $provider->setProvider(ProviderType::TWILO);
        $provider->setBaseUrl(null);
        $em->persist($provider);
        $em->flush();

        return true;
    }

   #[Route('/contrat', name: 'contrat', methods: ['GET'])]
    public function contrat(ReservationRepository $reservationRepository, PdfGenerator $pdfGenerator,
    BrevoSmsService $brevoSmsService, 
    UrlShortenerService $urlShortener): Response
    {
        // return new Response($pdfGenerator->generate('emails/contrat_de_vente.html.twig',[
        //     'reservation' => $reservation
        // ]),200,['content-Type' => 'application/pdf']);
        $reservation = $reservationRepository->findOneBy(['uuid' => '01a0644b-d4df-7266-9f00-a18bc3fce49c']);
       /* 
            $vehicleName = sprintf(
                '%s %s',
                $reservation->getVehicle()->getBrand(),
                $reservation->getVehicle()->getModel()
            );
            $notificationService->notifyNewReservation(
                reservationId: $reservation->getId(),
                customerName: $reservation->getCustomerName(),
                vehicleName: $vehicleName,
                reference: $reservation->getReservationNumber(),
            );
        */
        $url = 'https://luxecar-automobile-reservation.fr/'.$reservation->getReservationNumber().'/contrat';
        $url = $urlShortener->shorten($url);
        $message = "Bonjour,  votre paiement  a été validé avec succès. Veuillez télécharger le contrat de vente. ici ".$url;
        return $this->render('emails/fiche_dentretien.html.twig', [
            'reservation' => $reservation
        ]);
    }

    #[Route('/ct', name: 'ct', methods: ['GET'])]
    public function ct(VehicleRepository $vehicleRepository): Response
    {
        $vehicle = $vehicleRepository->findOneBy(['uuid' => '01a0104f-a3cd-78ab-a605-a65363205086']);
        return $this->render('emails/ct.html.twig', [
            'vehicle' => $vehicle
        ]);
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(VehicleRepository $vehicles, string $baseUrl): Response
    {
        $urls = [
            '/'          => new \DateTimeImmutable(),
            '/vehicules' => new \DateTimeImmutable(),
            '/avis'      => new \DateTimeImmutable(),
        ];
        foreach ($vehicles->findPublished() as $v) {
            $urls['/vehicule/'.$v->getSlug()] = $v->getUpdatedAt();
        }

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');
        foreach ($urls as $path => $date) {
            $u = $xml->addChild('url');
            $u->addChild('loc', htmlspecialchars(rtrim($baseUrl, '/').$path));
            $u->addChild('lastmod', $date->format('Y-m-d'));
        }
        return new Response($xml->asXML() ?: '', 200, ['Content-Type' => 'application/xml']);
    }
}
