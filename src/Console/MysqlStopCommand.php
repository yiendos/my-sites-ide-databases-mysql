<?php

namespace Yiendos\MySitesIde\Databases\Mysql\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiendos\MySitesIde\Databases\Mysql\Traits\InteractsWithMysql;

class MysqlStopCommand extends Command
{
    use InteractsWithMysql;

    /**
     * The ability to configure the console command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('databases:mysql-stop')
            ->setDescription('Stop the MySQL container, leaving the rest of the IDE running')
        ;
    }

    /**
     * Stopped rather than removed, so databases:mysql-start brings back the
     * same container. The next ide:spark starts it again (autostart).
     *
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param SymfonyStyle $io
     * @return integer
     */
    public function __invoke(OutputInterface $output, InputInterface $input, SymfonyStyle $io): int
    {
        if (!$this->mysqlRunning()) {
            $io->writeln('MySQL is not running - nothing to stop.');
            return Command::SUCCESS;
        }

        if ($this->compose($output, 'stop mysql') !== 0) {
            $io->error('MySQL did not stop - see above.');
            return Command::FAILURE;
        }

        $io->success('MySQL stopped.');

        return Command::SUCCESS;
    }
}
