<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Permet $this->authorize(...) dans les contrôleurs
    use AuthorizesRequests;
}
