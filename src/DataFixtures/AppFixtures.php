<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AdminUser;
use App\Entity\Review;
use App\Entity\Setting;
use App\Entity\Vehicle;
use App\Enum\FuelType;
use App\Enum\ReviewStatus;
use App\Service\SettingService;
use App\Service\SlugService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly SlugService $slugger,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Admin DEV
        $admin = new AdminUser();
        $admin->setEmail('admin@luxecar.local')
              ->setDisplayName('Admin DEV')
              ->setRoles(['ROLE_ADMIN'])
              ->setPassword($this->hasher->hashPassword($admin, 'admin1234!'));
        $manager->persist($admin);

        // Settings
        $settings = [
            SettingService::COMPANY_NAME          => 'LUXE CAR AUTOMOBIL',
            SettingService::COMPANY_ADDRESS       => '15 Rue des Lilas, 75000 Paris, France',
            SettingService::COMPANY_EMAIL         => 'contact@luxecar-automobil.fr',
            SettingService::COMPANY_PHONE         => '01 23 45 67 89',
            SettingService::RESERVATION_PERCENTAGE => '20',
            SettingService::BANK_HOLDER           => 'LUXE CAR AUTOMOBIL SAS',
            SettingService::BANK_NAME             => 'Banque exemple',
            SettingService::BANK_IBAN             => 'FR76 3000 4001 2300 0001 2345 678',
            SettingService::BANK_BIC              => 'AGRIFRPP',
            SettingService::PAYMENT_MESSAGE       => "Merci d'effectuer le virement en mentionnant votre numéro de réservation en référence.",
            SettingService::TIKTOK_URL            => 'https://www.tiktok.com/@luxecar',
            SettingService::TIKTOK_TEXT           => 'Suivez-nous sur TikTok',
            SettingService::COMPANY_LOGO           => 'default_company_logo.png',
            SettingService::EXPEDITEUR_PAR_DEFAULT           => 'AutoPro',
            SettingService::OPENING_HOUR           => 'Lundi - Vendredi : 9h00 - 12h00 / 14h00 - 18h30
                                                       Samedi : 9h00 - 12h00
                                                       Dimanche : Fermé',
            SettingService::BORDERAU_MESSAGE   => 'Bonjour %user%, Votre paiement sécurisé avec la %reference% a bien été nregistré avec succès. Veuillez confirmer l\'envoi du borderau de paiement au vendeur en suivant ce lien: %url% ',
        ];
        foreach ($settings as $name => $value) {
            $manager->persist(new Setting($name, $value));
        }

        // Sample vehicles with rich metadata
        // $samples = [
        //     // brand, model, version, year, km, fuel, price, power, color, doors, gearbox, rating, cat
        //     ['Peugeot',   '208',        '1.2 PureTech 130 Allure EAT8', 2019, 42000,  'PETROL', '16900', 130, 'Noir',   5, 'Automatique', 5, 'Citadine'],
        //     ['Renault',   'Clio IV',    '1.5 dCi 90ch Business',        2017, 152000, 'DIESEL',  '9490', 90,  'Blanc',  5, 'Manuelle',    4, 'Citadine'],
        //     ['Volkswagen','Golf 7 GTD', '2.0 TDI 184ch DSG',            2016, 118000, 'DIESEL', '17990', 184, 'Gris',   5, 'Automatique', 5, 'Berline'],
        //     ['KIA',       'Sportage III','1.6 GDI 135 Active',          2014, 69000,  'PETROL', '13500', 135, 'Bleu',   5, 'Manuelle',    4, 'SUV'],
        //     ['Toyota',    'Yaris',      'Hybride 100h Dynamic',         2020, 34000,  'HYBRID', '15200', 100, 'Rouge',  5, 'Automatique', 5, 'Citadine'],
        //     ['BMW',       'Série 3',    '320d M Sport',                 2018, 88000,  'DIESEL', '22400', 190, 'Noir',   4, 'Automatique', 5, 'Berline'],
        //     ['Mercedes',  'Classe A',   '180 AMG Line',                 2019, 55000,  'PETROL', '25990', 136, 'Argent', 5, 'Automatique', 5, 'Berline'],
        //     ['Audi',      'Q3',         '2.0 TDI 150 S Line',           2018, 78000,  'DIESEL', '27500', 150, 'Blanc',  5, 'Automatique', 4, 'SUV'],
        // ];
        // $manager->flush();

        // foreach ($samples as [$brand, $model, $version, $year, $km, $fuel, $price, $power, $color, $doors, $gear, $rating, $cat]) {
        //     $v = new Vehicle();
        //     $v->setBrand($brand)
        //       ->setModel($model)
        //       ->setVersion($version)
        //       ->setYear($year)
        //       ->setMileage($km)
        //       ->setFuel(FuelType::from($fuel))
        //       ->setPrice($price)
        //       ->setPower($power)
        //       ->setColor($color)
        //       ->setDoors($doors)
        //       ->setTransmission($gear)
        //       ->setRating($rating)
        //       ->setCategory($cat)
        //       ->setPublished(true)
        //       ->setDescription("Véhicule d'exemple pour l'environnement de développement.");
        //     $v->setSlug($this->slugger->makeUniqueVehicleSlug($v->getFullName()));
        //     $manager->persist($v);
        // }

        // Reviews
        $reviewSamples = [
            ['Marie D.', 5, 'Service impeccable, véhicule conforme à l\'annonce. Je recommande.'],
            ['Julien P.', 5, 'Livraison rapide et équipe très professionnelle.'],
            ['Sofia M.',  4, 'Bonne expérience globale, quelques détails à améliorer sur la communication.'],
            ['Ahmed K.',  5, 'Achat sans stress, tout est clair du début à la fin.'],
        ];
        foreach ($reviewSamples as [$name, $rating, $comment]) {
            $r = new Review();
            $r->setCustomerName($name)->setRating($rating)->setComment($comment)
              ->setStatus(ReviewStatus::PUBLISHED)->setPublishedAt(new \DateTimeImmutable());
            $manager->persist($r);
        }

        $manager->flush();
    }
}
