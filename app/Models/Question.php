<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'questionnaire_id',
        'type',
        'question',
        'required',
        'order',
        'options',
        'conditional_question_id',
        'conditional_value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'required' => 'boolean',
            'order' => 'integer',
            'options' => 'array',
        ];
    }

    /**
     * Get the questionnaire that owns the question.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    /**
     * Get the conditional question that triggers this question.
     */
    public function conditionalQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'conditional_question_id');
    }

    /**
     * Get the questions that depend on this question.
     */
    public function conditionalQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'conditional_question_id');
    }

    /**
     * Get the responses for the question.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(QuestionnaireResponse::class);
    }
}
