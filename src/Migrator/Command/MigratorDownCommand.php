<?php

declare(strict_types=1);

namespace Warp\Bridge\Cycle\Migrator\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Warp\Bridge\Cycle\Migrator\Handler;
use Warp\Bridge\Cycle\Migrator\Input;
use Warp\Bridge\Cycle\Migrator\LockFacade;

final class MigratorDownCommand extends Command implements MigratorApplyCommandInterface
{
    protected static $defaultName = 'migrator:down';

    protected static $defaultDescription = 'Rollback executed database migrations';

    private Input\ForceOption $force;

    private Input\MigrationsCountArgument $count;

    private ContainerInterface $container;

    private LockFacade $lockFacade;

    public function __construct(ContainerInterface $container, ?string $name = null)
    {
        $this->container = $container;

        $this->lockFacade = new LockFacade($this->container);

        parent::__construct($name);

        $this->force = new Input\ForceOption();
        $this->force->configure($container);
        $this->force->register($this);

        $this->count = new Input\MigrationsCountArgument();
        $this->count->register($this);
    }

    public function getMigrationsCount(InputInterface $input): int
    {
        return (int)\max($this->count->getValueFrom($input) ?? 0, 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->force->confirm($input, $io);

        $this->lockFacade->acquire();

        try {
            return $this->container->get(Handler\MigratorDownCommandHandler::class)->handle($this, $input, $io);
        } finally {
            $this->lockFacade->release();
        }
    }
}
