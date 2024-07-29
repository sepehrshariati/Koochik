<?php
namespace Koochik\Koochik\Tests\Mocks\Controllers;
use Laminas\Diactoros\Response;

class DemoController
{
    public function hello(): Response {
        $response = new Response();
        $response->getBody()->write("Hello world");
        return $response;
    }

    public function greet($name,$lastname,$age): Response {
        $response = new Response();
        $response->getBody()->write("Hello user {$name} {$lastname} {$age}");
        return $response;
    }

    public function shoot($name,$lastname,$age): Response {
        $response = new Response();
        $response->getBody()->write("Hello user {$name} {$lastname} {$age}");
        return $response;
    }


}