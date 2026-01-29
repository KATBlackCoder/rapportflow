<?php

namespace App\Http\Controllers;

use App\Enums\Position;
use App\Enums\QuestionnaireStatus;
use App\Enums\QuestionnaireTargetType;
use App\Enums\ResponseStatus;
use App\Models\Employee;
use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard adapted to the authenticated user's role.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return Inertia::render('Dashboard/Index', [
                'stats' => [],
                'recentReports' => [],
                'pendingCorrections' => [],
                'lastReport' => null,
                'canAccessQuestionnaires' => false,
                'canAccessEmployees' => false,
                'canExportReports' => false,
            ]);
        }

        $position = $employee->position;
        $periodStart = now()->subDays(30);

        $stats = $this->buildStats($user, $employee, $position, $periodStart);
        $recentReports = $this->buildRecentReports($user, $employee, $position, 8);
        $pendingCorrections = $this->buildPendingCorrections($user, $employee, $position, 5);
        $lastReport = $this->buildLastReport($user);
        $availableQuestionnairesCount = $this->getAvailableQuestionnairesCount($user);

        $canAccessQuestionnaires = in_array($position, [Position::Manager, Position::ChefSuperviseur], true);
        $canAccessEmployees = in_array($position, [Position::Manager, Position::ChefSuperviseur], true);
        $canExportReports = in_array($position, [Position::Manager, Position::ChefSuperviseur], true);

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentReports' => $recentReports,
            'pendingCorrections' => $pendingCorrections,
            'lastReport' => $lastReport,
            'availableQuestionnairesCount' => $availableQuestionnairesCount,
            'canAccessQuestionnaires' => $canAccessQuestionnaires,
            'canAccessEmployees' => $canAccessEmployees,
            'canExportReports' => $canExportReports,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function buildStats(User $user, Employee $employee, Position $position, \DateTimeInterface $periodStart): array
    {
        $stats = [
            'myReportsCount' => 0,
            'pendingCorrectionsCount' => 0,
            'teamReportsCount' => 0,
            'supervisedEmployeesCount' => 0,
            'questionnairesCount' => 0,
            'employeesCount' => 0,
            'supervisorsCount' => 0,
            'totalReportsCount' => 0,
        ];

        $userId = $user->id;

        if ($position === Position::Employer || $position === Position::Superviseur) {
            $stats['myReportsCount'] = QuestionnaireResponse::query()
                ->where('respondent_id', $userId)
                ->whereNotNull('submitted_at')
                ->groupBy('questionnaire_id', 'row_identifier')
                ->count();
            $stats['pendingCorrectionsCount'] = QuestionnaireResponse::query()
                ->where('respondent_id', $userId)
                ->where('status', ResponseStatus::ReturnedForCorrection)
                ->groupBy('questionnaire_id', 'row_identifier')
                ->count();
        }

        if ($position === Position::Superviseur) {
            $supervisedUserIds = $employee->supervisedEmployees()->pluck('user_id')->filter()->values()->all();
            $stats['supervisedEmployeesCount'] = count($supervisedUserIds);
            if ($stats['supervisedEmployeesCount'] > 0) {
                $stats['teamReportsCount'] = (int) DB::table('questionnaire_responses')
                    ->whereIn('respondent_id', $supervisedUserIds)
                    ->where('submitted_at', '>=', $periodStart)
                    ->groupBy('questionnaire_id', 'respondent_id', 'row_identifier')
                    ->count(DB::raw('1'));
            }
        }

        if ($position === Position::ChefSuperviseur) {
            $department = $employee->department;
            $stats['supervisorsCount'] = Employee::query()
                ->where('department', $department)
                ->where('position', Position::Superviseur->value)
                ->count();
            $stats['employeesCount'] = Employee::query()
                ->where('department', $department)
                ->count();
            $stats['questionnairesCount'] = Questionnaire::query()
                ->where('status', QuestionnaireStatus::Published->value)
                ->count();
            $departmentUserIds = Employee::query()->where('department', $department)->pluck('user_id')->filter()->values()->all();
            $stats['totalReportsCount'] = count($departmentUserIds) > 0
                ? (int) DB::table('questionnaire_responses')
                    ->whereIn('respondent_id', $departmentUserIds)
                    ->whereNotNull('submitted_at')
                    ->groupBy('questionnaire_id', 'respondent_id', 'row_identifier')
                    ->count(DB::raw('1'))
                : 0;
            $stats['pendingCorrectionsCount'] = QuestionnaireResponse::query()
                ->where('status', ResponseStatus::ReturnedForCorrection)
                ->whereIn('respondent_id', $departmentUserIds)
                ->groupBy('questionnaire_id', 'row_identifier')
                ->count();
        }

        if ($position === Position::Manager) {
            $stats['employeesCount'] = Employee::query()->count();
            $stats['questionnairesCount'] = Questionnaire::query()
                ->where('status', QuestionnaireStatus::Published->value)
                ->count();
            $stats['totalReportsCount'] = (int) DB::table('questionnaire_responses')
                ->whereNotNull('submitted_at')
                ->groupBy('questionnaire_id', 'respondent_id', 'row_identifier')
                ->count(DB::raw('1'));
            $stats['pendingCorrectionsCount'] = QuestionnaireResponse::query()
                ->where('status', ResponseStatus::ReturnedForCorrection)
                ->groupBy('questionnaire_id', 'row_identifier')
                ->count();
        }

        return $stats;
    }

    /**
     * @return list<array{questionnaire_title: string, submitted_at: string|null, respondent_name?: string, id: int}>
     */
    private function buildRecentReports(User $user, Employee $employee, Position $position, int $limit): array
    {
        $query = QuestionnaireResponse::query()
            ->with(['questionnaire:id,title', 'respondent:id,name'])
            ->select('questionnaire_responses.id', 'questionnaire_id', 'row_identifier', 'respondent_id', 'submitted_at')
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at');

        if ($position === Position::Superviseur) {
            $supervisedUserIds = $employee->supervisedEmployees()->pluck('user_id')->filter()->values()->all();
            if (count($supervisedUserIds) > 0) {
                $query->whereIn('respondent_id', $supervisedUserIds);
            } else {
                return [];
            }
        } elseif ($position === Position::Employer) {
            $query->where('respondent_id', $user->id);
        }
        // Manager et ChefSuperviseur : tous les rapports

        $responses = $query->limit($limit * 5)->get();
        $seen = [];
        $result = [];
        foreach ($responses as $r) {
            $key = $r->questionnaire_id.'_'.($r->row_identifier ?? '').'_'.$r->respondent_id.'_'.($r->submitted_at?->toIso8601String() ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = [
                'id' => $r->id,
                'questionnaire_title' => $r->questionnaire?->title ?? '',
                'submitted_at' => $r->submitted_at?->toIso8601String(),
                'respondent_name' => $r->respondent?->name,
            ];
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return list<array{response_id: int, questionnaire_title: string, questionnaire_id: int, row_identifier: string|null, reviewed_at: string|null}>
     */
    private function buildPendingCorrections(User $user, Employee $employee, Position $position, int $limit): array
    {
        $query = QuestionnaireResponse::query()
            ->select('id', 'questionnaire_id', 'row_identifier', 'reviewed_at')
            ->where('status', ResponseStatus::ReturnedForCorrection)
            ->orderByDesc('reviewed_at');

        if ($position === Position::Employer || $position === Position::Superviseur) {
            $query->where('respondent_id', $user->id);
        }
        // ChefSuperviseur et Manager : tous

        $responses = $query->limit(50)->get();
        $unique = [];
        foreach ($responses as $r) {
            $key = $r->questionnaire_id.'_'.($r->row_identifier ?? '');
            if (isset($unique[$key])) {
                continue;
            }
            $unique[$key] = $r;
            if (count($unique) >= $limit) {
                break;
            }
        }
        $questionnaireIds = array_unique(array_column(array_values($unique), 'questionnaire_id'));
        $questionnaires = Questionnaire::query()->whereIn('id', $questionnaireIds)->get(['id', 'title'])->keyBy('id');
        $list = [];
        foreach (array_values($unique) as $r) {
            $list[] = [
                'response_id' => $r->id,
                'questionnaire_title' => $questionnaires->get($r->questionnaire_id)?->title ?? '',
                'questionnaire_id' => $r->questionnaire_id,
                'row_identifier' => $r->row_identifier,
                'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            ];
        }

        return $list;
    }

    /**
     * @return array{questionnaire_title: string, submitted_at: string|null}|null
     */
    private function buildLastReport(User $user): ?array
    {
        $response = QuestionnaireResponse::query()
            ->with('questionnaire:id,title')
            ->where('respondent_id', $user->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first(['id', 'questionnaire_id', 'submitted_at']);

        if (! $response) {
            return null;
        }

        return [
            'questionnaire_title' => $response->questionnaire?->title ?? '',
            'submitted_at' => $response->submitted_at?->toIso8601String(),
        ];
    }

    private function getAvailableQuestionnairesCount(User $user): int
    {
        $submittedInPeriod = QuestionnaireResponse::query()
            ->where('respondent_id', $user->id)
            ->where('submitted_at', '>=', now()->subDays(30))
            ->distinct()
            ->pluck('questionnaire_id');

        return Questionnaire::query()
            ->where('status', QuestionnaireStatus::Published->value)
            ->where('target_type', QuestionnaireTargetType::Employees->value)
            ->whereNotIn('id', $submittedInPeriod)
            ->count();
    }
}
