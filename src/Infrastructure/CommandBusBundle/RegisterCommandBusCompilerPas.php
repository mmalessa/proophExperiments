<?php

namespace App\Infrastructure\CommandBusBundle;

use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterCommandBusCompilerPas implements CompilerPassInterface
{
    private $busService;
    private $serviceTag;
    private $subscriberInterface;

    /**
     * @param string $busService
     * @param string $serviceTag
     * @param string $subscriberInterface
     */
    public function __construct($busService, $serviceTag, $subscriberInterface)
    {
        $this->busService          = $busService;
        $this->serviceTag          = $serviceTag;
        $this->subscriberInterface = $subscriberInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->busService) && !$container->hasAlias($this->busService)) {
            return;
        }

        $definition = $container->findDefinition($this->busService);


        foreach ($container->findTaggedServiceIds($this->serviceTag) as $id => $attributes) {
            $def = $container->getDefinition($id);

            // Definition getClass can return a parameter
            $class = $container->getParameterBag()->resolveValue($def->getClass());

            $refClass = new ReflectionClass($class);

            if (!$refClass->implementsInterface($this->subscriberInterface)) {
                throw new InvalidArgumentException(
                    sprintf('Service "%s" must implement interface "%s".', $id, $this->subscriberInterface)
                );
            }

            $definition->addMethodCall('subscribe', [new Reference($id)]);
        }
    }
}
