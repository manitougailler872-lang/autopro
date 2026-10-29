<?php

declare(strict_types=1);

namespace App\Utils;
use DateTimeImmutable;
use InvalidArgumentException;


final  class Common
{
        private const MAX_SIGNATURE_BYTES = 300 * 1024;
        /**
         * Génère un code numérique aléatoire.
         *
         * Exemple :
         * 483921
         */
        public static function generateNumericCode(int $length = 6): string
        {
            if ($length < 1) {
                throw new InvalidArgumentException('La longueur doit être supérieure à 0.');
            }

            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= random_int(0, 9);
            }
            return $code;
        }

        /**
         * Génère un code alphanumérique.
         *
         * Exemple :
         * H7K9M2XA
         */
        public static function generateAlphaNumericCode(int $length = 8): string
        {
            if ($length < 1) {
                throw new InvalidArgumentException('La longueur doit être supérieure à 0.');
            }
            $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $code = '';
            $max = strlen($characters) - 1;

            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, $max)];
            }
            return $code;
        }

        /**
         * Génération d'un IBAN français de démonstration.
         *
         * FR :
         * - 2 lettres pays
         * - 2 chiffres de contrôle
         * - 5 chiffres code banque
         * - 5 chiffres code guichet
         * - 11 caractères numéro de compte
         * - 2 chiffres clé RIB
         */
        public static function generateFrenchIban(): array
        {
                $bankCode = self::generateNumericCode(5);
                $branchCode = self::generateNumericCode(5);
                $accountNumber = self::generateNumericCode(7);
                $ribKey = self::calculateFrenchRibKey(
                    $bankCode,
                    $branchCode,
                    $accountNumber
                );
                $bban = $bankCode. $branchCode . $accountNumber . $ribKey;
                $countryCode = 'FR';
                $checkDigits = self::calculateIbanCheckDigits(
                    $countryCode,
                    $bban
                );
                $iban = $countryCode . $checkDigits . $bban;
                return [
                    'country' => 'FR',
                    'iban' => $iban,
                    'iban_formatted' => self::formatIban($iban),
                    'bank_code' => $bankCode,
                    'branch_code' => $branchCode,
                    'account_number' => $accountNumber,
                    'rib_key' => $ribKey,
                    'bic' => self::generateFakeBic('FR'),
                ];
        }

    /**
     * Vérifie la validité mathématique d'un IBAN.
     */
    public static function validateIban(string $iban): bool
    {
            $iban = strtoupper(
                preg_replace('/\s+/', '', $iban)
            );
            if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
                return false;
            }
            if (strlen($iban) < 15 || strlen($iban) > 34) {
                return false;
            }
            // Pour la France : 27 caractères.
            if (
                str_starts_with($iban, 'FR')
                && strlen($iban) !== 27
            ) {
                return false;
            }
            // On déplace les 4 premiers caractères à la fin.
            $rearranged = substr($iban, 4)
                . substr($iban, 0, 4);
            $numeric = self::ibanToNumeric($rearranged);
            
            return self::mod97($numeric) === 1;
    }

    /**
     * Formate un IBAN :
     *
     * FR76 1234 5678 9012 3456 7890 123
     */
    public static function formatIban(string $iban): string
    {
        $iban = strtoupper(
            preg_replace('/\s+/', '', $iban)
        );

        return trim(
            chunk_split($iban, 4, ' ')
        );
    }

    /**
     * Génère un BIC fictif.
     *
     * Exemple :
     * NXTRFRP1XXX
     */
    public static function generateFakeBic(
        string $country = 'FR'
    ): string {

        $bank = 'NXTR';
        $country = strtoupper($country);

        $location =
            chr(random_int(65, 90))
            . chr(random_int(65, 90));

        return $bank
            . $country
            . $location
            . 'XXX';
    }

    /**
     * Génère des informations de carte VISA uniquement
     * pour environnement de test/démonstration.
     *
     * Le numéro utilisé est un numéro de test connu.
     */
    public static function generateVisaTestCard(): array
    {
        $expireDate = (new DateTimeImmutable())
            ->modify('+3 years');
        $number = self::generateNumericCode(12);
        return [
            'brand' => 'VISA',
            // Numéro de carte TEST.
            'number' => '4242'.$number,
            'number_masked' => '4242 **** **** '. substr($number, -4),
            'cvv' => (string) random_int(100, 999),
            'expire_month' => $expireDate->format('m'),
            'expire_year' => $expireDate->format('Y'),
            'expire' => $expireDate->format('m/y'),
            'holder' => 'NexoBanq',
            'test_mode' => true,
        ];
    }

    /**
     * Vérifie un numéro de carte avec l'algorithme de Luhn.
     */
    public static function validateCardNumber(
        string $number
    ): bool {
        
        $number = preg_replace('/\D/', '', $number);
        if (strlen($number) < 12) {
            return false;
        }

        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];
            if ($alternate) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $alternate = !$alternate;
        }

        return $sum % 10 === 0;
    }

    /**
     * Masque un numéro de carte bancaire.
     */
    public static function maskCardNumber(
        string $number
    ): string {
        $number = preg_replace('/\D/', '', $number);
        if (strlen($number) < 8) {
            return $number;
        }
        return substr($number, 0, 4)
            . ' **** **** '
            . substr($number, -4);
    }

    /**
     * Génère une référence NexoTrade.
     *
     * Exemple :
     * NXT-2026-847293
     */
    public static function generateReference(
        string $prefix = 'NXT'
    ): string {
        return strtoupper($prefix)
            . '-'
            . date('Y')
            . '-'
            . self::generateNumericCode(6);
    }

    private static function calculateFrenchRibKey(
        string $bankCode,
        string $branchCode,
        string $accountNumber
    ): string {
        $value =
            (int) $bankCode * 89
            + (int) $branchCode * 15
            + (int) $accountNumber * 3;

        $key = 97 - ($value % 97);

        return str_pad(
            (string) $key,
            2,
            '0',
            STR_PAD_LEFT
        );
    }

    private static function calculateIbanCheckDigits(
        string $country,
        string $bban
    ): string {
        $countryNumeric = self::ibanToNumeric(
            strtoupper($country)
        );

        $numeric =
            $bban
            . $countryNumeric
            . '00';

        $remainder = self::mod97($numeric);
        $checkDigits = 98 - $remainder;
        return str_pad(
            (string) $checkDigits,
            2,
            '0',
            STR_PAD_LEFT
        );
    }

    private static function ibanToNumeric(
        string $value
    ): string {
        $result = '';
        foreach (str_split($value) as $char) {
            if (ctype_alpha($char)) {
                $result .= ord($char) - 55;
            } else {
                $result .= $char;
            }
        }
        return $result;
    }

    /**
     * Calcul MOD 97 sans utiliser de très grand entier.
     */
    private static function mod97(
        string $numeric
    ): int {
        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder =
                ($remainder * 10 + (int) $digit)
                % 97;
        }
        return $remainder;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<string, mixed>|null
     */
    public static  function pickById(array $items, string $id): ?array
    {
        foreach ($items as $item) {
            if ((string) $item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    public static  function formatDate(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return false !== $date ? $date->format('d/m/Y') : $value;
    }

    /**
     *  ********************METHODES PRIV*******************
     * */

     /**
     * Série de démonstration pour la courbe d'évolution.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public static  function buildBalanceSeries(int $days): array
    {
        $series = [];
        $value = 118000.0;
        $start = new \DateTimeImmutable('2025-05-16');
        $step = max(1, (int) round($days / 30));

        for ($i = $days; $i >= 0; $i -= $step) {
            // Progression légèrement bruitée, purement cosmétique.
            $value += random_int(-2200, 3400);
            $value = max(90000.0, $value);

            $series[] = [
                'label' => $start->modify("-{$i} days")->format('j M'),
                'value' => round($value, 2),
            ];
        }

        // On force le dernier point à correspondre au solde affiché.
        if ([] !== $series) {
            $series[array_key_last($series)]['value'] = 156780.50;
        }

        return $series;
    }

    
    /**
     * Pays proposés à l'étape 1.
     *
     * @return array<int, array<string, string>>
     */
    public static  function countries(): array
    {
        return [
            ['code' => 'FR', 'name' => 'France', 'flag' => '🇫🇷'],
            ['code' => 'BE', 'name' => 'Belgique', 'flag' => '🇧🇪'],
            ['code' => 'DE', 'name' => 'Allemagne', 'flag' => '🇩🇪'],
            ['code' => 'ES', 'name' => 'Espagne', 'flag' => '🇪🇸'],
            ['code' => 'IT', 'name' => 'Italie', 'flag' => '🇮🇹']
        ];
    }

        /**
     * Banques proposées à l'étape 2, filtrées par pays côté navigateur.
     *
     * @return array<int, array<string, string>>
     */
    public static  function banks(): array
    {
        return [
                ['country' => 'FR', 'name' => 'BNP Paribas',        'short' => 'BNP',  'bic' => 'BNPAFRPP', 'color' => '#00915A'],
                ['country' => 'FR', 'name' => 'Crédit Agricole',    'short' => 'CA',   'bic' => 'AGRIFRPP', 'color' => '#00915A'],
                ['country' => 'FR', 'name' => 'Société Générale',   'short' => 'SG',   'bic' => 'SOGEFRPP', 'color' => '#E4002B'],
                ['country' => 'FR', 'name' => 'La Banque Postale',  'short' => 'LBP',  'bic' => 'PSSTFRPP', 'color' => '#003B7E'],
                ['country' => 'FR', 'name' => 'Crédit Mutuel',      'short' => 'CM',   'bic' => 'CMCIFRPP', 'color' => '#005DA9'],
                ['country' => 'BE', 'name' => 'KBC Bank',           'short' => 'KBC',  'bic' => 'KREDBEBB', 'color' => '#004E9A'],
                ['country' => 'BE', 'name' => 'Belfius',            'short' => 'BFS',  'bic' => 'GKCCBEBB', 'color' => '#C8102E'],
                ['country' => 'DE', 'name' => 'Deutsche Bank',      'short' => 'DB',   'bic' => 'DEUTDEFF', 'color' => '#0018A8'],
                ['country' => 'DE', 'name' => 'Commerzbank',        'short' => 'CBK',  'bic' => 'COBADEFF', 'color' => '#FFD700'],
                ['country' => 'ES', 'name' => 'Banco Santander',    'short' => 'SAN',  'bic' => 'BSCHESMM', 'color' => '#EC0000'],
                ['country' => 'ES', 'name' => 'BBVA',               'short' => 'BBVA', 'bic' => 'BBVAESMM', 'color' => '#004481'],
                ['country' => 'IT', 'name' => 'UniCredit',          'short' => 'UC',   'bic' => 'UNCRITMM', 'color' => '#E2001A'],
                ['country' => 'IT', 'name' => 'Intesa Sanpaolo',    'short' => 'ISP',  'bic' => 'BCITITMM', 'color' => '#008C4A'],
        ];
    }

        /**
     * @return array<int, array<string, mixed>>
     */
    public static  function buildBeneficiaryDataset(): array
    {
        return [
            [
                'id' => 1,
                'bic' => 'BNPAFRPP',
                'country' => 'France',
                'name' => 'John Doe',
                'iban' => 'FR85 3000 4008 2800 0100 1234 567',
                'bank' => 'BNP Paribas',
                'verified' => true,
            ],
            [
                'id' => 2,
                'bic' => 'AGRIFRPP',
                'country' => 'France',
                'name' => 'Marie Smith',
                'iban' => 'FR14 2004 1010 0505 0001 3M02 606',
                'bank' => 'Crédit Agricole',
                'verified' => true,
            ],
            [
                'id' => 3,
                'bic' => 'SOGEFRPP',
                'country' => 'France',
                'name' => 'Lucas Bernard',
                'iban' => 'FR82 1027 8060 3400 0201 2340 123',
                'bank' => 'Société Générale',
                'verified' => true,
            ],
            [
                'id' => 4,
                'bic' => 'BNPAFRPP',
                'country' => 'France',
                'name' => 'Alexandre Dubois',
                'iban' => 'FR15 3000 6000 0112 3456 7890 123',
                'bank' => 'BNP Paribas',
                'verified' => true,
            ],
            [
                'id' => 5,
                'bic' => 'PSSTFRPP',
                'country' => 'France',
                'name' => 'Sophie Cartier',
                'iban' => 'FR59 1695 8000 0102 3456 7890 145',
                'bank' => 'La Banque Postale',
                'verified' => true,
            ],
            [
                'id' => 6,
                'bic' => 'BNPAFRPP',
                'country' => 'France',
                'name' => 'Entreprise Tech Solutions',
                'iban' => 'FR64 3000 2070 1200 0007 8945 612',
                'bank' => 'BNP Paribas',
                'verified' => true,
            ],
        ];
    }

        /**
     * Construit le tableau d'amortissement d'un prêt.
     *
     * ATTENTION : cette génération est ici une commodité de démonstration.
     * En production, l'échéancier est une pièce CONTRACTUELLE : il doit être
     * lu depuis ce qui a été édité et signé, jamais recalculé à l'affichage.
     * Un recalcul, même avec la bonne formule, dérive de quelques centimes
     * selon la règle d'arrondi retenue — et le client verrait alors des
     * montants différents de ceux de son contrat.
     *
     * Le calcul ne doit en aucun cas être fait côté navigateur.
     *
     * @return array<int, array<string, mixed>>
     */
    public static  function buildAmortization(float $amount, int $months, float $monthlyPayment, float $annualRate, \DateTimeImmutable $firstPaymentAt): array
    {
        $monthlyRate = $annualRate / 100 / 12;
        $balance = $amount;
        $rows = [];
        $today = new \DateTimeImmutable('today');

        for ($n = 1; $n <= $months; ++$n) {
            $interest = round($balance * $monthlyRate, 2);
            $principal = round($monthlyPayment - $interest, 2);

            // Dernière échéance : on solde exactement, les arrondis successifs
            // laissant sinon un résidu de quelques centimes.
            if ($n === $months || $principal > $balance) {
                $principal = $balance;
            }

            $balance = round($balance - $principal, 2);
            $dueDate = $firstPaymentAt->modify('+'.($n - 1).' months');

            $rows[] = [
                'number' => $n,
                'dueDate' => $dueDate,
                'payment' => round($principal + $interest, 2),
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0.0, $balance),
                'paid' => $dueDate < $today,
            ];

            if ($balance <= 0) {
                break;
            }
        }

        return $rows;
    }

    public static function generateColor(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

        /**
     * Valide une signature manuscrite transmise en data URL.
     *
     * Le préfixe `data:image/png;base64,` est déclaré par le client : on
     * vérifie donc que le contenu décodé est bien un PNG, et non un fichier
     * arbitraire déguisé.
     */
    private function isAcceptableSignatureImage(string $dataUrl): bool
    {
        if (1 !== preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $matches)) {
            return false;
        }
        $binary = base64_decode($matches[1], true);
        if (false === $binary || \strlen($binary) > self::MAX_SIGNATURE_BYTES) {
            return false;
        }
        // Signature de fichier PNG (\x89PNG\r\n\x1a\n).
        return str_starts_with($binary, "\x89PNG\r\n\x1a\n");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static  function buildLoanDataset(): array
    {
        return [
            [
                'id' => 1,
                'type' => 'mortgage',
                'label' => 'Prêt hypothécaire',
                'reference' => '#NXP-2026-0142',
                'submittedAt' => new \DateTimeImmutable('2026-01-03'),
                'amount' => 300000.00,
                'currency' => 'USD',
                'durationMonths' => 180,
                // La maquette affichait 1 501,87 $ : incompatible avec 300 000 $
                // sur 180 mois (1 501,87 × 180 = 270 336,60, soit moins que le
                // capital emprunté). Mensualité recalculée au taux contractuel.
                'monthlyPayment' => 1951.32,
                'rate' => '2,15',
                'totalCost' => 51237.60,
                'firstPaymentAt' => new \DateTimeImmutable('2026-02-05'),
                'remaining' => 284120.00,
                'status' => 'active',
                'refusalReason' => null,
            ],
            [
                'id' => 2,
                'type' => 'auto',
                'label' => 'Prêt auto',
                'reference' => '#NXP-2026-0131',
                'submittedAt' => new \DateTimeImmutable('2025-12-18'),
                'amount' => 28000.00,
                'currency' => 'USD',
                'durationMonths' => 60,
                'monthlyPayment' => 518.19,
                // Taux déduit du couple (montant, durée, mensualité) de la maquette.
                'rate' => '4,20',
                'totalCost' => 3091.40,
                'firstPaymentAt' => new \DateTimeImmutable('2026-01-15'),
                'remaining' => 24870.00,
                'status' => 'active',
                'refusalReason' => null,
            ],
            [
                'id' => 3,
                'type' => 'personal',
                'label' => 'Prêt personnel',
                'reference' => '#NXP-2026-0117',
                'submittedAt' => new \DateTimeImmutable('2025-12-02'),
                'amount' => 8000.00,
                'currency' => 'USD',
                'durationMonths' => 36,
                'monthlyPayment' => 242.65,
                'rate' => '5,80',
                'totalCost' => 735.40,
                // Échéancier prévisionnel : le contrat attend la signature.
                'firstPaymentAt' => new \DateTimeImmutable('2026-03-05'),
                'remaining' => null,
                // Contrat édité, en attente de signature du client.
                'status' => 'to_sign',
                'refusalReason' => null,
            ],
            [
                'id' => 4,
                'type' => 'other',
                'label' => 'Prêt personnel',
                'reference' => '#NXP-2026-0098',
                'submittedAt' => new \DateTimeImmutable('2025-11-14'),
                'amount' => 15000.00,
                'currency' => 'USD',
                'durationMonths' => 24,
                'monthlyPayment' => null,
                'rate' => '—',
                'totalCost' => null,
                'firstPaymentAt' => null,
                'remaining' => null,
                'status' => 'refused',
                'refusalReason' => "Taux d'endettement supérieur au seuil autorisé au moment de l'étude.",
            ],
        ];
    }

        /**
     * @return array<int, array<string, mixed>>
     */
    public static function buildTransactionDataset(): array
    {
        $rows = [
            ['Virement reçu',   'Société Générale', 'Logement',         8500.00,  'REF12345', '2025-05-16 14:32'],
            ['Paiement carte',  'Airbnb Ireland',   'Courses',          -320.00,  'REF12346', '2025-05-16 11:05'],
            ['Achat en ligne',  'Amazon EU',        'Courses',          -159.99,  'REF12347', '2025-05-15 09:18'],
            ['Virement émis',   'John Doe',         'Transport',        -1200.00, 'REF12348', '2025-05-14 16:45'],
            ['Paiement facture','EDF Energie',      'Abonnements',      -89.60,   'REF12349', '2025-05-14 10:22'],
            ['Intérêts perçus', 'Livret A+',        'Loisirs & autres', 12.45,    'REF12350', '2025-05-13 18:30'],
            ['Restaurant',      'Le Bistrot Paris', 'Loisirs & autres', -45.80,   'REF12351', '2025-05-13 12:14'],
            ['Virement reçu',   'Client Dupont',    'Transport',        3200.00,  'REF12352', '2025-05-12 15:00'],
            ['Abonnement',      'Netflix',          'Abonnements',      -17.99,   'REF12353', '2025-05-11 08:05'],
            ['Paiement carte',  'Intermarché',      'Courses',          -76.40,   'REF12354', '2025-05-10 19:22'],
            ['Salaire',         'NAKOD SARL',       'Logement',         4200.00,  'REF12355', '2025-05-02 07:00'],
            ['Loyer',          'SCI Les Tilleuls',  'Logement',         -1150.00, 'REF12356', '2025-05-02 09:30'],
        ];

        $dataset = [];

        foreach ($rows as [$label, $counterparty, $category, $amount, $reference, $date]) {
            $dataset[] = [
                'label' => $label,
                'counterparty' => $counterparty,
                'category' => $category,
                'amount' => $amount,
                'reference' => $reference,
                'status' => 'Payé',
                'date' => new \DateTimeImmutable($date),
            ];
        }

        // Le compteur de la maquette annonce 42 opérations : on complète le jeu
        // pour que la pagination ait un comportement réaliste à parcourir.
        $filler = \count($dataset);

        for ($i = $filler; $i < 42; ++$i) {
            $source = $dataset[$i % $filler];
            $source['reference'] = 'REF'.(12345 + $i);
            $source['date'] = $source['date']->modify('-'.($i - $filler + 1).' days');
            $dataset[] = $source;
        }

        return $dataset;
    }
}



