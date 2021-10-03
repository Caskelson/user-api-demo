<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait JsonHandlingTrait
{
    public function getJsonFromRequest(Request $request): mixed
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Invalid json');
        }

        return $data;
    }

    public function jsonResponseHandler(SerializerInterface $serializer, $data, int $status = Response::HTTP_OK, array $context = []): Response
    {
        return new JsonResponse(
            $serializer->serialize(
                $data, 
                'json', 
                $context
            ), 
            $status,
            [],
            true
        );
    }

    public function getJsonDefaultMessage(string $format, string|array|object $messages): array
    {
        $data = [];
        switch($format)
        {
            case "auth_err":
                $data = [
                    'type' => 'authentication_error',
                    'title' => 'There was an authentication error',
                    'error' => $messages, 
                ];
                break;

            case "auth_suc":
                $data = [
                    'type' => 'authentication_success',
                    'title' => 'User authentication was successful',
                    'X-AUTH-TOKEN' => $messages,
                ];
                break;

            case "val_err":
                $data = [
                    'type' => 'validation_error',
                    'title' => 'There was a validation error',
                    'errors' => $messages,
                ];
                break;

            case "val_suc":
                $data = [
                    'type' => 'validation_success',
                    'title' => 'Request validation was successful',
                    'data' => $messages,
                ];
        }

        return $data;
    }
}