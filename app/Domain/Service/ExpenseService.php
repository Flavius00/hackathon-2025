<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses
        $criteria = [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month
        ];

        $offset = ($pageNumber - 1) * $pageSize;
        $expenses = $this->expenses->findBy($criteria, $offset, $pageSize);
        $totalCount = $this->expenses->countBy($criteria);

        $this->logger->info('Fetched expenses for user', $expenses);
        $this->logger->info('Total expenses count', ['count' => $totalCount]);

        $totalPages = (int)ceil($totalCount / $pageSize);
        $hasNextPage = $pageNumber < $totalPages;
        $hasPreviousPage = $pageNumber > 1;

        return [
            'expenses' => $expenses,
            'pagination' => [
                'currentPage' => $pageNumber,
                'totalPages' => $totalPages,
                'hasNextPage' => $hasNextPage,
                'hasPreviousPage' => $hasPreviousPage,
                'totalCount' => $totalCount,
                'pageSize' => $pageSize,
            ],
        ];
    }

    public function getAvailableYears(User $user): array
    {
        return $this->expenses->listExpenditureYears($user);
    }

    public function getExpenseById(int $id): ?Expense
    {
        return $this->expenses->find($id);
    }

    public function delete(Expense $expense): void
    {
        $this->expenses->delete($expense->id);
        $this->logger->info('Deleted expense', ['id' => $expense->id]);
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist

        // TODO: here is a code sample to start with
        $amountCents = $amount * 100;
        $expense = new Expense(null, $user->id, $date, $category, (int)$amountCents, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
        $amountCents = $amount * 100;
        $expense->amountCents = (int)$amountCents;
        $expense->description = $description;
        $expense->date = $date;
        $expense->category = $category;

        $this->expenses->save($expense);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
