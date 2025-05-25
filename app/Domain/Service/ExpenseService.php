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
        return [];
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
        return [];
    }

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
        $tempStream = fopen('php://temp', 'w+b');
        if ($tempStream === false) {
            throw new \RuntimeException('Failed to open temporary stream for CSV import');
        }

        $uploadedStream = $csvFile->getStream();
        $uploadedStream->rewind();

        while(!$uploadedStream->eof()) {
           $chunk = $uploadedStream->read(8192);
           fwrite($tempStream, $chunk);
        }

        rewind($tempStream);

        $importedRows = 0;
        $skippedRows = [];
        $processedRows = [];

        $validCategories = $this->getCategories();

        while(($row = fgetcsv($tempStream)) !== false) {
           if(count($row) < 4) {
                continue;
           }

           [$dateStr,  $amountStr, $description , $category] = $row;

           if(!in_array($category, $validCategories, true)) {
                $this->logger->warning('Invalid category in CSV row', ['category' => $category]);
                $skippedRows[] = [
                    'row' => $row,
                    'reason' => 'Invalid category: ' . $category
                ];
                continue;
           }

           $rowKey = $dateStr . '|' . $category . '|' . $amountStr . '|' . $description;
           if(in_array($rowKey, $processedRows, true)) {
                $skippedRows[] = [
                    'row' => $row,
                    'reason' => 'Duplicate row'
                ];
                continue;
           }

           try{
                $date = new DateTimeImmutable($dateStr);
                $amount = (float)$amountStr;

                if($amount <= 0) {
                    throw new \InvalidArgumentException('Amount must be greater than zero');
                }

                if(empty(trim($description))) {
                    throw new \InvalidArgumentException('Description cannot be empty');
                }

                $this->create($user, $amount, trim($description), $date, $category);

                $processedRows[] = $rowKey;
                $importedRows++;
           }catch(\Exception $e) {
                $skippedRows[] = [
                    'row' => $row,
                    'reason' => 'Error: ' . $e->getMessage()
                ];
           }
        }
        fclose($tempStream);

        if(!empty($skippedRows)) {
            $this->logger->warning('Skipped rows during CSV import', [
                'skippedRows' => $skippedRows,
                'importedCount' => $importedRows
            ]);
        }

        $this->logger->info('CSV import completed successfully', ['importedCount' => $importedRows]);
        return $importedRows; // number of imported rows
    }
}
