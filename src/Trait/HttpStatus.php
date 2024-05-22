<?php

namespace App\Trait;

enum StatusCode: int
{
    case OK = 200;
    case CREATED = 201;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
}

trait HttpStatus
{
    public function response(StatusCode $httpCode = StatusCode::OK, $data = null, $headers = [])
    {
        return $this->json($data, $httpCode->value, $headers);
    }
}
