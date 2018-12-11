<?php
/**
 * Created by PhpStorm.
 * User: EKMYmiamtermeze
 * Date: 10/12/2018
 * Time: 1:05 PM
 */

namespace Dumper;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;


class Extractor
{
    protected $connection;
    protected $host;
    protected $username;
    protected $password;
    protected $db;
    protected $options;
    protected $date;

    /**
     * Extractor constructor.
     * @param $username
     * @param $password
     * @param $host
     * @param $db
     * @param array $options
     * @throws DatabaseException
     * @return void
     */
    public function __construct($username, $password, $host, $db, $options = [])
    {
        $this->connection = new Database($username, $password, $host, $db);
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->db = $db;
        $this->options = $options;
        $this->date = 'dumper-' . date("Y-m-d_H-i-s");
    }

    /**
     * @return array
     */
    public function checkTable()
    {
        return $this->connection->showTables();
    }

    /**
     * @param $table
     * @return int
     */
    public function getNumberOfRows($table)
    {
        return $this->connection->getNumberOfRows($table);
    }

    /**
     * @param $table
     * @return void
     */
    public function extract($table, $limitation, $rows)
    {
        if($rows > $limitation){
            $this->chunking($table, $limitation, $rows);
        } else {
            $process = new Process($this->dumper($table));
            $this->executor($process);
        }
    }

    /**
     * @param $process
     */
    protected function executor($process)
    {
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();
    }

    /**
     * @param $table
     * @param bool $exceed
     * @param null $offset
     * @param null $limit
     * @return string
     */
    protected function dumper($table,$exceed = false,$offset = null, $limit = null, $directory = '/tmp')
    {
        $filesystem = new Filesystem();

        if(!$filesystem->exists("$directory/$this->date")){
            $filesystem->mkdir("$directory/$this->date", 0644);
        }


        $name = "$directory/$this->date/" . system('date +%s%N') . '_' . $table . ".sql";
        echo $name . "\n";
        if($exceed){
            return "mysqldump -h$this->host -u$this->username -p$this->password $this->db $table --where=\"1 limit $offset, $limit\"> $name";
        } else {
            return "mysqldump -h$this->host -u$this->username -p$this->password $this->db $table> $name";
        }

    }

    /**
     * @param $table
     * @param $limitation
     * @param $rows
     */
    protected function chunking($table, $limitation, $rows)
    {
        $nums = range(0, $rows, $limitation);
        foreach($nums as $num){
            $process = new Process($this->dumper($table, true, $num, $limitation));
            $this->executor($process);
        }

    }

}
