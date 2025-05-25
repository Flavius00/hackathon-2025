<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        // TODO: compute expenses total for year-month for a given user
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month
        ];

        $total = $this->expenses->sumAmounts($criteria);
        return $total;
    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        // TODO: compute totals for year-month for a given user
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month
        ];

        $totals = $this->expenses->sumAmountsByCategory($criteria);

        $result = [];
        $grandTotals = array_sum($totals);
        foreach ($totals as $category => $amount) {
            $result[$category] = [
                'amount' => $amount,
                'percentage' => $grandTotals > 0 ? ($amount / $grandTotals) * 100 : 0,
            ];
        }
        return $result;
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        // TODO: compute averages for year-month for a given user
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month
        ];

        $averages = $this->expenses->averageAmountsByCategory($criteria);
        $result = [];
        $maxAverage = $averages ? max($averages) : 0;
        foreach ($averages as $category => $average) {
            $result[$category] = [
                'average' => $average,
                'percentage' => $maxAverage > 0 ? ($average / $maxAverage) * 100 : 0,
            ];
        }
        return $result;
    }
}
