<?php

namespace App\Http\Controllers;

use App\Enums\Position;
use App\Enums\QuestionnaireTargetType;
use App\Enums\ResponseStatus;
use App\Http\Requests\Rapport\ReturnForCorrectionRequest;
use App\Http\Requests\Rapport\StoreRapportRequest;
use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RapportController extends Controller
{
    /**
     * Display the main rapport page with dropdown options.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $employee = $user->employee;

        $options = [];

        if ($employee) {
            $position = $employee->position;

            if ($position === Position::Employer || $position === Position::Superviseur) {
                $options[] = [
                    'value' => 'create',
                    'label' => 'Faire un rapport',
                    'description' => 'Remplir un nouveau questionnaire et soumettre un rapport',
                ];
                $options[] = [
                    'value' => 'my-reports',
                    'label' => 'Regarder ses rapports',
                    'description' => 'Consulter vos rapports déjà soumis (lecture seule)',
                ];
                $options[] = [
                    'value' => 'corrections',
                    'label' => 'Corriger un rapport',
                    'description' => 'Corriger les rapports renvoyés pour correction',
                ];

                if ($position === Position::Superviseur) {
                    $options[] = [
                        'value' => 'analysis',
                        'label' => 'Analyser rapport',
                        'description' => 'Consulter et analyser les rapports de votre groupe',
                    ];
                }
            }

            if ($position === Position::Manager || $position === Position::ChefSuperviseur) {
                $options[] = [
                    'value' => 'analysis',
                    'label' => 'Analyser rapport',
                    'description' => 'Consulter et analyser tous les rapports soumis dans l\'application',
                ];
            }
        }

        return Inertia::render('rapports/Index', [
            'options' => $options,
        ]);
    }

    /**
     * Display the list of available questionnaires for filling.
     */
    public function create(Request $request): Response
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'Vous devez être un employé pour accéder à cette page.');
        }

        $query = Questionnaire::query()
            ->with(['creator', 'questions'])
            ->where('status', 'published')
            ->latest();

        // Filtrage selon target_type et position
        $position = $employee->position;

        if ($position === Position::Employer) {
            $query->where('target_type', QuestionnaireTargetType::Employees->value);
        } elseif ($position === Position::Superviseur || $position === Position::ChefSuperviseur || $position === Position::Manager) {
            $query->where(function ($q) {
                $q->where('target_type', QuestionnaireTargetType::Supervisors->value)
                    ->orWhere('target_type', QuestionnaireTargetType::Employees->value);
            });
        }

        $questionnaires = $query->paginate(15);

        return Inertia::render('rapports/Create', [
            'questionnaires' => $questionnaires,
        ]);
    }

    /**
     * Display the questionnaire filling form.
     */
    public function show(Questionnaire $questionnaire): Response
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'Vous devez être un employé pour accéder à cette page.');
        }

        // Vérifier que le questionnaire est publié
        if ($questionnaire->status->value !== 'published') {
            abort(404);
        }

        $questionnaire->load(['questions' => function ($query) {
            $query->orderBy('order');
        }, 'questions.conditionalQuestion']);

        return Inertia::render('rapports/Show', [
            'questionnaire' => $questionnaire,
        ]);
    }

    /**
     * Store a new rapport submission.
     */
    public function store(StoreRapportRequest $request): RedirectResponse
    {
        $user = auth()->user();

        DB::transaction(function () use ($request, $user) {
            $responses = $request->validated()['responses'];

            foreach ($responses as $responseData) {
                QuestionnaireResponse::create([
                    'questionnaire_id' => $request->validated()['questionnaire_id'],
                    'question_id' => $responseData['question_id'],
                    'respondent_id' => $user->id,
                    'row_identifier' => $responseData['row_identifier'] ?? null,
                    'response' => $responseData['response'],
                    'status' => ResponseStatus::Submitted,
                    'submitted_at' => now(),
                ]);
            }
        });

        return redirect()->route('rapports.my-reports')
            ->with('success', 'Rapport soumis avec succès.');
    }

    /**
     * Display the list of user's submitted reports.
     */
    public function myReports(Request $request): Response
    {
        $user = auth()->user();

        $query = QuestionnaireResponse::query()
            ->with(['questionnaire', 'question'])
            ->where('respondent_id', $user->id)
            ->select('questionnaire_id', 'row_identifier', 'status', 'submitted_at', 'correction_reason')
            ->groupBy('questionnaire_id', 'row_identifier', 'status', 'submitted_at', 'correction_reason')
            ->latest('submitted_at');

        if ($request->has('questionnaire_id')) {
            $query->where('questionnaire_id', $request->questionnaire_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }

        $reports = $query->paginate(15);

        return Inertia::render('rapports/MyReports', [
            'reports' => $reports,
            'filters' => $request->only(['questionnaire_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display a single user report (read-only).
     */
    public function showMyReport(QuestionnaireResponse $response): Response
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est le propriétaire
        if ($response->respondent_id !== $user->id) {
            abort(403);
        }

        $response->load([
            'questionnaire.questions' => function ($query) {
                $query->orderBy('order');
            },
            'question',
        ]);

        // Récupérer toutes les réponses pour ce questionnaire et ce row_identifier
        $responses = QuestionnaireResponse::query()
            ->where('questionnaire_id', $response->questionnaire_id)
            ->where('respondent_id', $user->id)
            ->where('row_identifier', $response->row_identifier)
            ->with('question')
            ->get()
            ->groupBy('row_identifier');

        return Inertia::render('rapports/ShowMyReport', [
            'response' => $response,
            'responses' => $responses,
        ]);
    }

    /**
     * Display the list of reports returned for correction.
     */
    public function corrections(Request $request): Response
    {
        $user = auth()->user();

        $query = QuestionnaireResponse::query()
            ->with(['questionnaire', 'question'])
            ->where('respondent_id', $user->id)
            ->where('status', ResponseStatus::ReturnedForCorrection)
            ->select('questionnaire_id', 'row_identifier', 'status', 'submitted_at', 'correction_reason', 'reviewed_at')
            ->groupBy('questionnaire_id', 'row_identifier', 'status', 'submitted_at', 'correction_reason', 'reviewed_at')
            ->latest('reviewed_at');

        $corrections = $query->paginate(15);

        return Inertia::render('rapports/Corrections', [
            'corrections' => $corrections,
        ]);
    }

    /**
     * Display the correction form for a specific report.
     */
    public function showCorrection(QuestionnaireResponse $response): Response
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est le propriétaire et que le statut est returned_for_correction
        if ($response->respondent_id !== $user->id || $response->status !== ResponseStatus::ReturnedForCorrection) {
            abort(403);
        }

        $response->load([
            'questionnaire.questions' => function ($query) {
                $query->orderBy('order');
            },
            'question',
        ]);

        // Récupérer toutes les réponses pour ce questionnaire et ce row_identifier
        $responses = QuestionnaireResponse::query()
            ->where('questionnaire_id', $response->questionnaire_id)
            ->where('respondent_id', $user->id)
            ->where('row_identifier', $response->row_identifier)
            ->with('question')
            ->get()
            ->groupBy('row_identifier');

        return Inertia::render('rapports/ShowCorrection', [
            'response' => $response,
            'responses' => $responses,
        ]);
    }

    /**
     * Update a corrected report.
     */
    public function updateCorrection(StoreRapportRequest $request, QuestionnaireResponse $response): RedirectResponse
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est le propriétaire et que le statut est returned_for_correction
        if ($response->respondent_id !== $user->id || $response->status !== ResponseStatus::ReturnedForCorrection) {
            abort(403);
        }

        DB::transaction(function () use ($request, $response) {
            // Supprimer les anciennes réponses pour ce row_identifier
            QuestionnaireResponse::query()
                ->where('questionnaire_id', $response->questionnaire_id)
                ->where('respondent_id', $response->respondent_id)
                ->where('row_identifier', $response->row_identifier)
                ->delete();

            // Créer les nouvelles réponses
            $responses = $request->validated()['responses'];

            foreach ($responses as $responseData) {
                QuestionnaireResponse::create([
                    'questionnaire_id' => $request->validated()['questionnaire_id'],
                    'question_id' => $responseData['question_id'],
                    'respondent_id' => $response->respondent_id,
                    'row_identifier' => $responseData['row_identifier'] ?? null,
                    'response' => $responseData['response'],
                    'status' => ResponseStatus::Submitted,
                    'submitted_at' => now(),
                ]);
            }
        });

        return redirect()->route('rapports.my-reports')
            ->with('success', 'Correction soumise avec succès.');
    }

    /**
     * Display the analysis page for supervisors/managers.
     */
    public function analysis(Request $request): Response
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403);
        }

        $position = $employee->position;

        $query = QuestionnaireResponse::query()
            ->with(['questionnaire', 'respondent.employee', 'question'])
            ->select('questionnaire_id', 'respondent_id', 'row_identifier', 'status', 'submitted_at')
            ->groupBy('questionnaire_id', 'respondent_id', 'row_identifier', 'status', 'submitted_at')
            ->latest('submitted_at');

        // Filtrage selon le rôle
        if ($position === Position::Superviseur) {
            // Uniquement les rapports des employés qu'il supervise (via supervisor_id)
            $supervisedEmployeeIds = $employee->supervisedEmployees()->pluck('id');
            $supervisedUserIds = \App\Models\Employee::whereIn('id', $supervisedEmployeeIds)->pluck('user_id')->filter();
            $query->whereIn('respondent_id', $supervisedUserIds);
        }
        // Pour managers et chefs superviseurs, pas de filtre (tous les rapports)

        // Filtres
        if ($request->has('questionnaire_id')) {
            $query->where('questionnaire_id', $request->questionnaire_id);
        }

        if ($request->has('respondent_id')) {
            $query->where('respondent_id', $request->respondent_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }

        $reports = $query->paginate(15);

        $questionnaires = Questionnaire::where('status', 'published')->get(['id', 'title']);
        $respondents = \App\Models\User::whereHas('employee')->with('employee')->get(['id', 'name']);

        return Inertia::render('rapports/Analysis', [
            'reports' => $reports,
            'questionnaires' => $questionnaires,
            'respondents' => $respondents,
            'filters' => $request->only(['questionnaire_id', 'respondent_id', 'status', 'date_from', 'date_to']),
            'canExport' => $position === Position::Manager || $position === Position::ChefSuperviseur,
        ]);
    }

    /**
     * Display detailed analysis of a specific report.
     */
    public function showAnalysis(QuestionnaireResponse $response): Response
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403);
        }

        $position = $employee->position;

        // Vérifier les permissions
        if ($position === Position::Superviseur) {
            $supervisedEmployeeIds = $employee->supervisedEmployees()->pluck('id');
            $supervisedUserIds = \App\Models\Employee::whereIn('id', $supervisedEmployeeIds)->pluck('user_id')->filter();
            if (! $supervisedUserIds->contains($response->respondent_id)) {
                abort(403);
            }
        }

        $response->load([
            'questionnaire.questions' => function ($query) {
                $query->orderBy('order');
            },
            'respondent.employee',
            'question',
        ]);

        // Récupérer toutes les réponses pour ce questionnaire et ce row_identifier
        $responses = QuestionnaireResponse::query()
            ->where('questionnaire_id', $response->questionnaire_id)
            ->where('respondent_id', $response->respondent_id)
            ->where('row_identifier', $response->row_identifier)
            ->with('question')
            ->get()
            ->groupBy('row_identifier');

        return Inertia::render('rapports/ShowAnalysis', [
            'response' => $response,
            'responses' => $responses,
            'canExport' => $position === Position::Manager || $position === Position::ChefSuperviseur,
        ]);
    }

    /**
     * Return a report for correction.
     */
    public function returnForCorrection(ReturnForCorrectionRequest $request, QuestionnaireResponse $response): RedirectResponse
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403);
        }

        $position = $employee->position;

        // Vérifier les permissions
        if ($position === Position::Superviseur) {
            $supervisedEmployeeIds = $employee->supervisedEmployees()->pluck('id');
            $supervisedUserIds = \App\Models\Employee::whereIn('id', $supervisedEmployeeIds)->pluck('user_id')->filter();
            if (! $supervisedUserIds->contains($response->respondent_id)) {
                abort(403);
            }
        }

        // Mettre à jour les réponses sélectionnées
        $responseIds = $request->validated()['response_ids'];

        DB::transaction(function () use ($responseIds, $request, $user) {
            QuestionnaireResponse::whereIn('id', $responseIds)
                ->update([
                    'status' => ResponseStatus::ReturnedForCorrection,
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'correction_reason' => $request->validated()['correction_reason'],
                ]);
        });

        return redirect()->route('rapports.analysis')
            ->with('success', 'Rapport renvoyé pour correction.');
    }

    /**
     * Export reports to Excel.
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403);
        }

        $position = $employee->position;

        // Seuls les managers et chefs superviseurs peuvent exporter
        if ($position !== Position::Manager && $position !== Position::ChefSuperviseur) {
            abort(403);
        }

        // TODO: Implémenter l'export Excel avec Maatwebsite\Excel ou PhpSpreadsheet
        // Pour l'instant, retourner une réponse JSON avec les données
        $query = QuestionnaireResponse::query()
            ->with(['questionnaire', 'respondent.employee', 'question'])
            ->latest('submitted_at');

        // Appliquer les mêmes filtres que la page d'analyse
        if ($request->has('questionnaire_id')) {
            $query->where('questionnaire_id', $request->questionnaire_id);
        }

        if ($request->has('respondent_id')) {
            $query->where('respondent_id', $request->respondent_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }

        $responses = $query->get();

        // TODO: Générer le fichier Excel
        return response()->json(['data' => $responses], 200);
    }
}
