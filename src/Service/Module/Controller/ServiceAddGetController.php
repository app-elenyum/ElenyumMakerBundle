<?php

namespace Elenyum\Maker\Service\Module\Controller;

use Nette\PhpGenerator\PhpNamespace;

class ServiceAddGetController implements ServiceAddControllerInterface
{
    public function createController(string $fullNamespace,  array $data, ?string $prefix): PhpNamespace
    {
        $namespace = new PhpNamespace($fullNamespace);
        $namespace->addUse('Symfony\Bundle\FrameworkBundle\Controller\AbstractController');
        $namespace->addUse('Symfony\Component\Routing\Annotation\Route');
        $namespace->addUse('Symfony\Component\HttpFoundation\Response');
        $namespace->addUse('Elenyum\Maker\HttpFoundation\Request');
        $namespace->addUse('Elenyum\OpenAPI\Attribute\Tag');
        $namespace->addUse('OpenApi\Attributes', 'OA');

        $controllerClass = $namespace->addClass('GetController');
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
        $controllerClass->addAttribute('Route', ['path' => $path, 'methods' => 'Request::METHOD_GET']);

        $invoke = $controllerClass->addMethod('__invoke');
        $invoke->addParameter('id')->setType('int');
        $invoke->addParameter('request')->setType('Request');
        $invoke->addParameter('service')->setType('object');

        $body = '
try {
    $repository = $service->getRepository();
    if (!$repository instanceof GetItemInterface) {
        throw new Exception(\'Repository not implements GetItemInterface\');
    }
    $item = $repository->getItem($id);
    if (!$item instanceof News) {
        throw new UndefinedEntity(News::class, $id);
    }
    return $this->json([
        \'success\' => true,
        \'code\' => Response::HTTP_OK,
        \'item\' => $item->toArray(\'get\'),
    ]);
} catch (Exception $e) {
    return $this->json([
        \'success\' => false,
        \'code\' => Response::HTTP_EXPECTATION_FAILED,
        \'message\' => $e->getMessage(),
    ], Response::HTTP_EXPECTATION_FAILED);
}
';

        $invoke->addBody($body);
        $invoke->setReturnType('Response');

        return $namespace;
    }
}