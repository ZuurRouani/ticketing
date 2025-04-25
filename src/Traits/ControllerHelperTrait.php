<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\Response;

trait ControllerHelperTrait
{
    private function redirectWithError(string $route, string $message, array $parameters = []): Response
    {
        $this->addFlash('error', $message);
        return $this->redirectToRoute($route, $parameters);
    }

    private function redirectWithSuccess(string $route, string $message, array $parameters = []): Response
    {
        $this->addFlash('success', $message);
        return $this->redirectToRoute($route, $parameters);
    }

    private function renderWithError(string $template, string $message, array $parameters = []): Response
    {
        $this->addFlash('error', $message);
        return $this->render($template, $parameters);
    }
}
