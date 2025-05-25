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
        // ...
    ];

    public function generate(User $user, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category

        return [];
    }

    public function getCategories(): array
    {
        // Load categories from .env if available, otherwise use keys from budgets
        $categoriesJson = $_ENV['CATEGORIES'] ?? '';
        if (!empty($categoriesJson)) {
            $decoded = json_decode($categoriesJson, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Fallback to category budget keys
        return array_keys($this->categoryBudgets);
    }

    public function isValidCategory(string $category): bool
    {
        $categoriesJson = $_ENV['CATEGORIES'] ?? '';
        if (!empty($categoriesJson)) {
            $decoded = json_decode($categoriesJson, true);
            if (is_array($decoded)) {
                return in_array($category, $decoded, true);
            }
        }

        // Fallback to category budget keys
        return array_key_exists($category, $this->categoryBudgets);
    }
}
