<?php

namespace App\Enums;

enum QuestionType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Select = 'select';
    case Number = 'number';
    case Date = 'date';
    case Email = 'email';
}
