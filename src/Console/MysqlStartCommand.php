<?php

namespace Yiendos\MySitesIde\Databases\Mysql\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiendos\MySitesIde\Databases\Mysql\Traits\InteractsWithMysql;

class MysqlStartCommand extends Command
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
            ->setName('databases:mysql-start')
            ->setDescription('Start the MySQL container - or recreate it if its compose config changed')
        ;
    }

    /**
     * Always runs `up -d`, even when mysql is already running: it's a no-op
     * for an up-to-date container, and recreates one whose compose config
     * has changed - e.g. a new MYSQL_PORT, or a container created before
     * mysql became a plugin. The data is on the host, so it survives.
     *
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param SymfonyStyle $io
     * @return integer
     */
    public function __invoke(OutputInterface $output, InputInterface $input, SymfonyStyle $io): int
    {
        if ($this->compose($output, 'up -d mysql') !== 0) {
            $io->error('MySQL did not start - see above.');
            return Command::FAILURE;
        }

        $io->success('MySQL started - localhost:' . (getenv('MYSQL_PORT') ?: '3306') . ' from the host');
        $io->text([
            "Point a site's .env at it:",
            '',
            '    DB_CONNECTION=mysql',
            '    DB_HOST=mysql',
            '    DB_PORT=3306',
            '',
        ]);

        return Command::SUCCESS;
    }
}
