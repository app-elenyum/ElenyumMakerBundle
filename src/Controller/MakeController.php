<?php

namespace Elenyum\Maker\Controller;

use Doctrine\DBAL\Exception;
use Elenyum\Maker\Service\Module\ServiceMakeModule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class MakeController extends AbstractController
{
    public function __construct(
        private ServiceMakeModule $makeModule
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
//        $data = json_decode(file_get_contents(__DIR__ . '/../../tests/testData.json'), true);

        /** structure of created files and folder */
        try {
            if (mb_strtoupper($request->getMethod()) !== Request::METHOD_POST) {
                return $this->json([
                    'success' => false,
                    'message' => 'Allow only POST method'
                ]);
            }

            $data = json_decode($request->getContent(), true);

            [$structures, $sqls] = $this->makeModule->createModule($data);
            return $this->json([
                'success' => true,
                'data' => [
                    'structures' => $structures,
                    'sqls' => $sqls
                ]
            ]);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}