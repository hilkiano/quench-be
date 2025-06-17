<?php

namespace App\Enums;

enum RecipeStatus: string
{
    case SUBMITTED = "SUBMITTED";
    case APPROVED = "APPROVED";
    case REJECTED = "REJECTED";
    case HIDDEN = "HIDDEN";
}
