<?php

declare(strict_types=1);

namespace Warp\Bridge\Cycle\Schema;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Schema\Compiler;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Warp\Bridge\Cycle\Mapper\HydratorMapper;
use Warp\Bridge\Cycle\Mapper\StdClassMapper;

abstract class AbstractRegistryFactory
{
    protected DatabaseProviderInterface $dbal;

    /**
     * @var GeneratorInterface[]
     */
    protected array $generators;

    /**
     * @param DatabaseProviderInterface $dbal
     * @param GeneratorInterface[] $generators
     */
    public function __construct(DatabaseProviderInterface $dbal, iterable $generators = [])
    {
        $this->dbal = $dbal;
        $this->generators = [...$generators];
    }

    public function addGenerator(GeneratorInterface $generator, GeneratorInterface ...$generators): void
    {
        foreach ([$generator, ...$generators] as $g) {
            $this->generators[] = $g;
        }
    }

    abstract public function make(): Registry;

    /**
     * @param Registry|null $registry
     * @param array<array-key,mixed> $defaults
     * @return mixed[]
     */
    public function compile(?Registry $registry = null, array $defaults = []): array
    {
        return (new Compiler())->compile($registry ?? $this->make(), $this->generators, $defaults);
    }

    protected function autocompleteEntity(Entity $e): void
    {
        if (null === $e->getRole() && null !== $class = $e->getClass()) {
            $e->setRole($class);
        }

        if (null === $e->getRole()) {
            throw new \RuntimeException('Entity must define role or class name.');
        }

        if (null === $e->getMapper()) {
            $e->setMapper(null === $e->getClass() ? StdClassMapper::class : HydratorMapper::class);
        }
    }
}
