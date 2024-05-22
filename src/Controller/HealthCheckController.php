<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Trait\StatusCode;

class HealthCheckController extends ApplicationController
{
    #[Route('/health-check', methods: ['GET'])]
    public function index(): Response
    {
        return $this->response(StatusCode::OK, [ 'status' => 'OK' ]);
    }
}
