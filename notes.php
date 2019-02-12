#!/usr/bin/php
<?php

    /**
     * Config
     */
    class Config {
        /**
         * App info
         */
        public static $name = "Maven Release Notes";
        public static $version = "1.0";
        public static $author = "Vlad Marian";
        public static $email = "transilvlad@gmail.com";

        /**
         * Options
         * http://php.net/manual/en/function.getopt.php
         */
        public static $short = [
            // Built in
            "h" => "Show help",
            "v" => "Show version",

            // Custom
            "p:" => "Project directory path",
            "l:" => "Logfile name",
        ];

        public static $long = [
            // Built in
            "help" => "Show help",

            // Custom
            "list" => "List latest releases",
            "show:" => "Show selected version changes",
            "from:" => "Show changes from this version onwards",
        ];

        /**
         * List options that are always required (without colons)
         */
        public static $required = [];

        /**
         * Get description
         */
        public function desc($param = "") {
            $opts = strlen($param) == 1 ? Config::$short : Config::$long;

            foreach([$param, $param . ":", $param . "::"] as $entry) {
                if(array_key_exists($entry, $opts)) {
                    return $opts[$entry];
                }
            }

            return "";
        }
    }


    /**
     * Display
     */
    class Display {
        /**
         * Show about
         */
        public static function about() {
            echo Config::$name . " " . Config::$version . " by " . Config::$author . " <" . Config::$email . ">\r\n";
            echo "\r\n";
        }

        /**
         * Show help
         */
        public static function help() {
            $short = array_map(function($v) { return str_replace(":", "", $v); }, array_keys(Config::$short));
            $long = array_map(function($v) { return str_replace(":", "", $v); }, array_keys(Config::$long));

            Display::about();

            echo "Usage: " . basename(__FILE__) . " ";
            if(count($short) > 0) echo "[-" . implode($short, "][-") . "]";
            if(count($long) > 0) echo "[--" . implode($long, "][--") . "]";
            echo "\r\n";
            echo "\r\n";

            if(count(Config::$required) > 0) {
                echo "Mandatory:\r\n";
                foreach(Config::$required as $param) {
                    echo "  " . (strlen($param) == 1 ? " -" : "--") . str_pad($param, 12) . Config::desc($param) . "\r\n";
                }
                echo "\r\n";
            }

            echo "Optional:\r\n";
            foreach($short as $param) {
                echo "  " . (strlen($param) == 1 ? " -" : "--") . str_pad($param, 12) . Config::desc($param) . "\r\n";
            }
            foreach($long as $param) {
                echo "  " . (strlen($param) == 1 ? " -" : "--") . str_pad($param, 12) . Config::desc($param) . "\r\n";
            }

            echo "\r\n";
            Display::stop();
        }

        /**
         * Show version
         */
        public static function version() {
            Display::about();
            Display::stop();
        }

        /**
         * Show error message and exit
         */
        public static function error($str = "") {
            Display::about();

            echo $str . "\r\n\r\n";

            Display::stop();
        }

        /**
         * Exit app
         */
        public static function stop() {
            exit(1);
        }
    }


    /**
     * Usage
     */
    class Usage {
        public static $opts;

        public function __construct() {
           Usage::$opts = getopt(implode(array_keys(Config::$short)), array_keys(Config::$long));
           $this->checkBuiltIn();
           $this->checkOptions();
        }

        /**
         * Check built-in options called
         */
        private function checkBuiltIn() {
            if (in_array("h", array_keys(Usage::$opts)) || in_array("help", array_keys(Usage::$opts))) Display::help();
            if (in_array("v", array_keys(Usage::$opts))) Display::version();
        }

        /**
         * Check required options present
         */
        private function checkOptions() {
            foreach(Config::$required as $param) {
                if (!array_key_exists($param, Usage::$opts)) {
                    Display::error("Missing required argument: " . $param . " - " . Config::desc($param));
                }
            }
        }
    }


    /**
     * App
     */
    class App {
        private $project;
        private $logfile;
        private $tmpfile;
        private $versions;
        private $show;
        private $list;

        public function __construct() {
            $this->project = array_key_exists("p", Usage::$opts) ? Usage::$opts["p"] : "./";
            $this->logfile = array_key_exists("l", Usage::$opts) ? Usage::$opts["l"] : str_replace(".php", ".log", basename(__FILE__));
            $this->tmpfile = $tmp = tempnam("/tmp", "notes");
            $this->list = array_key_exists("list", Usage::$opts);
            $this->show = array_key_exists("show", Usage::$opts) ? Usage::$opts["show"] : "";

            file_put_contents($this->logfile, ""); // clear logfile

            $this->versions();
            if($this->list) $this->list();
            if(!empty($this->show)) $this->show($this->show);
        }

        /**
         * Versions
         */
        private function versions() {
            // execute git log grep for maven releases
            shell_exec("cd " . $this->project . " && git log --pretty=format:\"[%h] %s\" -500 | grep \"maven-release-plugin\" | grep \"prepare release\" > " . $this->tmpfile);

            // read git releases
            $file = file($this->tmpfile);

            // list of versions
            $this->versions = [];
            foreach($file as $k => $line) {
                // parse line and extract hash/version
                preg_match("/\[([a-z0-9]+)\]\s\[maven\-release\-plugin\].*?\(.*\)prepare\srelease\s(.*)/", $line, $matches);

                // record versions and hashes except last
                if (count($matches) > 1 && $k < count($file)) {
                    $this->versions[] = [$matches[2], $matches[1]];

                    // stop if 10 reached
                    if (count($this->versions) == 11) break;
                }
            }
        }

        /**
         * List
         */
        private function list() {
            $this->out("Versions:");
            foreach($this->versions as $k => $array) {
                if($k < 10) $this->out($array[0]);
            }

            $this->out("");
        }

        /**
         * Show
         */
        private function show($version = "") {
            // pick start/end hashes for selected version
            $start = "";
            $end = "";
            foreach($this->versions as $k => $array) {
                if (count($array) == 2 && $array[0] == $version) {
                    $end = $array[1];
                    $start = $this->versions[$k+1][1];
                }
            }

            // validate
            if(empty($start) || empty($end)) {
                Display::error("Cannot find version: " . $version);
            }

            // execute git log grep for release commits
            shell_exec("cd " . $this->project . " && git log --pretty=format:\"[%h] %s\" " . $start . ".." . $end . " | grep -v \"maven-release-plugin\" | grep -Ev \"Merge( remote-tracking)? branch \'\" > " . $this->tmpfile);

            // read git commits
            $file = file($this->tmpfile);

            // separate
            $tickets = [];
            $changes = [];
            foreach($file as $k => $line) {
                // parse line and extract hash/version
                preg_match("/\[([a-z0-9]+)\] (.*)/", $line, $matches);

                // output comments
                if (count($matches) > 1) {
                    if (preg_match('/([a-z]+-[0-9]+)/i', $matches[2], $ticket)) {
                        $tickets[] = $ticket[1];
                    } else {
                        $changes[] = $matches[2];
                    }
                }
            }

            // tickets
            if(count($tickets) > 0) $this->out("Tickets:");
            $unique = array_unique($tickets);
            sort($unique);
            foreach($unique AS $line) {
                $this->out($line);
            }

            // separate if both
            if(count($tickets) > 0 && count($changes) > 0) $this->out("");

            // changes
            if(count($changes) > 0) $this->out("Changes:");
            foreach($changes AS $line) {
                $this->out($line);
            }

            $this->out("");
        }

        /**
         * Print to screen and logfile
         */
        private function out($str = "") {
            echo $str . "\r\n";
            file_put_contents($this->logfile, $str . "\r\n", FILE_APPEND);
        }
    }


    // Run
    new Usage();
    new App();
    exit(0);
