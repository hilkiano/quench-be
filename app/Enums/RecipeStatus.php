<?php

namespace App\Enums;

enum RecipeStatus: string
{
    case DRAFT = "DRAFT";
    case APPROVED = "APPROVED";
    case REJECTED = "REJECTED";
    case HIDDEN = "HIDDEN";
}
