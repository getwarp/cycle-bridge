<?php

declare(strict_types=1);

namespace Warp\Bridge\Cycle\Select;

use Cycle\ORM\Select;
use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

/**
 * @implements \IteratorAggregate<ScopeInterface>
 */
final class ScopeAggregate implements ScopeInterface, PrepareSelectScopeInterface, PrepareLoaderScopeInterface, \IteratorAggregate
{
    /**
     * @var \SplObjectStorage<ScopeInterface,null>|null
     */
    private ?\SplObjectStorage $scopes;

    public function __construct(ScopeInterface ...$scopes)
    {
        /** @phpstan-var \SplObjectStorage<ScopeInterface,null> $storage */
        $storage = new \SplObjectStorage();
        $this->scopes = $storage;

        foreach ($scopes as $scope) {
            $this->add($scope);
        }
    }

    public function __destruct()
    {
        $this->scopes = null;
    }

    public function add(ScopeInterface $scope): void
    {
        if ($scope instanceof self) {
            foreach ($scope as $s) {
                $this->add($s);
            }
            return;
        }

        \assert(null !== $this->scopes);

        if ($this->scopes->contains($scope)) {
            return;
        }

        $this->scopes->attach($scope);
    }

    public function apply(QueryBuilder $query): void
    {
        foreach ($this->getIterator() as $scope) {
            $scope->apply($query);
        }
    }

    public function prepareLoader(AbstractLoader $loader): void
    {
        foreach ($this->getIterator() as $scope) {
            if ($scope instanceof PrepareLoaderScopeInterface) {
                $scope->prepareLoader($loader);
            }
        }
    }

    public function prepareSelect(Select $select): void
    {
        foreach ($this->getIterator() as $scope) {
            if ($scope instanceof PrepareSelectScopeInterface) {
                $scope->prepareSelect($select);
            }
        }
    }

    /**
     * @return \Traversable<ScopeInterface>
     */
    public function getIterator(): \Traversable
    {
        \assert(null !== $this->scopes);
        foreach ($this->scopes as $scope) {
            yield $scope;
        }
    }
}
