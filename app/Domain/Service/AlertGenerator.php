<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;

class AlertGenerator
{
    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.

    private array $categoryBudgets = [
        'Groceries' => 300.00,
        'Utilities' => 200.00,
        'Transport' => 500.00,
        'Entertainment' => 150.00,
        'Healthcare' => 100.00,
        'Housing' => 800.00,
        'Other' => 100.00
    ];

    public function __construct(
        private readonly ExpenseRepositoryInterface $expenseRepository
    ) {}

    public function generate(User $user, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category
        $alerts = [];

        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month
        ];
        $expenses = $this->expenseRepository->sumAmountsByCategory($criteria);

        foreach($this->categoryBudgets as $category => $budget) {
            $spentCents = $expenses[$category] ?? 0;
            $spentEuros = (float)$spentCents / 100;

            if ($spentEuros > $budget) {
                $amountOver = $spentEuros - $budget;
                $alerts[] = [
                    'type' => 'overspending',
                    'category' => $category,
                    'message' => sprintf('⚠ %s budget exceeded by %.2f €', $category, $amountOver),
                    'amount' => $amountOver,
                    'severity' => $this->calculateSeverity($amountOver, $budget)
                ];
            }
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'message' => '✅ Looking good! You\'re within budget for this month.',
                'severity' => 'low'
            ];
        }

        return $alerts;
    }

    private function calculateSeverity(float $amountOver, float $budget): string
    {
       $percentageOver = ($amountOver / $budget) * 100;
        
        if ($percentageOver > 50) {
            return 'high';
        } elseif ($percentageOver > 20) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
