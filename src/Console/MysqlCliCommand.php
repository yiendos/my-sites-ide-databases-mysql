<?php

namespace Yiendos\MySitesIde\Databases\Mysql\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiendos\MySitesIde\Databases\Mysql\Traits\InteractsWithMysql;

class MysqlCliCommand extends Command
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
            ->setName('databases:mysql-cli')
            ->setDescription('Open a mysql client, as root, inside the MySQL container')
            ->addArgument('database', InputArgument::OPTIONAL, 'The database to use')
        ;
    }

    /**
     * Root has no password (MYSQL_ALLOW_EMPTY_PASSWORD), so no prompt.
     *
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param SymfonyStyle $io
     * @return integer
     */
    public function __invoke(OutputInterface $output, InputInterface $input, SymfonyStyle $io): int
    {
        if (!$this->mysqlRunning()) {
            $io->error('MySQL is not running - php my-sites-ide databases:mysql-start');
            return Command::FAILURE;
        }

        $database = $input->getArgument('database');

        return $this->compose($output, 'exec mysql mysql -uroot' . ($database ? ' ' . escapeshellarg($database) : ''));
    }
}
