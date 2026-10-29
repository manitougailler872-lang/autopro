<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class SettingService
{
    public const RESERVATION_PERCENTAGE = 'reservation_percentage';
    public const COMPANY_NAME           = 'company_name';
    public const COMPANY_ADDRESS        = 'company_address';
    public const COMPANY_EMAIL          = 'company_email';
    public const COMPANY_CITY          = 'company_city';
    public const COMPANY_CP          = 'company_cp';
    public const COMPANY_SITE          = 'company_site';
    public const COMPANY_PHONE          = 'company_phone';
    public const COMPANY_REGISTRATION   = 'company_registration';
    public const LOGO_FILENAME          = 'logo';
    public const BANK_HOLDER            = 'bank_holder';
    public const BANK_NAME              = 'bank_name';
    public const BANK_IBAN              = 'bank_iban';
    public const BANK_BIC               = 'bank_bic';
    public const BANK_REFERENCE_PREFIX  = 'bank_reference';
    public const PAYMENT_MESSAGE        = 'payment_message';
    public const PAYMENT_MESSAGE_V1     = 'payment_message_v1';
    public const TIKTOK_URL             = 'tiktok_url';
    public const TIKTOK_TEXT            = 'tiktok_text';
    public const LEGAL_NOTICE           = 'legal_notice';
    public const TERMS                  = 'terms';
    public const PRIVACY_POLICY         = 'privacy_policy';
    public const COMPANY_LOGO           = 'company_logo';
    public const OPENING_HOUR           = 'open_hour';
    public const COMPANY_SIGN   = 'signature';
    public const CONTRAT_SIGNATURE  = 'signature';
    public const ACTIVE_SMS  = 'active_sms';
    public const EXPEDITEUR_PAR_DEFAULT  = 'expediteur_default';

    private const DEFAULTS = [
        self::RESERVATION_PERCENTAGE => '20',
        self::COMPANY_NAME           => 'AUTOPRO AUTOMOBIL',
        self::COMPANY_CITY           => '',
        self::COMPANY_CP           => '',
        self::COMPANY_ADDRESS        => '15 Rue des Lilas, 75000 Paris, France',
        self::COMPANY_EMAIL          => 'garage@autopro-reservation.fr',
        self::COMPANY_PHONE          => '01 23 45 67 89',
        self::COMPANY_REGISTRATION   => '',
        self::LOGO_FILENAME          => '',
        self::BANK_HOLDER            => 'AUTOPRO AUTOMOBIL SAS',
        self::BANK_NAME              => 'Banque exemple',
        self::COMPANY_SITE              => 'https://autopro-reservation.com',
        self::BANK_IBAN              => 'FR76 0000 0000 0000 0000 0000 000',
        self::BANK_BIC               => 'AGRIFRPP',
        self::BANK_REFERENCE_PREFIX  => 'LC',
        self::PAYMENT_MESSAGE        => "Merci d'effectuer le virement en mentionnant votre numéro de réservation en référence. Envoyez ensuite la preuve de virement pour finaliser votre commande.",
        self::TIKTOK_URL             => 'https://www.tiktok.com/@luxecar',
        self::TIKTOK_TEXT            => 'Suivez-nous sur TikTok',
        self::LEGAL_NOTICE           => '',
        self::TERMS                  => '',
        self::ACTIVE_SMS                  => false,
        self::CONTRAT_SIGNATURE                  => '',
        self::EXPEDITEUR_PAR_DEFAULT                  => 'AutoPro',
        self::PRIVACY_POLICY         => '',
        self::COMPANY_LOGO         => '',
        self::COMPANY_SIGN       => '',
        self::OPENING_HOUR         => 'Lundi - Vendredi : 9h00 - 12h00 / 14h00 - 18h30
                        Samedi : 9h00 - 12h00
                        Dimanche : Fermé',
    ];

    /** @var array<string,?string>|null */
    private ?array $cache = null;

    public function __construct(
        private readonly SettingRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function get(string $name, ?string $default = null): ?string
    {
        $this->hydrate();
        return $this->cache[$name] ?? $default ?? self::DEFAULTS[$name] ?? null;
    }

    public function getInt(string $name, int $default = 0): int
    {
        $v = $this->get($name);
        return $v === null ? $default : (int) $v;
    }

    public function getFloat(string $name, float $default = 0.0): float
    {
        $v = $this->get($name);
        return $v === null ? $default : (float) $v;
    }

    public function set(string $name, ?string $value): void
    {
        $setting = $this->repository->find($name);
        if ($setting === null) {
            $setting = new Setting($name, $value);
            $this->em->persist($setting);
        } else {
            $setting->setValue($value);
        }
        $this->em->flush();
        $this->cache = null;
    }

    /** @param array<string,?string> $values */
    public function setMany(array $values): void
    {
        foreach ($values as $name => $value) {
            $setting = $this->repository->find($name);
            if ($setting === null) {
                $setting = new Setting($name, $value);
                $this->em->persist($setting);
            } else {
                $setting->setValue($value);
            }
        }
        $this->em->flush();
        $this->cache = null;
    }

    /** @return array<string,?string> */
    public function all(): array
    {
        $this->hydrate();
        return array_merge(self::DEFAULTS, $this->cache ?? []);
    }

    private function hydrate(): void
    {
        if ($this->cache === null) {
            $this->cache = $this->repository->findAllAsMap();
        }
    }
}
