<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;

class Dashboard extends AbstractController
{
    public function index(): Response
    {
        return $this->view('dashboard.index');
    }
}