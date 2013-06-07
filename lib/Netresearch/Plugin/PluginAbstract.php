<?php
namespace Netresearch\Plugin;

use Netresearch\Config;
use Netresearch\IssueHandler;
use Netresearch\Issue as Issue;

/**
 * Base class for plugins
 */
abstract class PluginAbstract
{
    const OCCURRENCES_LIST_PREFIX = '  * ';
    const OCCURRENCES_LIST_SUFFIX = PHP_EOL;

    protected $_phpBin;
    /**
     * Execution command
     * @var string
     */
    protected $_execCommand;

    /**
     * Plugin name, same as check class name
     * @var string
     */
    protected $_pluginName;

    /**
     * Path to extension source
     * @var string
     */
    protected $_extensionPath;

    /**
     * The global Judge configuration
     * @var \Netresearch\Config
     */
    protected $_config;
    /**
     * The local plugin configuration
     * @var \Zend_Config
     */
    protected $_settings;

    /**
     * Base constructor for all plugins
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->_config = $config;
        $this->_pluginName = current(explode('\\', get_class($this)));
        $this->_settings = $this->_config->plugins->{$this->_pluginName};
    }

    /**
     * Execute a plugin (entry point)
     *
     * @param string $extensionPath the path to the extension to check
     */
    public function execute($extensionPath)
    {
        $this->_extensionPath = $extensionPath;
    }


    /**
     * @param Config $config
     * @param array $additionalOptions
     * @return array
     */
    protected function _executePhpCommand(Config $config, array $additionalOptions)
    {
        exec('which php', $response);
        $this->_phpBin = reset($response);

        if (!empty($config->phpOptions)) {
            foreach ($config->phpOptions as $option) {
                $this->_phpBin .= ' -d ' . $option;
            }
        }

        if (!empty($additionalOptions)) {
            foreach ($additionalOptions as $key => $value) {
                $this->_execCommand .= is_string($key) ? ' --' . $key . '=' . $value
                    : ' ' . $value;
            }
        }

        $command = $this->_phpBin . ' ' . $this->_execCommand;
        $command .= !in_array($this->_extensionPath, $additionalOptions) ? ' ' . $this->_extensionPath : '';

        return $this->_executeCommand($command);
    }

    /**
     * @param string $command
     * @return array
     * @throws \Zend_Exception
     */
    protected function _executeCommand($command)
    {
        //Logger::log($command);
        exec($command, $response, $status);

        if ($status == 255) {
            $this->setUnfinishedIssue();
            throw new \Zend_Exception('Failed to execute ' . $this->_pluginName .' plugin.');
        }

        return $response;
    }

    /**
     *
     */
    public function setUnfinishedIssue($reason = '')
    {
        $message = 'Failed to execute ' . $this->_pluginName .' plugin.';
        // if a specific reason is given, append it to the message
        if (0 < strlen(trim($reason))) {
            $message .= ' reason: ' . $reason;
        }
        IssueHandler::addIssue(new Issue(
            array(
                'extension' =>  $this->_extensionPath,
                'checkname' =>  $this->_pluginName,
                'type'      =>  'unfinished',
                'comment'   =>  $message,
                'failed'    =>  false
            )
        ));
    }

    /**
     * @param $command
     * @return PluginAbstract
     */
    public function setExecCommand($command)
    {
        $this->_execCommand = $command;
        return $this;
    }

}