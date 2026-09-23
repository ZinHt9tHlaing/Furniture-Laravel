<?php

namespace App\Enums;

enum ErrorCode: string
{
    case Invalid            = 'Error_Invalid';
    case BadRequest         = 'Error_BadRequest';
    case Unauthenticated    = 'Error_Unauthenticated';
    case Attack             = 'Error_Attack';
    case TokenExpired       = 'Error_TokenExpired';
    case AccessTokenExpired = 'Error_AccessTokenExpired';
    case UserExist          = 'Error_UserAlreadyExist';
    case OverLimit          = 'Error_OverLimit';
    case OtpExpired         = 'Error_OtpExpired';
    case RequestExpired     = 'Error_RequestExpired';
    case AccountFreeze      = 'Error_AccountFreeze';
    case Unauthorized       = 'Error_Unauthorized';
    case Maintenance        = 'Error_Maintenance';
    case InternalError      = 'Error_InternalError';
}
