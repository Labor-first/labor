<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\RequirePassword as Middleware;

class RequirePassword extends Middleware
{
    /**
     * Determine the duration after which the user should be prompted to re-enter their password.
     *
     * @return int
     */
    protected function passwordTimeout()
    {
        // For JWT, return a default timeout of 3 hours
        return 180;
    }
}
