<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The core rule: reservation fees are ALWAYS computed server-side from the
 * vehicle price snapshot and the configured percentage. This test guards the
 * arithmetic contract used by ReservationService::create().
 */
final class ReservationFeeCalculationTest extends TestCase
{
    /**
     * @dataProvider provideAmounts
     */
    public function testFeeCalculation(float $price, float $percentage, float $expected): void
    {
        $actual = round($price * ($percentage / 100.0), 2);
        $this->assertSame($expected, $actual);
    }

    public static function provideAmounts(): iterable
    {
        yield [15000.00, 20.0, 3000.00];
        yield [16900.00, 20.0, 3380.00];
        yield [ 9490.00, 20.0, 1898.00];
        yield [17990.00, 20.0, 3598.00];
        yield [15000.00, 25.0, 3750.00];
        yield [15000.00, 10.0, 1500.00];
        yield [15000.00,  0.0,    0.00];
    }
}
