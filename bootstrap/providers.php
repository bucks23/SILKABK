<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;

return [
    AppServiceProvider::class,
    // Gate kepemilikan data (orang tua hanya melihat data anaknya sendiri)
    AuthServiceProvider::class,
];
