<?php

namespace Eyewitness\Eye;

use Eyewitness\Eye\Witness\Scheduler;
use Eyewitness\Eye\Witness\Database;
use Eyewitness\Eye\Witness\Request;
use Eyewitness\Eye\Witness\Server;
use Eyewitness\Eye\Witness\Queue;
use Eyewitness\Eye\Witness\Email;
use Eyewitness\Eye\Witness\Disk;
use Eyewitness\Eye\Witness\Log;
use Eyewitness\Eye\Api;

class Eye
{
    const QUEUE_CONNECTION_PLACEHOLDER = 'QUEUE_CONNECTION_PLACEHOLDER';
    const QUEUE_TUBE_PLACEHOLDER = 'QUEUE_TUBE_PLACEHOLDER';
    const SECRET_KEY_PLACEHOLDER = 'SECRET_KEY_PLACEHOLDER';
    const APP_TOKEN_PLACEHOLDER = 'APP_TOKEN_PLACEHOLDER';
    const EYE_VERSION = '1.2.2';

    /**
     * The Scheduler witness.
     *
     * @var \Eyewitness\Eye\Witness\Scheduler
     */
    protected $scheduler;

    /**
     * The Database witness.
     *
     * @var \Eyewitness\Eye\Witness\Database
     */
    protected $database;

    /**
     * The Request witness.
     *
     * @var \Eyewitness\Eye\Witness\Request
     */
    protected $request;

    /**
     * The Server witness.
     *
     * @var \Eyewitness\Eye\Witness\Server
     */
    protected $server;

    /**
     * The Queue wintess.
     *
     * @var \Eyewitness\Eye\Witness\Queue
     */
    protected $queue;

    /**
     * The Email witness.
     *
     * @var \Eyewitness\Eye\Witness\Email
     */
    protected $email;

    /**
     * The Disk witness.
     *
     * @var \Eyewitness\Eye\Witness\Disk
     */
    protected $disk;

    /**
     * The Log witness.
     *
     * @var \Eyewitness\Eye\Witness\Log
     */
    protected $log;

    /**
     * The Api back to Eyewitness.io server.
     *
     * @var \Eyewitness\Eye\Api
     */
    protected $api;

    /**
     * Get the version number of the package.
     *
     * @return string
     */
    public function version()
    {
        return static::EYE_VERSION;
    }

    /**
     * Check if Eyewitness appears to be installed and configured.
     *
     * @return bool
     */
    public function checkConfig()
    {
        $app_token = config('eyewitness.app_token');
        $secret_key = config('eyewitness.secret_key');

        if (($app_token == '') || (is_null($app_token)) || ($app_token === self::APP_TOKEN_PLACEHOLDER)) {
            return false;
        }

        if (($secret_key == '') || (is_null($secret_key)) || ($secret_key === self::SECRET_KEY_PLACEHOLDER)) {
            return false;
        }

        return true;
    }

    /**
     * Run all checks.
     *
     * @return array
     */
    public function runAllChecks($email = true)
    {
        $data['server_stats'] = $this->server()->check();

        $data['eyewitness_version'] = $this->version();

        if (config('eyewitness.monitor_database')) {
            $data['db_stats'] = $this->database()->check();
        }

        if (config('eyewitness.monitor_request')) {
            $data['request_stats'] = $this->request()->check();
        }

        if (config('eyewitness.monitor_queue')) {
            $data['queue_stats'] = $this->queue()->allTubeStats();
        }

        if (config('eyewitness.monitor_disk')) {
            $data['disk_stats'] = $this->disk()->check();
        }

        if (config('eyewitness.monitor_email') && ($email)) {
            $this->email()->send();
        }

        if (config('eyewitness.monitor_log')) {
            $data['log_stats'] = $this->log()->check();
        }

        return $data;
    }

    /**
     * Return the Scheduler instance.
     *
     * @return \Eyewitness\Eye\Witness\Scheduler
     */
    public function scheduler()
    {
        if (is_null($this->scheduler)) {
            $this->scheduler = app(Scheduler::class);
        }

        return $this->scheduler;
    }

    /**
     * Return the Database instance.
     *
     * @return \Eyewitness\Eye\Witness\Database
     */
    public function database()
    {
        if (is_null($this->database)) {
            $this->database = app(Database::class);
        }

        return $this->database;
    }

    /**
     * Return the Request instance.
     *
     * @return \Eyewitness\Eye\Witness\Request
     */
    public function request()
    {
        if (is_null($this->request)) {
            $this->request = app(Request::class);
        }

        return $this->request;
    }

    /**
     * Return the Server instance.
     *
     * @return \Eyewitness\Eye\Witness\Server
     */
    public function server()
    {
        if (is_null($this->server)) {
            $this->server = app(Server::class);
        }

        return $this->server;
    }
    /**
     * Return the Queue instance.
     *
     * @return \Eyewitness\Eye\Witness\Queue
     */
    public function queue()
    {
        if (is_null($this->queue)) {
            $this->queue = app(Queue::class);
        }

        return $this->queue;
    }

    /**
     * Return the Email instance.
     *
     * @return \Eyewitness\Eye\Witness\Email
     */
    public function email()
    {
        if (is_null($this->email)) {
            $this->email = app(Email::class);
        }

        return $this->email;
    }

    /**
     * Return the Disk instance.
     *
     * @return \Eyewitness\Eye\Witness\Disk
     */
    public function disk()
    {
        if (is_null($this->disk)) {
            $this->disk = app(Disk::class);
        }

        return $this->disk;
    }

    /**
     * Return the Log instance.
     *
     * @return \Eyewitness\Eye\Log
     */
    public function log()
    {
        if (is_null($this->log)) {
            $this->log = app(Log::class);
        }

        return $this->log;
    }

    /**
     * Return the Api instance.
     *
     * @return \Eyewitness\Eye\Api
     */
    public function api()
    {
        if (is_null($this->api)) {
            $this->api = app(Api::class);
        }

        return $this->api;
    }
}
