<?php

namespace Dumper;

use Symfony\Component\Console\Question\Question;
use Dumper\DatabaseException;
use ZipArchive;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class MySql extends Command
{
    protected $host;
    protected $username;
    protected $password;
    protected $db;
    protected $limitation;
    protected $location;
    protected $options;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('mysql')
            ->setDescription('Dumping MySql Data')
            ->setHelp('This command will be able to handle dumping large data and split it into sql file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Initiating...</info>');
        $output->writeln('<info>Preparing export directory</info>');
//        $this->prepareWritableDirectories($output);

        start:

        $helper = $this->getHelper('question');

        $this->askHost($input, $output, $helper);
        $this->askUsername($input, $output, $helper);
        $this->askPassword($input, $output, $helper);

        try {
            $db = new Database($this->username, $this->password, $this->host);
        } catch (DatabaseException $e) {
            $output->writeln('<error>Unable to connect to database. Please try again</error>');
            $output->writeln('<info>Re-initiate...</info>');
            goto start;

        }

        $db_list = $db->showDB();

        $this->askDB($input, $output, $helper, $db_list);
        $this->askExtractLocation($input, $output, $helper);
        $this->askLimitRow($input, $output, $helper);

        $extractor = new Extractor($this->username, $this->password, $this->host, $this->db, $this->options);

        $tables = $extractor->checkTable();

        $output->writeln('<info>' . count($tables) . ' Tables Found</info>');

        foreach($tables as $table) {
            $rows = $extractor->getNumberOfRows($table);

            $output->writeln('<comment>Processing Table ' . $table . '</comment>');

            $output->writeln('<comment>' . $rows . ' of data found!</comment>');

            if($rows > $this->limitation){
                $output->writeln('<comment>Rows Number Exceed Limit, Output file will be chunked.</comment>');
            }

            $output->writeln('<comment>Extracting Table ' . $table . '...</comment>');


            $extractor->extract($table, $this->limitation, $rows);
        }

        $output->writeln('<info>Successfully Exported</info>');

    }

    /**
     * Request for host @ server location
     * @param $input
     * @param $output
     * @param $helper
     * @return void
     */
    protected function askHost($input, $output, $helper)
    {
        $question = new Question('What is your db server host ["127.0.0.1"]? ', '127.0.0.1');

        $this->host = $helper->ask($input, $output, $question);
    }

    /**
     * Request for username
     * @param $input
     * @param $output
     * @param $helper
     * @return void
     */
    protected function askUsername($input, $output, $helper)
    {
        $question = new Question('What is database username ["root"]? ', 'root');

        $this->username = $helper->ask($input, $output, $question);
    }

    /**
     * Request for password
     * @param $input
     * @param $output
     * @param $helper
     * @return void
     */
    protected function askPassword($input, $output, $helper)
    {
        $question = new Question('What is the database password [null]? ');

        $question->setHidden(true);

        $question->setHiddenFallback(false);

        $this->password = $helper->ask($input, $output, $question);
    }

    /**
     * Request for DB name
     * @param $input
     * @param $output
     * @param $helper
     * @param array $db_list
     * @return void
     */
    protected function askDB($input, $output, $helper, $db_list)
    {
        $question = new ChoiceQuestion('Which database you want to export? ', $db_list, 0 );

        $question->setErrorMessage('Invalid Database');

        $this->db = $helper->ask($input, $output, $question);
    }

    /**
     * @param $input
     * @param $output
     * @param $helper
     * @return void
     */
    protected function askLimitRow($input, $output, $helper)
    {
        $question = new Question('How much your data per file? minimum is 50k ["100k"]? ', 100000);

        $this->limitation = $helper->ask($input, $output, $question);

        if($this->limitation < 50000){
            $this->limitation = 50000;
        }
    }

    /**
     * @param $input
     * @param $output
     * @param $helper
     * @return void
     */
    protected function askExtractLocation($input, $output, $helper)
    {
        $question = new Question('Where file should be extract? ["/tmp"]? ', '/tmp');

        $this->location = $helper->ask($input, $output, $question);
    }

//    protected function prepareWritableDirectories(OutputInterface $output)
//    {
//        $filesystem = new Filesystem;
//        $date = date("Y-m-d_H-i-s");
//
//        try {
//            $filesystem->chmod(getcwd().DIRECTORY_SEPARATOR. $date, 0755, 0000, true);
//        } catch (IOExceptionInterface $e) {
//            $output->writeln('<comment>"storage"</comment>');
//        }
//        return $this;
//    }

}
