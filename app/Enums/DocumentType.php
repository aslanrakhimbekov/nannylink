<?php

namespace App\Enums;

enum DocumentType: string
{
    case CRIMINAL_RECORD = 'criminal_record';
    case MEDICAL_CLEARANCE = 'medical_clearance';
    case IDENTITY_CARD = 'identity_card';
    case NARCOLOGY_CLEARANCE = 'narcology_clearance';
    case PSYCHIATRY_CLEARANCE = 'psychiatry_clearance';
}
