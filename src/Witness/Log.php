<?php

namespace Eyewitness\Eye\Witness;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Eyewitness\Eye\Eye;
use Psr\Log\LogLevel;
use ReflectionClass;
use Exception;

class Log
{
    /**
     * Get the exception error and send to Eyewitness server.
     *
     * @return array
     */
    public function sendExceptionAlert($level, $message = null)
    {
        if ($level instanceof MessageLogged) {
            $message = $level->message;
            $level = $level->level;
        }
        
        if ($message instanceof Exception) {
            $message = $message->getMessage();
        }

        if (in_array($level, config('eyewitness.exception_alert_levels'))) {
            app(Eye::class)->api()->sendExceptionAlert($level, $message);
        }
    }

    /**
     * Get the error count for the server for the past hour (if tracked).
     *
     * @return array
     */
    public function getErrorHistory()
    {
        for ($i=0; $i<2; $i++) {
            $tag = gmdate('Y_m_d_H', time() - (3600*$i));
            $history[$tag] = Cache::get('eyewitness_error_count_'.$tag, 0);
        }

        return $history;
    }

    /**
     * Get any/all log files in the log directory.
     *
     * @return array
     */
    public function getLogFiles()
    {
        $log_files = glob(storage_path('logs/*.log'));
        $files = [];

        foreach (array_filter($log_files, 'is_file') as $id => $file) {
            $files[$id]['path'] = $file;
            $files[$id]['size'] = filesize($file);
            $files[$id]['log_count'] = $this->getLogCount($file);
        }

        return $files;
    }

    /**
     * Get a list of log filenames.
     *
     * @return array
     */
    public function getLogFilenames()
    {
        $files = [];

        foreach (glob(storage_path('logs/*.log')) as $file) {
            $files[] = basename($file);
        }

        return $files;
    }

    /**
     * Get a list of all log levels.
     *
     * @return array
     */
    public function getLogLevels()
    {
        $loglevels = new ReflectionClass(new LogLevel);
        return $loglevels->getConstants();
    }

    /**
     * Get a count of the number of logs in a file.
     *
     * @param  string  $file
     * @return array
     */
    public function getLogCount($file)
    {
        try {
            $fp = fopen($file, 'rb');
        } catch (Exception $e) {
            return 0;
        }
                
        $logs = substr_count(fread($fp, 5), "[20");
        rewind($fp);

        while (!feof($fp)) {
            $logs += substr_count(fread($fp, 8192), "\n[20");
        }

        fclose($fp);

        return $logs;
    }

    /**
     * Read a log file in reverse.
     *
     * @param  string  $filename
     * @param  int  $log_count_required
     * @param  int  $offset
     * @param  mixed  $start
     * @return array
     */
    public function readLogFile($filename, $log_count_required, $offset, $start = null)
    {
        $fp = fopen(storage_path('logs/'.$filename), "r");

        if (is_null($start)) {
            $start = filesize(storage_path('logs/'.$filename))-1;
        }
        
        $pos = $start - (is_null($offset) ? 0 : $offset);

        $logs = [];
        $current_log = [];
        $log_count = 0;
        $current_line = '';

        while (fseek($fp, $pos, SEEK_SET) !== -1) {
            $char = fgetc($fp);

            if (($char == "\n") && ($current_line !== '')) {
                $current_log[] = $current_line;
                if (starts_with($current_line, '[20')) {
                    $logs[$log_count] = $current_log;
                    $current_log = [];
                    $log_count++;
                }
                $current_line = '';
            } else {
                $current_line = $char . $current_line;
            }

            $pos--;

            if ($log_count >= $log_count_required) {
                break;
            }
        }

        if ($log_count < $log_count_required) {
            $current_log[] = $current_line;
            if (starts_with($current_line, '[20')) {
                $logs[$log_count] = $current_log;
            }
        }

        return ['logs' => $logs, 'offset' => $pos, 'start' => $start];
    }
}