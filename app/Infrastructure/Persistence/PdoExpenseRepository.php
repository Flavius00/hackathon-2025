<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.
        if ($expense->id === null) {
            $query = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) 
                      VALUES (:user_id, :date, :category, :amount_cents, :description)';
            $statement = $this->pdo->prepare($query);
            $statement->execute([
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d H:i:s'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);
            
            $expense->id = (int)$this->pdo->lastInsertId();
        } else {
            $query = 'UPDATE expenses SET user_id = :user_id, date = :date, category = :category, 
                      amount_cents = :amount_cents, description = :description WHERE id = :id';
            $statement = $this->pdo->prepare($query);
            $statement->execute([
                'id' => $expense->id,
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d H:i:s'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        
        $conditions = [];
        $params = [];

        if (isset($criteria['user_id'])) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['year']) && isset($criteria['month'])) {
            $conditions[] ='strftime("%Y", date) = :year AND strftime("%m", date) = :month';
            $params['year'] = $criteria['year'];
            $params['month'] = $criteria['month'];
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $query = "SELECT * FROM expenses {$whereClause} ORDER BY date DESC LIMIT :limit OFFSET :from";
        $statement = $this->pdo->prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue(":{$key}", $value);
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':from', $from, PDO::PARAM_INT);

        $statement->execute();

        $data = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (false === $data) {
            return [];
        }

        $expenses = [];
        foreach ($data as $row) {
            $expenses[] = $this->createExpenseFromData($row);
        }
        return $expenses;
    }


    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.
        $conditions = [];
        $params = [];

        if (isset($criteria['user_id'])) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['year']) && isset($criteria['month'])) {
            $conditions[] = 'strftime("%Y", date) = :year AND strftime("%m", date) = :month';
            $params['year'] = $criteria['year'];
            $params['month'] = $criteria['month'];
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $query = "SELECT COUNT(*) FROM expenses {$whereClause}";
        $statement = $this->pdo->prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue(":{$key}", $value);
        }

        $statement->execute();
        $count = (int)$statement->fetchColumn();

        return $count;
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.
        $query = 'SELECT DISTINCT strftime("%Y", date) AS year FROM expenses WHERE user_id = :user_id ORDER BY year DESC';

        $statement = $this->pdo->prepare($query);   
        $statement->execute(['user_id' => $user->id]);

        $years = [];
        while ($row = $statement->fetch()) {
            $years[] = (int)$row['year'];
        }

        $currentYear = (int)(new DateTimeImmutable())->format('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        return $years;
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        return [];
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        return [];
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        return 0;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }
}
