<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** A query-string value as trimmed text; arrays (e.g. ?q[]=x) and other non-strings count as empty. */
    protected function queryText(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? trim($value) : '';
    }
}
