<?php

namespace App\Http\Controllers;

use App\Http\Requests\Questionnaire\StoreQuestionnaireRequest;
use App\Http\Requests\Questionnaire\UpdateQuestionnaireRequest;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuestionnaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Questionnaire::class);

        $query = Questionnaire::query()
            ->with(['creator', 'questions'])
            ->latest();

        if ($request->has('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $questionnaires = $query->paginate(15);

        return Inertia::render('Questionnaires/Index', [
            'questionnaires' => $questionnaires,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Questionnaire::class);

        return Inertia::render('Questionnaires/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionnaireRequest $request): RedirectResponse
    {
        Gate::authorize('create', Questionnaire::class);

        DB::transaction(function () use ($request) {
            $questionnaire = Questionnaire::create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);

            if ($request->filled('questions')) {
                $createdQuestions = [];
                foreach ($request->questions as $questionData) {
                    $conditionalQuestionId = null;
                    if (isset($questionData['conditional_question_index']) && $questionData['conditional_question_index'] !== null) {
                        $conditionalIndex = (int) $questionData['conditional_question_index'];
                        if (isset($createdQuestions[$conditionalIndex])) {
                            $conditionalQuestionId = $createdQuestions[$conditionalIndex]->id;
                        }
                    }
                    
                    $question = Question::create([
                        'questionnaire_id' => $questionnaire->id,
                        'type' => $questionData['type'],
                        'question' => $questionData['question'],
                        'required' => $questionData['required'] ?? false,
                        'order' => $questionData['order'] ?? 0,
                        'options' => $questionData['options'] ?? null,
                        'conditional_question_id' => $conditionalQuestionId,
                        'conditional_value' => $questionData['conditional_value'] ?? null,
                    ]);
                    
                    $createdQuestions[] = $question;
                }
            }
        });

        return redirect()->route('questionnaires.index')
            ->with('success', 'Questionnaire créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Questionnaire $questionnaire): Response
    {
        Gate::authorize('view', $questionnaire);

        $questionnaire->load(['creator', 'questions.conditionalQuestion']);

        return Inertia::render('Questionnaires/Show', [
            'questionnaire' => $questionnaire,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Questionnaire $questionnaire): Response
    {
        Gate::authorize('update', $questionnaire);

        $questionnaire->load(['questions']);

        return Inertia::render('Questionnaires/Edit', [
            'questionnaire' => $questionnaire,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionnaireRequest $request, Questionnaire $questionnaire): RedirectResponse
    {
        Gate::authorize('update', $questionnaire);

        DB::transaction(function () use ($request, $questionnaire) {
            $questionnaire->update($request->only(['title', 'description', 'status', 'target_type']));

            if ($request->has('questions')) {
                $questionnaire->questions()->delete();

                $createdQuestions = [];
                foreach ($request->questions as $questionData) {
                    $conditionalQuestionId = null;
                    // Utiliser conditional_question_index si disponible (création/mise à jour)
                    if (isset($questionData['conditional_question_index']) && $questionData['conditional_question_index'] !== null) {
                        $conditionalIndex = (int) $questionData['conditional_question_index'];
                        if (isset($createdQuestions[$conditionalIndex])) {
                            $conditionalQuestionId = $createdQuestions[$conditionalIndex]->id;
                        }
                    } elseif (isset($questionData['conditional_question_id']) && $questionData['conditional_question_id'] !== null) {
                        // Fallback pour compatibilité (ne devrait pas arriver en mise à jour)
                        $conditionalQuestionId = $questionData['conditional_question_id'];
                    }
                    
                    $question = Question::create([
                        'questionnaire_id' => $questionnaire->id,
                        'type' => $questionData['type'],
                        'question' => $questionData['question'],
                        'required' => $questionData['required'] ?? false,
                        'order' => $questionData['order'] ?? 0,
                        'options' => $questionData['options'] ?? null,
                        'conditional_question_id' => $conditionalQuestionId,
                        'conditional_value' => $questionData['conditional_value'] ?? null,
                    ]);
                    
                    $createdQuestions[] = $question;
                }
            }
        });

        return redirect()->route('questionnaires.index')
            ->with('success', 'Questionnaire mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Questionnaire $questionnaire): RedirectResponse
    {
        Gate::authorize('delete', $questionnaire);

        $questionnaire->delete();

        return redirect()->route('questionnaires.index')
            ->with('success', 'Questionnaire supprimé avec succès.');
    }
}
