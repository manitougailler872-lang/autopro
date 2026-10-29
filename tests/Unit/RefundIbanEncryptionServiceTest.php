<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\RefundIbanEncryptionService;
use PHPUnit\Framework\TestCase;

final class RefundIbanEncryptionServiceTest extends TestCase
{
    private RefundIbanEncryptionService $service;

    protected function setUp(): void
    {
        $key = bin2hex(random_bytes(32));
        $this->service = new RefundIbanEncryptionService($key);
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $plain = 'FR7630004000123000012345678';
        $cipher = $this->service->encrypt($plain);
        $this->assertNotSame($plain, $cipher);
        $this->assertSame($plain, $this->service->decrypt($cipher));
    }

    public function testDifferentNoncesEachTime(): void
    {
        $iban = 'FR7630004000123000012345678';
        $a = $this->service->encrypt($iban);
        $b = $this->service->encrypt($iban);
        $this->assertNotSame($a, $b, 'Each encryption must use a fresh nonce.');
    }

    public function testLastFour(): void
    {
        $this->assertSame('5678', $this->service->lastFour('FR76 3000 4000 1230 0001 2345 678'));
        $this->assertSame('5678', $this->service->lastFour('FR7630004000123000012345678'));
    }

    public function testNormalizeIban(): void
    {
        $this->assertSame('FR7630004000123000012345678', $this->service->normalizeIban('fr76 3000 4000 1230 0001 2345 678'));

        $this->expectException(\InvalidArgumentException::class);
        $this->service->normalizeIban('too short');
    }

    public function testDecryptGarbageFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->decrypt('not-base64-!@#$');
    }
}
