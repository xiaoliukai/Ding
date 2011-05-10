<?php
/**
 * TCP Client example.
 *
 * PHP Version 5
 *
 * @category   Ding
 * @package    Examples
 * @subpackage TCPServerHelper
 * @author     Marcelo Gornstein <marcelog@gmail.com>
 * @license    http://marcelog.github.com/ Apache License 2.0
 * @version    SVN: $Id$
 * @link       http://marcelog.github.com/
 *
 * Copyright 2011 Marcelo Gornstein <marcelog@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
declare(ticks=1);
use Ding\Helpers\ErrorHandler\ErrorInfo;
use Ding\Helpers\ErrorHandler\IErrorHandler;
use Ding\Helpers\SignalHandler\ISignalHandler;
use Ding\Helpers\ShutdownHandler\IShutdownHandler;
use Ding\Helpers\TCP\ITCPServerHandler;

ini_set(
    'include_path',
    implode(
        PATH_SEPARATOR,
        array(
            ini_get('include_path'),
            implode(DIRECTORY_SEPARATOR, array('..', '..', '..', 'src', 'mg'))
        )
    )
);

require_once 'Ding/Autoloader/Autoloader.php'; // Include ding autoloader.
\Ding\Autoloader\Autoloader::register(); // Call autoloader register for ding autoloader.
use Ding\Container\Impl\ContainerImpl;

/**
 * @ErrorHandler
 * @SignalHandler
 * @ShutdownHandler
 */
class MyErrorHandler implements IErrorHandler, ISignalHandler, IShutdownHandler
{
    public function handleError(ErrorInfo $error)
    {
        echo "This is your custom error handler: " . print_r($error, true);
    }

    public function handleShutdown()
    {
        echo "This is your custom shutdown handler.\n";
    }

    public function handleSignal($signal)
    {
        global $run;
        echo "This is your custom signal handler: " . $signal . "\n";
        $run = false;
    }
}
class MyServerHandler implements ITCPServerHandler
{
    public function beforeOpen()
    {
        echo "before open\n";
    }

    public function beforeListen()
    {
        echo "before listen\n";
    }

    public function close()
    {
        echo "close\n";
    }

    public function handleConnection($remoteAddress, $remotePort)
    {
        global $server;
        echo "new connection from: $remoteAddress:$remotePort\n";
    }

    public function readTimeout($remoteAddress, $remotePort)
    {
        global $server;
        echo "read timeout for $remoteAddress:$remotePort\n";
        $server->disconnect($remoteAddress, $remotePort);
    }

    public function handleData($remoteAddress, $remotePort)
    {
        global $server;
        $buffer = '';
        $len = 4096;
        echo "data from: $remoteAddress:$remotePort\n";
        $server->read($remoteAddress, $remotePort, $buffer, $len);
        echo $buffer . "\n";
        $server->write($remoteAddress, $remotePort, 'You said: ' . $buffer);
    }

    public function disconnect($remoteAddress, $remotePort)
    {
        echo "disconnect: $remoteAddress:$remotePort\n";
    }
}
$run = true;
$properties = array(
    'ding' => array(
        'log4php.properties' => './log4php.properties',
        'factory' => array(
            'drivers' => array(
                'signalhandler' => array(),
				'shutdown' => array(),
				'errorhandler' => array()
            ),
			'bdef' => array(
                'xml' => array('filename' => 'beans.xml'),
                'annotation' => array('scanDir' => array(realpath(__DIR__)))
            ),
        ),
        'cache' => array(
            'proxy' => array('impl' => 'dummy'),
            'bdef' => array('impl' => 'dummy'),
            'beans' => array('impl' => 'dummy')
        )
    )
);
$a = ContainerImpl::getInstance($properties);
$server = $a->getBean('Server');
$server->open();

while($run)
{
    usleep(1000);
}
$server->close();
