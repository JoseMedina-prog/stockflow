<?php

namespace App\Enums;

enum LeadSource: string
{
    case Website = 'website';
    case Referral = 'referral';
    case SocialMedia = 'social_media';
    case WalkIn = 'walk_in';
    case ColdCall = 'cold_call';
    case Ad = 'ad';
    case Event = 'event';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Sitio web',
            self::Referral => 'Referido',
            self::SocialMedia => 'Redes sociales',
            self::WalkIn => 'Visitó la tienda',
            self::ColdCall => 'Llamada en frío',
            self::Ad => 'Publicidad',
            self::Event => 'Evento',
            self::Other => 'Otro',
        };
    }
}
