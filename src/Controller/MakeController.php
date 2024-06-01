<?php

namespace Elenyum\Maker\Controller;

use Doctrine\DBAL\Exception;
use Elenyum\Maker\Service\Module\ServiceMakeModule;
use Elenyum\Maker\Service\Module\ServiceShowModule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class MakeController extends AbstractController
{
    public function __construct(
        private ServiceMakeModule $makeModule,
        private ServiceShowModule $showModule
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            if (mb_strtoupper($request->getMethod()) === Request::METHOD_GET) {
                $data = $this->showModule->getModules();
                return $this->json([
                    'success' => true,
                    'data' => $data,
                ]);
            } elseif (mb_strtoupper($request->getMethod()) === Request::METHOD_POST) {
                $data = json_decode($request->getContent(), true);

                [$structures, $sqls] = $this->makeModule->createModule($data);

                return $this->json([
                    'success' => true,
                    'data' => [
                        'structures' => $structures,
                        'sqls' => $sqls,
                    ],
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Allow only POST or GET method',
                ]);
            }
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}