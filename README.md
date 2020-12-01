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

The following five steps quickly go over setting up superseriousstats with full functionality. For more detailed notes be sure to [check out the FAQ](FAQ.md).

First, clone or download the git repository (green button) to a place on your machine which we will call `$repo` from here on out.

1 - Logs
--------

Use a separate directory for each channel you are logging and write the logs one file per day, having the full date (Ymd or Y-m-d) in the filename. E.g. `~/irclogs/my_channel/%Y%m%d.log`.

The logfile parsers can be found in `$repo/parsers`. Currently, the following formats are supported: eggdrop, hexchat (xchat), irssi (smuxi), limechat, mirc, nodelog, supybot, textual, weechat, znc (muh2).

2 - Environment
---------------

Install the latest version of SQLite3 along with both PHP cli and PHP fpm-fcgi version 8.0 or later. The cli runtime should have the `mbstring`, `sqlite3` and `zlib` modules compiled in, fpm-fcgi should contain `mbstring` and `sqlite3`. Ensure you have a working webserver which is configured to handle PHP pages.

Create a dedicated directory to serve stats pages for the channel. Copy ***only the following*** files from `$repo` to that directory `cp banner.png bg.png common* favicon.ico history.php sss.css user.php /path/to/www/my_channel`.

3 - Configure
-------------

From `$repo` run the following command to create an empty database `cat empty_database_v8.sqlite | sqlite3 /path/to/www/databases/my_channel.db`. Edit `sss.conf` to fit your needs. Then open up `/path/to/www/my_channel/common_user_history.php` and change the value `%CHANGEME%` into the full path to `/path/to/www/databases/my_channel.db` relative to the chroot/jail your webserver operates in.

4 - Parse Logs
-------------

`php sss.php -i ~/irclogs/my_channel/`


5 - Create Stats
----------------

`php sss.php -o /path/to/www/my_channel/index.html`

---

Bugs, Features and Discussion
=============================

Find me and others on IRC in **#superseriousstats** on *chat.freenode.net*. All project related talk is welcomed there. Please report any bugs you may find, given enough details i usually have a patch out in a couple of days.

---

Supporting the Project
======================

I started this project back in 2002 as a fun way to give some insight into the chat patterns and such of me and my friends on IRC. Over the years it evolved from a single static page to a dynamic *piece of art* containing user profiles and a browsable history. I released the source so others can enjoy it as well. The project isn't done. I'm always connected to IRC and there are still multiple ideas on my TODO.

In the meantime, have fun with superseriousstats and if you can, give this repo a star to increase the chances others will discover it too. Support by means of a donation is also very much appreciated and can be done via Bitcoin to the following address:

```
Bitcoin: 14Nd9sTUfQ88SfsazL5GJ99JjtaKGpS2Kx
```
