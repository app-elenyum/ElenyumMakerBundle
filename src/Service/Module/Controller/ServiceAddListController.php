<?php

namespace Elenyum\Maker\Service\Module\Controller;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\HttpFoundation\Response;

class ServiceAddListController implements ServiceAddControllerInterface
{
    public function createController(string $fullNamespace, string $service, string $entity, array $data, ?string $prefix): PhpNamespace
    {
        $namespace = new PhpNamespace($fullNamespace);
        $namespace->addUse('Symfony\Bundle\FrameworkBundle\Controller\AbstractController');
        $namespace->addUse('Symfony\Component\Routing\Attribute\Route');
        $namespace->addUse('Symfony\Component\HttpFoundation\Response');
        $namespace->addUse('Symfony\Component\HttpFoundation\Request');
        $namespace->addUse('Elenyum\OpenAPI\Attribute\Tag');
        $namespace->addUse('Elenyum\OpenAPI\Attribute\Model');
        $namespace->addUse('OpenApi\Attributes', 'OA');
        $namespace->addUse('Throwable');

        $controllerName = $this->getName($data['entity_name']);
        $controllerClass = $namespace->addClass($controllerName);
        $controllerClass->setExtends('AbstractController');

        if (class_exists('\Elenyum\Dashboard\Attribute\StatCountRequest')) {
            $namespace->addUse('Elenyum\Dashboard\Attribute\StatCountRequest');
            $controllerClass->addAttribute('StatCountRequest');
        }

        $lowerNameModule = $data['module_name_lower'];
        $lowerNameEntity = $data['entity_name_lower'];

        $controllerClass->addAttribute('Tag', ['name' => $lowerNameModule]);
        $version = str_replace('.', '_', $data['version']);
        $path = (!empty($prefix) ? "/{$prefix}" : '').sprintf(
                '/%s/%s/%s',
                $version,
                $lowerNameModule,
                $lowerNameEntity
            );

        $entityClass = new Literal($entity.'::class');
        $controllerClass->addAttribute('OA\Response', [
            'response' => Response::HTTP_OK,
            'description' => 'Get list items',
            'content' => Literal::new('OA\JsonContent', [
                'properties' => [
                    Literal::new('OA\Property', ['property' => 'message', 'type' => 'string', 'default' => 'ok']),
                    Literal::new('OA\Property', ['property' => 'success', 'default' => true]),
                    Literal::new(
                        'OA\Property',
                        [
                            'property' => 'items',
                            'type' => 'array',
                            'items' => Literal::new('OA\Items', ['ref' => Literal::new('Model', ['type' => $entityClass, 'options' => ['method' => 'GET']])]),
                        ]
                    ),
                    Literal::new('OA\Property', [
                        'property' => 'paginator',
                        'properties' => [
                            Literal::new('OA\Property', ['property' => 'offset', 'type' => 'integer']),
                            Literal::new('OA\Property', ['property' => 'limit', 'type' => 'integer']),
                            Literal::new('OA\Property',
                                [
                                    'property' => 'total',
                                    'description' => 'total elements, width filter if exist',
                                    'type' => 'integer',
                                ]
                            ),
                        ],
                        'type' => 'object',
                    ]),
                ],
            ]),
        ]);
        $controllerClass->addAttribute('OA\Response', [
            'response' => Response::HTTP_EXPECTATION_FAILED,
            'description' => 'Get list error',
            'content' => Literal::new('OA\JsonContent', [
                'properties' => [
                    Literal::new('OA\Property', ['property' => 'message', 'type' => 'string', 'default' => 'Error message']),
                    Literal::new('OA\Property', ['property' => 'success', 'type' => 'boolean', 'default' => false]),
                ],
            ]),
        ]);

        $controllerClass->addAttribute('OA\Parameter', ['name' => 'limit', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'integer']), 'example' => 10]);
        $controllerClass->addAttribute('OA\Parameter', ['name' => 'offset', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'integer']), 'example' => 20]);
        $controllerClass->addAttribute('OA\Parameter', ['name' => 'fields', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'string']), 'example' => '["id", "name", "card.id", "card.name"]']);
        $controllerClass->addAttribute('OA\Parameter', ['name' => 'filter', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'string']), 'example' => '{"name":"test"}']);
        $controllerClass->addAttribute('OA\Parameter', ['name' => 'orderBy', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'string']), 'example' => '{"name":"desc"}']);
        $method = new Literal('Request::METHOD_GET');
        /** @todo тут нужно добавлять атрибуты кастумные если они прописаны в настройках */
        $controllerClass->addAttribute('Route', ['path' => $path, 'methods' => [$method]]);

        $invoke = $controllerClass->addMethod('__invoke');
        $invoke->addParameter('request')->setType('Request');

        $invoke->addParameter('service')->setType($service);

        $body = '
try {
    $offset = $request->get(\'offset\', 0);
    $limit = $request->get(\'limit\', 10);
    $orderBy = $request->get(\'orderBy\', \'{}\');
    $filter = $request->get(\'filter\', \'{}\');
    $fields = $request->get(\'fields\', \'[]\');
    $groups = $service->getEntityGroups(\'GET\');
    [$total, $items] = $service->getList(
        offset: $offset,
        limit: $limit,
        orderBy: json_decode($service->prepareJsonFormat($orderBy), true) ?? [],
        groups: $groups,
        filter: json_decode($service->prepareJsonFormat($filter), true) ?? [],
        fields: json_decode($service->prepareJsonFormat($fields), true) ?? [],
    );
    return $this->json([
        \'message\' => \'success\',
        \'success\' => true,
        \'items\' => $items,
        \'paginator\' => [
            \'offset\' => $offset,
            \'limit\' => $limit,
            \'total\' => $total,
        ],
    ], Response::HTTP_OK);
} catch (Throwable $e) {
    return $this->json([
    \'message\' => $e->getMessage(),
    \'success\' => false,
    ], Response::HTTP_EXPECTATION_FAILED);
}
';
        $invoke->addBody($body);
        $invoke->setReturnType('Response');

        return $namespace;
    }

    public function getName(string $entityName): string
    {
        return sprintf('%sListController', ucfirst($entityName));
    }
}