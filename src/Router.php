<?php

namespace Middleware;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements MiddlewareInterface
{
    /** @var Dispatcher */
    private $dispatcher;

    /** @var string */
    private $handlerAttribute = 'Request-Handler';

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /**
     * Create a new Router instance
     *
     * @param Dispatcher               $dispatcher
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(Dispatcher $dispatcher, ResponseFactoryInterface $responseFactory)
    {
        $this->dispatcher = $dispatcher;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            return $this->responseFactory->createResponse(404);
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->responseFactory
                ->createResponse(405)
                ->withAddedHeader('Allow', implode(', ', $routeInfo[1]));
        }

        $request = $request
            ->withAttribute($this->handlerAttribute, $routeInfo[1])
            ->withAttribute('params', $routeInfo[2]);

        return $handler->handle($request);
    }

    /**
     * Set the attribute name for the request handler
     *
     * @param string $handlerAttribute
     * @return $this
     */
    public function withHandlerAttribute(string $handlerAttribute): self
    {
        $this->handlerAttribute = $handlerAttribute;

        return $this;
    }
}
