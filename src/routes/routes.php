<?php

use Illuminate\Support\Facades\Route;
use sqits\gripp\Client;

Route::get('/employees/{paginate?}', function () {
    $con = new Client();

    $options = [
        "paging" => [
            "firstresult" => 0,
            "maxresults" => request()->paginate ?? 250,
        ],
    ];

    $response = $con->employee_get([], $options);

    return $response;
});
