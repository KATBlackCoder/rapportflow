<?php

namespace App\Enums;

enum QuestionnaireStatus: string
{
    case Published = 'published';
    case Archived = 'archived';
}
