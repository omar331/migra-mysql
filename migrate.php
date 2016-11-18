<?php
include_once('config.php');


/*
 * Executes each migration contained in config.php
 */
foreach( $migrations as $migration ) {
    if ( ! array_key_exists('origin', $migration) ) throw new \RuntimeExcetion('Origin information is requered.');

    // file where the dump is going to be stored
    $outfile = sprintf("%s.sql", getOutfile());

    /*
     * Builds the dump command
     */
    $origin = $migration['origin'];

    $origin['outfile'] = $outfile;

    $dumpCommand = buildDumpCommand( $origin );
    echo "Executing dump command $dumpCommand \n";
    exec( $dumpCommand );


    /*
     * Builds the restore command
     */
    $destination = $migration['destination'];
    $destination['source-file'] = $outfile;
    $restoreCommand = buildRestoreCommand(  $destination );
    echo "Executing restore command $restoreCommand \n";

    exec( $restoreCommand );
}


/**
 * Builds the command line for dumping a database
 * @param array $origin origin parameters as follows:
 *
 * <code>
 * [
 *  'host' => '',
 *  'username' => '',
 *  'password' => '',
 *  'port' => '',
 *  'database' => '',
 *  'outfile' => ''
 * ]
 * </code>
 *
 * @return string
 */
function buildDumpCommand( Array $origin ) {
    $command = "mysqldump --no-create-db";

    if ( array_key_exists('host', $origin ) ) {
        $command .= sprintf(" -h %s", $origin['host']);
    }
    if ( array_key_exists('username', $origin ) ) {
        $command .= sprintf(" -u %s", $origin['username']);
    }
    if ( array_key_exists('password', $origin ) ) {
        $command .= sprintf(" -p%s", $origin['password']);
    }
    if ( array_key_exists('port', $origin ) ) {
        $command .= sprintf(" --port %s", $origin['port']);
    }
    if ( array_key_exists('database', $origin ) ) {
        $command .= sprintf(" %s", $origin['database']);
    }

    // it's a shorthand for the following mysqldump's options
    // --add-drop-table --add-locks --create-options --disable-keys --extended-insert --lock-tables --quick --set-charset
    $command .= " --opt";

    if ( array_key_exists('outfile', $origin) ) {
        $command .= sprintf(" > %s", $origin['outfile']);
    }

   return $command;
}



/**
 * Builds the command line for restore a database
 *
 * @param array $destination as follows
 * <code>
 * [
 * 'database' => '',
 * 'username' => '',
 * 'password' => '',
 * 'dir' => ''
 * ]
 * </code>
 *
 * @return string
 */
function buildRestoreCommand( Array $destination ) {

    $baseCommand = "mysql";
    if ( array_key_exists('host', $destination) ) {
        $baseCommand .= sprintf(" -h %s", $destination['host']);
    }

    if ( array_key_exists('username', $destination) ) {
        $baseCommand .= sprintf(" -u %s", $destination['username']);
    }

    if ( array_key_exists('password', $destination ) ) {
        $baseCommand .= sprintf(" -p'%s'", $destination['password']);
    }


    //
    // ---> starts building command
    //
    $command = "";

    //
    // ---> add command to create database
    //
    $command .= $baseCommand;
    if ( array_key_exists('database', $destination) ) {
        $command .= sprintf(" -e 'DROP DATABASE IF EXISTS %s ; CREATE DATABASE %s; ' ; ", $destination['database'],  $destination['database']);
    }
    $command .= " \n";


    //
    // ---> add command to run source file against new database
    //
    $command .= $baseCommand;

    if ( array_key_exists('database', $destination) ) {
        $command .= sprintf(" %s", $destination['database']);
    }

    if ( array_key_exists('source-file', $destination) ) {
        $command .= sprintf(" < %s", $destination['source-file']);
    }

    return $command;
}



function getOutfile() {
    $tempDir = sys_get_temp_dir();

    return tempnam($tempDir, "mysql-migrate");
}

