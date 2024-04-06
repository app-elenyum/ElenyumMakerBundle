<?php

namespace Elenyum\Maker\Controller;

use Elenyum\Maker\Service\Module\ServiceMakeModule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MakeController extends AbstractController
{
    public function __construct(
        private ServiceMakeModule $makeModule
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../../tests/testData.json'), true);

        /** structure of created files and folder */
        $structures = $this->makeModule->createModule($data);
        return $this->json([
            'success' => true,
            'structures' => $structures
        ]);
    }
}