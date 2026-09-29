<?php

namespace App\Utils;

class AuthorizeUtil
{
    /**
     * Usage in routes:
     * ->middleware('authorize:true,ADMIN,AUTHOR')
     * ->middleware('authorize:false,USER')
     *
     * @param bool $permission
     * @param string $userRole
     * @param string ...$roles
     * @return bool
     */
    public static function isAuthorized(bool $permission, string $userRole, string ...$roles): bool
    {
        $result = in_array($userRole, $roles);
        $grant = true;

        if ($permission && !$result) {
            $grant = false;
        }

        if (!$permission && $result) {
            $grant = false;
        }

        return $grant;
    }
}
