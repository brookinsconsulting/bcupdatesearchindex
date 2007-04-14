#!/bin/bash

#
# Created on: <14-Apr-2007 07:29:13 gb>
#
# SOFTWARE NAME: updatesearchindex
# COPYRIGHT NOTICE: Copyright (C) 2001-2007 Brookins Consulting
# SOFTWARE LICENSE: GNU General Public License v2.0
# NOTICE: >
#   This program is free software; you can redistribute it and/or
#   modify it under the terms of version 2.0  of the GNU General
#   Public License as published by the Free Software Foundation.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of version 2.0 of the GNU General
#   Public License along with this program; if not, write to the Free
#   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
#   MA 02110-1301, USA.
#
#

#################################################
date;

# Settings

# Database
DBNAME='nextgen';
DBUSER='nextgen';
DBPASSWORD='nextgen';
DB_AUTH="--db-user=$DBUSER --db-password=$DBPASSWORD --db-database=$DBNAME";

# DB_OPTIONS="--sql --clean";
DB_OPTIONS="";

# Options
OPTIONS='--logfiles --verbose';

# Siteaccess
SITEACCESS='ezwebin_site_user';

# Nice Level
PRIORITY='2';

# Nice
NICE="/bin/nice -n $PRIORITY";

# Memory Limit
PHP_MEMORY='1252M';

# PHP
# PHP='/usr/local/php-cli-4-4-4-refcount/bin/php';
PHP_CMD='/usr/bin/php';
PHP="$NICE $PHP_CMD";

# Scripts
INDEX_SCRIPT="extension/updatesearchindex/patch/3.8/update/common/scripts/updatesearchindex.php";
SUBSCRIPT=" --script='extension/updatesearchindex/patch/3.8/update/common/scripts/updatesearchindex_sub.php'";
PHPMEMORYLIMITSTRING=" --phpcmd='$PHP_CMD'";

#################################################
# Current | Run command at lower process priority
# to prevent slow down of primary production
# server durring index process.

CMD="$PHP -d memory_limit=$PHP_MEMORY -C $INDEX_SCRIPT -s $SITEACCESS $OPTIONS $PHPMEMORYLIMITSTRING $SUBSCRIPT $DB_AUTH $DB_OPTIONS";

echo "";
echo $CMD;
echo "";
echo "";

$PHP -d memory_limit=$PHP_MEMORY -C $INDEX_SCRIPT -s $SITEACCESS $OPTIONS $PHPMEMORYLIMITSTRING $SUBSCRIPT $DB_AUTH $DB_OPTIONS;

#################################################
date;

