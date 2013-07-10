<?php
namespace PHPDaemon\Applications;

use PHPDaemon\Core\Daemon;

/**
 * Class GiderosTest
 * @package PHPDaemon\Applications
 * For testing gideros functionality
 */
class GibsonTest extends \PHPDaemon\Core\AppInstance{

    public $gibson;
    /**
     * Called when the worker is ready to go.
     * @return void
     */
    public function onReady(){
        Daemon::log('GIBSON READY');
        $this->gibson = \PHPDaemon\Clients\Gibson\Pool::getInstance();
    }

    /**
     * Creates Request.
     * @param object Request.
     * @param object Upstream application instance.
     * @return object Request.
     */
    public function beginRequest($req, $upstream){
        return new GibsonTestRequest($this, $upstream, $req);
    }

}

use PHPDaemon\HTTPRequest\Generic;

/**
 * Class Gibson
 * @package PHPDaemon\Applications
 * For testing Gibson functionality
 */
class GibsonTestRequest extends Generic{

    public $job;

    /**
     * Constructor.
     * @return void
     */
    public function init(){
        $req = $this;

        $job = $this->job = new \PHPDaemon\Core\ComplexJob(function () use ($req){ // called when job is done
            $req->wakeup(); // wake up the request immediately
        });

        $job('testquery', function ($name, $job) { // registering job named 'testquery'

            $val = rand(0, 10000);

            $this->appInstance->gibson->set(100, 'testval', $val, function ($conn) use ($name, $job, $val) {
                $this->appInstance->gibson->get('testval', function($conn) use ($val, $job){
                    if ($conn->result === $val){
                        $job->setResult('testval', 1);
                    } else {
                        $job->setResult('testval', 0);
                    }
                });

            });
        });

        $job(); // let the fun begin

        $this->sleep(1, true); // setting timeout*/
    }

    /**
     * Called when request iterated.
     * @return integer Status.
     */
    public function run(){
        try {
            $this->header('Content-Type: text/html');
        } catch(\Exception $e) {
        }
        ?>
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>Example with Gibson</title>
        </head>
        <body>
        <?php
        if($r = $this->job->getResult('testquery')) {
            echo '<h1>It works! Be happy! ;-)</h1>Result of query: <pre>';
            var_dump($r);
            echo '</pre>';
        } else {
            echo '<h1>Something went wrong! We have no result.</h1>';
        }
        echo '<br />Request (http) took: ' . round(microtime(TRUE) - $this->attrs->server['REQUEST_TIME_FLOAT'], 6);
        ?>
        </body>
        </html>
    <?php
    }

}


