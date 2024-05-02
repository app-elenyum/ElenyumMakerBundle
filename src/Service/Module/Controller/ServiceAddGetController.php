<?php

namespace Elenyum\Maker\Service\Module\Controller;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\HttpFoundation\Response;

class ServiceAddGetController implements ServiceAddControllerInterface
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

        $controllerClass->addConstant('ALLOW_GROUPS', preg_replace('/(\w+)/', 'GET_$1', $data['group']));
        $entityClass = new Literal($entity.'::class');
        $controllerGroups = new Literal($controllerName.'::ALLOW_GROUPS');
        $controllerClass->addAttribute('OA\Response', [
            'response' => Response::HTTP_OK,
            'description' => 'Get item by id',
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
                                ['type' => $entityClass, 'groups' => $controllerGroups]
                            ),
                        ]
                    ),
                ],
            ]),
        ]);
        $controllerClass->addAttribute('OA\Response', [
            'response' => Response::HTTP_EXPECTATION_FAILED,
            'description' => 'Get item error',
            'content' => Literal::new('OA\JsonContent', [
                'properties' => [
                    Literal::new('OA\Property', ['property' => 'message', 'type' => 'string', 'default' => 'Error message']),
                    Literal::new('OA\Property', ['property' => 'success', 'type' => 'boolean', 'default' => false]),
                ],
            ]),
        ]);
        $method = new Literal('Request::METHOD_GET');
        $controllerClass->addAttribute('Route', ['path' => $path, 'methods' => [$method]]);

        $invoke = $controllerClass->addMethod('__invoke');
        $invoke->addParameter('request')->setType('Request');

        $invoke->addParameter('service')->setType($service);

        $body = '
try {
    $item = $service->getOne($request->get(\'id\'), self::ALLOW_GROUPS);
     
    return $this->json([
        \'message\' => \'ok\',
        \'success\' => true,
        \'item\' => $item,
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
        return sprintf('%sGetController', ucfirst($entityName));
    }
}