<?php

namespace Elenyum\Maker\Service\Module\Controller;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\HttpFoundation\Response;

class ServiceAddPutController extends AbstractServiceController implements ServiceAddControllerInterface
{
    public function createController(string $fullNamespace, string $service, string $entity, array $data, ?string $prefix): PhpNamespace
    {
        $namespace = new PhpNamespace($fullNamespace);
        $namespace->addUse('Symfony\Bundle\FrameworkBundle\Controller\AbstractController');
        $namespace->addUse('Symfony\Component\Routing\Attribute\Route');
        $namespace->addUse('Symfony\Component\HttpFoundation\Response');
        $namespace->addUse('Symfony\Component\HttpFoundation\Request');
        $namespace->addUse('Elenyum\Maker\Exception\ValidationException');
        $namespace->addUse('Elenyum\OpenAPI\Attribute\Tag');
        $namespace->addUse('Elenyum\OpenAPI\Attribute\Model');
        $namespace->addUse('OpenApi\Attributes', 'OA');
        $namespace->addUse('Throwable');

        $controllerName = $this->getName($data['entity_name']);
        $controllerClass = $namespace->addClass($controllerName);
        $controllerClass->setExtends('AbstractController');
        $entityClass = new Literal($entity.'::class');

        /** Если нет ограничений для групп то не зачем добавлять авторизацию */
        if (!empty($data['group']) || $this->hasRoles($data, 'PUT')) {
            $this->addAutAttribute($namespace, $entityClass, $controllerClass);
        }

        if (class_exists('\Elenyum\Dashboard\Attribute\StatCountRequest')) {
            $namespace->addUse('Elenyum\Dashboard\Attribute\StatCountRequest');
            $controllerClass->addAttribute('StatCountRequest');
        }

        $lowerNameModule = $data['module_name_lower'];
        $lowerNameEntity = $data['entity_name_lower'];

        $controllerClass->addAttribute('Tag', ['name' => $lowerNameModule]);
        $version = str_replace('.', '_', $data['version']);
        $path = (!empty($prefix) ? "/{$prefix}" : '').sprintf(
                '/%s/%s/%s/{id<\d+>}',
                $version,
                $lowerNameModule,
                $lowerNameEntity
            );

        $controllerClass->addConstant('ALLOW_GROUPS', preg_replace('/(\w+)/', 'PUT_$1', $data['group']));

        $controllerClass->addAttribute('OA\RequestBody', [
            'content' => Literal::new('OA\JsonContent', [
                'ref' => Literal::new('Model', ['type' => $entityClass, 'options' => ['method' => 'PUT']])
            ])
        ]);
        $controllerClass->addAttribute('OA\Response', [
            'response' => Response::HTTP_OK,
            'description' => 'Update item by id',
            'content' => Literal::new('OA\JsonContent', [
                'properties' => [
                    Literal::new('OA\Property', ['property' => 'message', 'type' => 'string', 'default' => 'ok']),
                    Literal::new('OA\Property', ['property' => 'success', 'type' => 'boolean', 'default' => true]),
                    Literal::new(
                        'OA\Property',
                        [
                            'property' => 'item',
                            'ref' => Literal::new(
                                'Model',
                                ['type' => $entityClass, 'options' => ['method' => 'GET']]
                            ),
                        ]
                    ),
                ],
            ]),
        ]);
        $controllerClass->addAttribute('OA\Response', [
            'response' => Response::HTTP_BAD_REQUEST,
            'description' => 'Update item error validation',
            'content' => Literal::new('OA\JsonContent', [
                'properties' => [
                    Literal::new('OA\Property', ['property' => 'message', 'type' => 'string', 'default' => 'Error message']),
                    Literal::new('OA\Property', ['property' => 'success', 'type' => 'boolean', 'default' => false]),
                    Literal::new('OA\Property', ['property' => 'errors', 'type' => 'object',
                        'default' => [
                            'name' => ['error text','error text 2']
                        ],
                        'additionalProperties' => Literal::new('OA\AdditionalProperties', [
                            'type' => 'array',
                            'items' => Literal::new('OA\Items', ['type' => 'string']),
                            'default' => [
                                'name' => ['error text','error text 2']
                            ]
                        ])
                    ]),
                ],
            ]),
        ]);
        $controllerClass->addAttribute('OA\Response', [
            'response' => Response::HTTP_EXPECTATION_FAILED,
            'description' => 'Update item error',
            'content' => Literal::new('OA\JsonContent', [
                'properties' => [
                    Literal::new('OA\Property', ['property' => 'message', 'type' => 'string', 'default' => 'Error message']),
                    Literal::new('OA\Property', ['property' => 'success', 'type' => 'boolean', 'default' => false]),
                ],
            ]),
        ]);
        $controllerClass->addAttribute(
            'OA\Parameter',
            ['name' => 'id', 'in' => 'path', 'schema' => Literal::new('OA\Schema', ['type' => 'integer'])]
        );
        $method = new Literal('Request::METHOD_PUT');
        $controllerClass->addAttribute('Route', ['path' => $path, 'methods' => [$method]]);

        $invoke = $controllerClass->addMethod('__invoke');
        $invoke->addParameter('request')->setType('Request');

        $invoke->addParameter('service')->setType($service);

        $body = '
try {
    $putGroups = $service->getEntityGroups(\'PUT\', $this->getUser(), [\'PUT_public\']);
    $getGroups = $service->getEntityGroups(\'GET\', $this->getUser(), [\'GET_public\']);
    $item = $service->update($request->getContent(), $request->get(\'id\'), $putGroups, $getGroups);
    
    return $this->json([
        \'message\' => \'ok\',
        \'success\' => true,
        \'item\' => $item
    ], Response::HTTP_OK);
} catch (ValidationException $e) {
    return $this->json([
        \'message\' => $e->getMessage(),
        \'success\' => false,
        \'errors\' => $e->getErrors()
    ], Response::HTTP_BAD_REQUEST);
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
        return sprintf('%sPutController', ucfirst($entityName));
    }
}