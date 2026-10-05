<?php

namespace App\Enums;

enum Category: string
{
    case Hair = 'hair';
    case Beard = 'beard';
    case HairAndBeard = 'hair and beard';
    case Fade = 'fade';
    case Coloring = 'coloring';
}
