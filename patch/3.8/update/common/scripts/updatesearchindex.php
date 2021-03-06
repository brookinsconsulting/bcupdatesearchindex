#!/usr/bin/env php
<?php
//
// Created on: <28-Nov-2002 12:45:40 bf>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.9.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
// 
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
// 
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
// 
// 
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

set_time_limit( 0 );

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );

$cli =& eZCLI::instance();
$endl = $cli->endlineString();

$script =& eZScript::instance( array( 'description' => ( "Alternate eZ publish search index updater.\n\n" .
                                                         "Goes trough all objects and reindexes the meta data to the search engine ... slowly" .
                                                         "\n" .
                                                         "updatesearchindex.php"),
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[phpcmd:][script:][db-host:][db-user:][db-password:][db-database:][db-type:|db-driver:][sql][clean]",
                                "",
                                array( 'phpcmd' =>  "Path to php cli binary",
                                       'script' =>  "Path to secondary index update script",
                                       'db-host' => "Database host",
                                       'db-user' => "Database user",
                                       'db-password' => "Database password",
                                       'db-database' => "Database name",
                                       'db-driver' => "Database driver",
                                       'db-type' => "Database driver, alias for --db-driver",
                                       'sql' => "Display sql queries",
                                       'clean' =>  "Remove all search data before beginning indexing"
                                       ) );
$script->initialize();

$PHP_COMMAND = $options['phpcmd'] ? $options['phpcmd'] : false;
// $PHP_COMMAND="'$PHP_COMMAND'";

$PHP_SCRIPT = $options['script'] ? $options['script'] : false;
// $PHP_SCRIPT="'$PHP_SCRIPT'";

/* $PHP_COMMAND='/usr/local/php-cli-4-4-4-refcount/bin/php -d memory_limit=456M';
   $PHP_SCRIPT='/web/pro/prosoft.com/extension/prosoftdesign/update/common/scripts/updatesearchindex2_sub.php'; */

$dbUser = $options['db-user'] ? $options['db-user'] : false;
$dbPassword = $options['db-password'] ? $options['db-password'] : false;
$dbHost = $options['db-host'] ? $options['db-host'] : false;
$dbName = $options['db-database'] ? $options['db-database'] : false;
$dbImpl = $options['db-driver'] ? $options['db-driver'] : false;
$showSQL = $options['sql'] ? true : false;
$siteAccess = $options['siteaccess'] ? $options['siteaccess'] : false;
$cleanupSearch = $options['clean'] ? true : false;

if ( $siteAccess )
{
    changeSiteAccessSetting( $siteaccess, $siteAccess );
}

function changeSiteAccessSetting( &$siteaccess, $optionData )
{
    global $isQuiet;
    $cli =& eZCLI::instance();
    if ( file_exists( 'settings/siteaccess/' . $optionData ) )
    {
        $siteaccess = $optionData;
        if ( !$isQuiet )
            $cli->notice( "Using siteaccess $siteaccess for search index update" );
    }
    else
    {
        if ( !$isQuiet )
            $cli->notice( "Siteaccess $optionData does not exist, using default siteaccess" );
    }
}

print( "Starting object re-indexing\n" );

include_once( 'lib/ezutils/classes/ezexecution.php' );
include_once( "lib/ezutils/classes/ezdebug.php" );
include_once( "kernel/classes/ezsearch.php" );

include_once( 'kernel/classes/ezcontentobjecttreenode.php' );

$db =& eZDB::instance();

if ( $dbHost or $dbName or $dbUser or $dbImpl )
{
    $params = array();
    if ( $dbHost !== false )
        $params['server'] = $dbHost;
    if ( $dbUser !== false )
    {
        $params['user'] = $dbUser;
        $params['password'] = '';
    }
    if ( $dbPassword !== false )
        $params['password'] = $dbPassword;
    if ( $dbName !== false )
        $params['database'] = $dbName;
    $db =& eZDB::instance( $dbImpl, $params, true );
    eZDB::setInstance( $db );
}

$db->setIsSQLOutputEnabled( $showSQL );

if ( $cleanupSearch )
{
    print( "{eZSearchEngine: Cleaning up search data" );
    eZSearch::cleanup();
    print( "}$endl" );
}

// Get top node
$topNodeArray = eZPersistentObject::fetchObjectList( eZContentObjectTreeNode::definition(),
                                                      null,
                                                      array( 'parent_node_id' => 1,
                                                             'depth' => 1 ) );
$subTreeCount = 0;
foreach ( array_keys ( $topNodeArray ) as $key  )
{
    $subTreeCount += $topNodeArray[$key]->subTreeCount( array( 'Limitation' => false ) );
}

print( "Number of objects to index: $subTreeCount $endl" );

$i = 0;
$dotMax = 70;
$dotCount = 0;
$limit = 50;

$optionString = '';
foreach ( $options as $key => $option )
{
    if ( $option )
    {
        if ( is_string( $option ) )
        {
            if ( $key != 'phpcmd' and $key != 'script' ){
                $optionString .= "--$key=$option ";
            }
        } else
        {
            $optionString .= "--$key ";
        }
    }
}

// BC
// var_dump ( $optionString );

foreach ( $topNodeArray as $node  )
{
    $offset = 0;
    $subTree = $node->subTree( array( 'Offset' => $offset, 'Limit' => $limit,
                                      'Limitation' => array() ) );
    while ( $subTree != null )
    {
        foreach ( $subTree as $innerNode )
        {
            // BC
            /*
            $object = $innerNode->attribute( 'object' );
            if ( !$object )
            {
                continue;
            }
            */

            $objectID = $innerNode->attribute( 'contentobject_id' );

            $retval = false;

            // BC
            $exec = $PHP_COMMAND . ' -d memory_limit=456M ' . $PHP_SCRIPT .' '. $optionString . $objectID;
            echo "\nExecuting $exec\n";

            $result = passthru( $exec, $retval );

            // BC
            /*
            eZSearch::removeObject( $object );
            eZSearch::addObject( $object );
            */

            // BC
            if ( $retval > 0 )
                echo "\nF(ail?)\n\n";

            ++$i;
            ++$dotCount;
            print( "." );
            if ( $dotCount >= $dotMax or $i >= $subTreeCount )
            {
                $dotCount = 0;
                $percent = (float)( ($i*100.0) / $subTreeCount );
                print( " " . $percent . "%" . $endl );
            }

            // Memory clean-up
            unset( $object );
            unset( $class );
            $GLOBALS['eZContentObjectDataMapCache'] = null;
        }
        echo( "\n" );
        $offset += $limit;
        $subTree = $node->subTree( array( 'Offset' => $offset, 'Limit' => $limit,
                                          'Limitation' => array() ) );
    }
}

print( $endl . "done" . $endl );

$script->shutdown();

?>