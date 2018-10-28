<?php


namespace Middleware\Tests\Fixtures;


use Zend\Diactoros\Response;

class TestController
{
    /**
     * GET /users
     */
    public function index()
    {
        return new Response('data://plain/text,index', 200);
    }

    /**
     * GET /users/{id}
     */
    public function show(array $params)
    {
        return new Response('data://plain/text,' . json_encode(['user' => (int) $params['id']]), 200);
    }
}
