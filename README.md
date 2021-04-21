![superseriousstats](banner.svg)

superseriousstats is a fast and efficient program to create statistics out of various types of chat logs.

### Features

* Cleanly written in PHP8 using strict typing
* Incremental processing of logs, progress being stored in an SQLite database
* Generates a static HTML page without dependencies or embedded scripts
* Optional dynamic pages for historical and user stats
* Tiny configuration file with as little as three required settings

[artist's impression](/.github/example.png)

---
### Installation

Get the latest stable source:

```
$ git clone --depth 1 --branch stable https://github.com/tommyrot/superseriousstats.git
```

Go down the checklist:

1. Install the latest version of [SQLite3](https://www.sqlite.org)
2. Install [PHP8](https://www.php.net) cli and a webserver along with PHP8 fpm-fcgi, confirm that both PHP8 cli and fcgi-fpm have the `mbstring` and `sqlite3` modules compiled in.

    *If you plan on parsing gzipped logs then PHP8 cli must have the `zlib` module compiled in as well.*
3. Check if a parser for your logs exists in `superseriousstats/parsers/`. Logfiles must be written as one file per day in a single directory for just that channel. All logs must have a date in their filename, either 'Ymd' or 'Y-m-d', for example: `~/irclogs/my_channel/#my_channel.1999-12-31.log`. All parseable lines and events should be prefixed by a 24-hour clock timestamp.

    *Currently, the following formats are supported: catgirl, eggdrop, hexchat (xchat), irssi (smuxi), limechat, mirc, nodelog, supybot, textual, thelounge, weechat, znc (muh2).*
4. Set up an empty database for your channel:
    ```
    $ cat sqlite_schema.sql | sqlite3 /path/to/www/databases/my_channel.db
    ```
5. Create a dedicated directory for your webserver to serve stats from and copy the following files to it:
    ```
    $ cp banner* common* favicon.svg history.php sss.css user.php web.php /path/to/www/my_channel
    ```
    Now edit `/path/to/www/my_channel/web.php` and change the value `%CHANGEME%` to `/path/to/www/databases/my_channel.db` (relative to the chroot/jail your webserver operates in, if applicable).
6. Finally, go over `sss.conf` and you're all set!

---
### Usage

The simplest way to keep your stats up to date is to make a cron job:
```
$ crontab -e
@hourly /usr/local/bin/php /path/to/superseriousstats/sss.php -qi /path/to/irclogs/my_channel/ -o /path/to/www/my_channel/index.html
```
For all options refer to the [MANUAL](https://raw.githubusercontent.com/tommyrot/superseriousstats/master/MANUAL)

---
### Bugs, Features and Discussion

Find me and others on IRC in #superseriousstats on chat.freenode.net. All project related talk is welcomed there. Please report any bugs you may find, given enough details i usually have a patch out in a couple of days.

---
### Supporting the Project

I started this project back in 2002 as a fun way to give some insight into the chat patterns and such of me and my friends on IRC. Over the years it evolved from a single static page to a dynamic *piece of art ;-)* containing user profiles and a browsable history. I released the source so others can enjoy it as well. The project isn't done. I'm always connected to IRC and there are still multiple ideas on my TODO.

In the meantime, have fun with superseriousstats and if you can, give this repo a star to increase the chances others will discover it too. Support by means of a donation is also very much appreciated and can be done via Bitcoin to the following address:

```
Bitcoin: 14Nd9sTUfQ88SfsazL5GJ99JjtaKGpS2Kx
```

---
## FAQ

### How do i link different nicks to a single user?

By default nicks are automatically linked in a simple manner that should result in near zero false positives. This behavior can be disabled in the config by setting `auto_link_nicks = "false"`

To manually alter user relationships first create an export of all existing ones with `sss.php -e <file>` This file can be edited and imported back into the database to make changes take effect.

The syntax is as follows:

    1,Bob,Bob2
    1,Jane,Jane^
    1,Wall-E,WallE
    *,Bobzzz,PartyBob,SPAMGUY,randomguy

These are the nicks superseriousstats found and automatically linked. The first value is the user status, it can be either:

    1 = Registered
    3 = Bot
    4 = Excluded
    * = Unlinked

All nicks on the same line belong to the same user. First in the list is the nick with the most lines typed. It will be the user's main nick, they will be referred to by this particular nick in the stats. All other nicks are considered aliases of that user.

Let's say you want to change a user to a bot. Just change the 1 into a 3. If you want Bobzzz and PartyBob to become aliases of Bob, put these nicks on the same line as Bob. Easy. To exclude certain nicks, like SPAMGUY, put their nick on a seperate line prefixed with a 4. We now have:

    1,Bob,Bob2,Bobzzz,PartyBob
    1,Jane,Jane^
    3,Wall-E,WallE
    4,SPAMGUY
    *,randomguy

Save your changes and run `sss.php -m <file>` to import them. The line(s) beginning with an asterisk will be ignored when importing this file.

*Wildcards will not work in this file. Made-up nicks will not be recognized. If you plan to manually take control of user relationships it is strongly advised to backup your work!*

### Can users have avatars?

Yes, but currently only on their profile page (user.php).

Create a directory on your webserver:

```
$ mkdir /path/to/www/my_channel/userpics
```

Put your avatars in this directory and name them exactly as one of the aliases of the user they belong to. The extension can be one of; bmp, gif, jpg, jpeg, png and svg. Avatars are displayed with a 80x80 pixel dimension so for optimal scaling use a 1:1 aspect ratio for your pictures.

If `userpics_default` is set in your config it will default to that avatar if there is no picture found for a user, example: `userpics_default = "favicon.svg"`

### What is TLD validation?

As you might have noticed there is a file called [tlds-alpha-by-domain.txt](https://raw.githubusercontent.com/tommyrot/superseriousstats/master/tlds-alpha-by-domain.txt) in the repository. This is a database of all valid TLDs at that time. By default superseriousstats flags all URLs with an invalid TLD as inactive so they won't show up in the stats. These hyperlinks normally lead nowhere so there is no point to include them.

To disable this behaviour just delete the file and the program will skip the check.

If you want to keep the TLD database updated, here is an easy way:

```
$ crontab -e
@weekly /usr/local/bin/curl -s http://data.iana.org/TLD/tlds-alpha-by-domain.txt -o /path/to/superseriousstats/tlds-alpha-by-domain.txt
```

### My channel was logged by different clients in the past, problem?

This is not a problem. Take into account that logs must always be parsed in chronological order.

Working from oldest to newest, put all logs with the same syntax in a separate directory. Edit your config and set the correct parser. Now parse all logs in that directory. Next, edit your config again and set the parser for the following set of logs. Parse the next directory. Repeat as many times as necessary.

### I have just one big log, now what?

Sorry but you will have to find a way to untangle it into separate per day logfiles yourself.. :-(

### The syntax of my logs is not recognized?

The parsers that come with superseriousstats are made for the default syntax of each supported client. When you customize your client it can happen that the parser won't work anymore. In this case you are on your own, obviously. Otherwise create an Issue or drop by on IRC (preferably).
