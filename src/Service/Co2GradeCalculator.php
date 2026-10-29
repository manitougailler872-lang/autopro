<?php
// src/Service/Co2GradeCalculator.php

namespace App\Service;

final class Co2GradeCalculator
{
    private const GRADES = [
        ['letter' => 'A', 'max' => 50],
        ['letter' => 'B', 'max' => 100],
        ['letter' => 'C', 'max' => 120],
        ['letter' => 'D', 'max' => 140],
        ['letter' => 'E', 'max' => 160],
        ['letter' => 'F', 'max' => 200],
        ['letter' => 'G', 'max' => PHP_INT_MAX],
    ];

    public function getGrade(int $co2GramsPerKm): string
    {
        foreach (self::GRADES as $grade) {
            if ($co2GramsPerKm <= $grade['max']) {
                return $grade['letter'];
            }
        }

        return 'G';
    }

    public function getGradeIndex(string $letter): int
    {
        return array_search($letter, array_column(self::GRADES, 'letter'), true);
    }
}