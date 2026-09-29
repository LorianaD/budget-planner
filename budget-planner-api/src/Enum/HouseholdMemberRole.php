<?php

namespace App\Enum;

enum HouseholdMemberRole : string
{
    case Admin = 'admin';
    case Viewer = 'viewer';
}
