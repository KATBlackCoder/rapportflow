<?php

namespace App\Enums;

enum ResponseStatus: string
{
    case Submitted = 'submitted';
    case ReturnedForCorrection = 'returned_for_correction';
}
