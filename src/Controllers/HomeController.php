<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class HomeController extends AbstractController
{
    public function index(): Response
    {
//        $this->viewer->registerFilter('lowercase', fn($value) => strtolower($value));
//        $this->viewer->registerFilter('reverse', fn($value) => strrev($value));
//        return $this->view('home.index', ['test' => 'Hello World']);

//        $geoLocator = new GeoLocator();
//
//        $location = $geoLocator->getLocation(); // Hämta plats för besökaren
//        echo "Land: " . ($location['country'] ?? 'Okänt');
//        echo "Stad: " . ($location['city'] ?? 'Okänt');
//
//        // Hämta endast specifik data
//        $country = $geoLocator->get('country', '85.228.5.49'); // För valfri IP
//        echo "Land för 85.228.5.49: $country";

//        $search = User::search('ma', ['first_name', 'last_name', 'email']);
//
//        dd($search);

        return $this->view('home.index');
    }
}