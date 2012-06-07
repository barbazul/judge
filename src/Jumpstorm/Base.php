<?php
namespace Jumpstorm;

use Netresearch\Logger;
use Netresearch\Config;
use Netresearch\Source\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * install extensions
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 */
class Base extends Command
{
    protected $config;
    protected $output;

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $this->addOption('config',  'c', InputOption::VALUE_OPTIONAL, 'provide a configuration file', 'ini/jumpstorm.ini');
    }

    protected function preExecute(InputInterface $input, OutputInterface $output)
    {
        $this->config = new Config($input->getOption('config'), null, array('allowModifications' => true));
        $this->config->setOutput($output);
        $this->config->setCommand($this);
        Logger::setOutputInterface($output);
    }

    /**
     * check if target exists (try to create it otherwise) and is writeable
     * 
     * @param string $target 
     * @return string $target
     */
    protected function validateTarget($target)
    {
        if (!$target) {
            throw new \Exception('Please set common.magento.target in ini-file.');
        }
        
        if (!is_dir($target)) {
            mkdir($target);
        }
        
        if (!is_dir($target)) {
            throw new \Exception("Target is not a directory: $target");
        }

        if (!is_writable($target)) {
            throw new \Exception("Target directory is not writeable: $target");
        }
        
        return $target;
    }
    
    /**
     * Prepare command for database access, including:
     * <ul>
     * <li>username</li>
     * <li>host</li>
     * <li>password</li>
     * </ul>
     * 
     * @return string MySQL command line string including credentials
     */
    protected function prepareMysqlCommand()
    {
        $mysql = sprintf(
            'mysql -u%s -h%s',
            $this->config->getDbUser(),
            $this->config->getDbHost()
        );

        // prepare mysql command: password
        if (!is_null($this->config->getDbPass())) {
            $mysql .= sprintf(' -p%s', $this->config->getDbPass());
        }

        return $mysql;
    }
    
    /**
     * Create empty database. Any old database with the same name gets dropped.
     * 
     * @return boolean true on success, false otherwise
     */
    protected function createDatabase($dbName)
    {
        // prepare mysql command: user, host and password
        $mysql = $this->prepareMysqlCommand();
        
        // recreate database if it already exists
        Logger::log('Creating database %s', array($dbName));

        exec(sprintf(
            '%s -e \'DROP DATABASE IF EXISTS `%s`\'',
            $mysql,
            $dbName
        ), $result, $return);
        
        exec(sprintf(
            '%s -e \'CREATE DATABASE `%s`\'',
            $mysql,
            $dbName
        ), $result, $return);

        return (0 === $return);
    }

    protected function getBasePath()
    {
        return realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    }
}
