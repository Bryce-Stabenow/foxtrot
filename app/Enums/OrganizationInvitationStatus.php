<?php

namespace App\Enums;

enum OrganizationInvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case EXPIRED = 'expired';
} 