<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use App\Domain\Service\AlertGenerator;
use App\Domain\Entity\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        $userId = $_SESSION['user_id'] ?? null; // TODO: obtain logged-in user ID from session
        
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);
        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('n'));


        $user = new User($userId, '', '', new \DateTimeImmutable());

        $expenses = $this->expenseService->list($user, $year, $month, $page, $pageSize);

        $this->logger->info('Fetched expenses for user', [
            $expenses
        ]);

        
        $availableYears = $this->expenseService->getAvailableYears($user);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses['expenses'],
            'pagination' => $expenses['pagination'],
            'currentYear' => $year,
            'currentMonth' => $month,
            'availableYears' => $availableYears,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        $alertGenerator = new AlertGenerator();
        $categories = $alertGenerator->getCategories();
        $this->logger->info('Available categories for expenses', ['categories' => $categories]);

        return $this->render($response, 'expenses/create.twig', ['categories' => $categories]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $userId = $_SESSION['user_id'] ?? null; // TODO: obtain logged-in user ID from session

        $params = $request->getParsedBody();
        $amount = ($params['amount'] ?? '');
        $description = ($params['description'] ?? '');
        $date = ($params['date'] ?? '');
        $category = ($params['category'] ?? '');

        try{

            if(empty($date)){
                throw new \InvalidArgumentException('Date is required');
            }

            $expenseDate = new \DateTimeImmutable($date);
            $today = new \DateTimeImmutable();
            if ($expenseDate > $today) {
                throw new \InvalidArgumentException('Expense date cannot be in the future');
            }

            if(empty($category)){
                throw new \InvalidArgumentException('Category is required');
            }

            if (empty($amount) || !is_numeric($amount) || (float)$amount <= 0) {
                throw new \InvalidArgumentException('Amount must be greater than 0.');
            }

            if (empty($description)) {
                throw new \InvalidArgumentException('Description cannot be empty.');
            }

            $user = new User($userId, '', '', new \DateTimeImmutable());
            $this->expenseService->create(
                $user,
                (float)$amount,
                $description,
                $expenseDate,
                $category
            );

            return $response->withHeader('Location', '/expenses')->withStatus(302);
        }
        catch (\InvalidArgumentException $e) {
            $this->logger->error('Failed to create expense', ['error' => $e->getMessage()]);

            // Rerender the create page with error messages
            return $this->render($response, 'expenses/create.twig', [
                'categories' => (new AlertGenerator())->getCategories(),
                'errors' => [$e->getMessage()],
                'amount' => $amount,
                'description' => $description,
                'date' => $date,
                'category' => $category,
            ]);
        }
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        $expenseId = (int)$routeParams['id'];
        $expense = $this->expenseService->getExpenseById($expenseId);

        if (!$expense) {
            $this->logger->error('Expense not found', ['expenseId' => $expenseId]);
            return $response->withStatus(404)->write('Expense not found');
        }

        $alertGeneratorq = new AlertGenerator();
        $categories = $alertGeneratorq->getCategories();

        return $this->render($response, 'expenses/edit.twig', ['expense' => $expense, 'categories' => $categories]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success
        $expenseId = (int)$routeParams['id'];
        $expense = $this->expenseService->getExpenseById($expenseId);

        if (!$expense) {
            $this->logger->error('Expense not found', ['expenseId' => $expenseId]);
            return $response->withStatus(404)->write('Expense not found');
        }

        $data = $request->getParsedBody();
        $amount = ($data['amount'] ?? '');
        $description = ($data['description'] ?? '');
        $date = ($data['date'] ?? '');
        $category = ($data['category'] ?? '');

        try{
            if(empty($date)){
                throw new \InvalidArgumentException('Date is required');
            }

            $expenseDate = new \DateTimeImmutable($date);
            $today = new \DateTimeImmutable();
            if ($expenseDate > $today) {
                throw new \InvalidArgumentException('Expense date cannot be in the future');
            }

            if(empty($category)){
                throw new \InvalidArgumentException('Category is required');
            }

            if (empty($amount) || !is_numeric($amount) || (float)$amount <= 0) {
                throw new \InvalidArgumentException('Amount must be greater than 0.');
            }

            if (empty($description)) {
                throw new \InvalidArgumentException('Description cannot be empty.');
            }

            $user = new User($userId, '', '', new \DateTimeImmutable());
            $this->expenseService->update(
                $expense,
                (float)$amount,
                $description,
                $expenseDate,
                $category
            );
            return $response->withHeader('Location', '/expenses')->withStatus(302);

        }
        catch (\InvalidArgumentException $e) {
            $this->logger->error('Failed to update expense', ['error' => $e->getMessage()]);

            // Rerender the edit page with error messages
            return $this->render($response, 'expenses/edit.twig', [
                'expense' => $expense,
                'categories' => (new AlertGenerator())->getCategories(),
                'errors' => [$e->getMessage()],
                'amount' => $amount,
                'description' => $description,
                'date' => $date,
                'category' => $category,
            ]);
        }
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        return $response;
    }
}
