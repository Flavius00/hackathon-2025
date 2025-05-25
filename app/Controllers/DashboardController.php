<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as LoggerInterface;
use App\Domain\Service\MonthlySummaryService as MonthlySummaryService;
use App\Domain\Service\AlertGenerator as AlertGenerator;
use App\Domain\Repository\UserRepositoryInterface as UserRepositoryInterface;
use App\Domain\Service\ExpenseService as ExpenseService;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        // TODO: add necessary services here and have them injected by the DI container
        private readonly MonthlySummaryService $summaryService,
        private readonly AlertGenerator $alertGenerator,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger,
        private readonly ExpenseService $expenseService,
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        // TODO: load the currently logged-in user
        // TODO: get the list of available years for the year-month selector
        // TODO: call service to generate the overspending alerts for current month
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month
        $userId = $_SESSION['user_id'] ?? null;
        $user = $this->userRepository->find($userId);

        if (!$user) {
            $this->logger->warning('User not found', ['userId' => $userId]);
            return $response->withStatus(404)->write('User not found');
        }

        $queryParams = $request->getQueryParams();
        $year = (int)($queryParams['year'] ?? date('Y'));
        $month = (int)($queryParams['month'] ?? date('m'));

        $availableYears = $this->expenseService->getAvailableYears($user);

        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        $alerts = $this->alertGenerator->generate($user, $year, $month);

        $totalForMonth = $this->summaryService->computeTotalExpenditure($user, $year, $month);
        $totalsForCategories = $this->summaryService->computePerCategoryTotals($user, $year, $month);
        $avgForCategories = $this->summaryService->computePerCategoryAverages($user, $year, $month);

        // Log the dashboard access
        $this->logger->info('Dashboard accessed', [
            'userId' => $user->id,
            'year' => $year,
            'month' => $month,
            'totalForMonth' => $totalForMonth,
            'totalsForCategories' => $totalsForCategories,
            'averagesForCategories' => $avgForCategories,
        ]);

        return $this->render($response, 'dashboard.twig', [
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'availableYears' => $availableYears,
            'alerts' => $alerts,
            'totalForMonth' => $totalForMonth,
            'totalsForCategories' => $totalsForCategories,
            'averagesForCategories' => $avgForCategories,
        ]);
    }
}
