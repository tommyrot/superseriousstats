SUPERSERIOUSSTATS
=================

superseriousstats is a fast and efficient program to create statistics out of various types of chat logs.

Features
--------

* Cleanly written in PHP using strict typing
* Incremental processing of logs, progress being stored in an SQLite database
* Generates a static HTML page without dependencies or embedded scripts
* Optional dynamic pages for historical and user stats
* Tiny configuration file with as little as three required settings

Demo
----

Have a look at this [live demo](https://sss.dutnie.nl) which uses default settings.

---

Installation
============

The following five steps quickly go over setting up superseriousstats with full functionality. For more detailed notes be sure to check out [FAQ.md](FAQ.md).

First, clone or download the git repository (green button) to a place on your machine which we will call `$repo` from here on.

1 - Logs
--------

Use a separate directory for each channel you are logging and write the logs one file per day, having the full date (Ymd or Y-m-d) in the filename. E.g. `~/irclogs/my_channel/%Y%m%d.log`.

The logfile parsers can be found in `$repo/parsers`. Currently, the following formats are supported: eggdrop, hexchat (xchat), irssi (smuxi), limechat, mirc, nodelog, supybot, textual, weechat, znc (muh2).

2 - Environment
---------------

Install the latest version of SQLite3 along with both PHP cli and PHP fpm-fcgi version 7.4 or later. The cli runtime should have the `mbstring`, `sqlite3` and `zlib` modules compiled in, fpm-fcgi should contain `mbstring` and `sqlite3`. Confirm by running `php -v && php -m`, `php-fpm -v && php-fpm -m`. Ensure you have a working webserver that is configured to handle PHP pages.

Create a dedicated directory to serve stats pages from for the channel. Copy ***only the following*** files from `$repo` to that directory `cp banner.png bg.png common* favicon.ico history.php sss.css user.php /path/to/www/my_channel`.

3 - Configure
-------------

From `$repo` run the following command to create an empty database `cat empty_database_v8.sqlite | sqlite3 /path/to/www/databases/my_channel.db`. Copy `sss.conf` to `my_channel.conf` and edit its contents. Then open up `/path/to/www/my_channel/common_user_history.php` and change the value `%CHANGEME%` into the full path to `/path/to/www/databases/my_channel.db` relative to the chroot/jail your webserver operates in.

4 - Parse Logs
-------------

`php -c my_channel.conf -i ~/irclogs/my_channel/`


5 - Create Stats
----------------

`php -c my_channel.conf -o /path/to/www/my_channel/index.html`

---

Reporting Problems
==================

Bugs
----

***Please*** report bugs by openening an issue here on github. I prioritize fixing bugs above all else. Provide logs whenever possible so i have something to work with (chat logs and/or verbose output from `php sss.php -v ...`).

Everything Else
---------------

For everything beside bugs you may find us on IRC in **#superseriousstats** on chat.**freenode**.net. Feel free to ask, talk about features, or help others get up and running. When it comes to adding support for new logging formats i expect you to provide enough information to extract the full syntax. Discord, slack, teams, we could make it all happen. Just keep in mind that my personal todo list is already filled to the brim and i don't have unlimited time to work on things by myself. One particular feature i have in mind requires some artwork to be commissioned.

---

Supporting the Project
======================

You're very welcome to help the project grow! This is best done by using superseriousstats and having fun with it, but also;

* Star this repo
* Share with a friend
* Buy me a coffee/beer
* ...
