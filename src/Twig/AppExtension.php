<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Reservation;
use App\Repository\ReviewRepository;
use App\Service\SettingService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly ReviewRepository $reviews,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('money_eur', $this->formatMoney(...)),
            new TwigFilter('mileage', $this->formatMileage(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_setting', fn (string $name, ?string $default = null) => $this->settings->get($name, $default)),
            new TwigFunction('published_reviews', fn (int $limit = 6) => $this->reviews->findPublished($limit)),
            new TwigFunction('reservation_status_label', fn (Reservation $r) => $r->getStatus()->label()),
            new TwigFunction('mask_vin', [$this, 'maskVin']),
        ];
    }

    public function maskVin(
        ?string $vin,
        int $start = 4,
        int $end = 5
    ): string {
        if (!$vin) {
            return '';
        }

        $length = mb_strlen($vin);

        if ($length <= ($start + $end)) {
            return str_repeat('*', $length);
        }

        return mb_substr($vin, 0, $start)
            . str_repeat('*', $length - $start - $end)
            . mb_substr($vin, -$end);
    }

    public function formatMoney(mixed $amount): string
    {
        if(empty($amount)){
            return '0.00';
        }
        return number_format((float) $amount, 0, ',', ' ').' €';
    }

    public function formatMileage(mixed $km): string
    {
        if(empty($km)){
            return '0';
        }
        return number_format($km, 0, ',', ' ').' km';
    }
}
