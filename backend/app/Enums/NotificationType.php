<?php

namespace App\Enums;

enum NotificationType: string
{
    case Like    = 'like';
    case Comment = 'comment';
    case Follow  = 'follow';
    case Mention = 'mention';
    case Repost  = 'repost';
    case Share   = 'share';
}
