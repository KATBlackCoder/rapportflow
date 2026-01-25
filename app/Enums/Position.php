<?php

namespace App\Enums;

enum Position: string
{
    case Employer = 'employer';
    case Superviseur = 'superviseur';
    case ChefSuperviseur = 'chef_superviseur';
    case Manager = 'manager';
}
