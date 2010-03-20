<?php
//
// Created on: <14-Apr-2007 08:09:41 gb>
//
// SOFTWARE NAME: updatesearchindex
// COPYRIGHT NOTICE: Copyright (C) 2001-2007 Brookins Consulting
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

/*! \file updatesearchindex.php
*/

include_once( 'lib/ezutils/classes/ezini.php' );

$ini =& eZINI::instance( 'site.ini' );

$DbUser = trim( $ini->variable( 'DatabaseSettings', 'User' ) );
$DbPassword = trim( $ini->variable( 'DatabaseSettings', 'Password' ) );
$DbDatabase = trim( $ini->variable( 'DatabaseSettings', 'Database' ) );
$DbAuth = "--db-user=$DbUser --db-password=$DbPassword --db-database=$DbDatabase";


$searchini =& eZINI::instance( 'updatesearchindex.ini' );

$PhpCmd = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'PhpCmd' ) );
$PhpCmdArg = " --phpcmd='$PhpCmd'";

$IndexScript = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'IndexScript' ) );
$IndexSubScript = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'IndexSubScript' ) );
$IndexSubScriptArg = " --script='$IndexSubScript'";

$SearchSiteAccess = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'SearchSiteAccess' ) );
$PhpMemory = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'PhpMemory' ) );
$Priority = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'Priority' ) );
$DbOptions = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'DbOptions' ) );
$Options = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'Options' ) );
$UseNice = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'UseNice' ) );
$Nice = trim( $searchini->variable( 'UpdateSearchIndexSettings', 'Nice' ) );

if ( $UseNice == 'enabled' )
{
    $Nice = "$Nice -n $Priority";
    $PhpCmd = "$Nice $PhpCmd";
}
if ( $DbOptions == 'disabled' )
{
    $DbOptions = '';
}

// ini debug
// var_dump ($searchini);
// print_r( $PhpCmd );

// Build index command
$cmd = "$PhpCmd -d memory_limit=$PhpMemory -C $IndexScript --siteaccess=$SearchSiteAccess $Options $PhpCmdArg $IndexSubScriptArg $DbAuth $DbOptions";
echo "$cmd;\n\n";

$retval = null;
$result = passthru( $cmd, $retval );

if ( $retval > 0 )
     echo "\nIndex Script Failure ...\n\n";

?>