Downloading superseriousstats
=============================

Just clone or download [the git repository](https://github.com/tommyrot/superseriousstats). This is a mirror of my local repo and aims to always be in a working state. Be mindful that if the **major** version changes (e.g. ***7***.1 => ***8***.0) it means that the database layout has changed. You will need to create a fresh empty database from the template and parse all your logs again. Migrate your channel's user relations with the `sss.php -e` and `sss.php -m` commands as outlined in [the MANUAL](MANUAL).

---

Supported Logfile Formats
=========================

Some clients log in a way which is unsuitable for parsing; multiple types of lines have the same syntax meaning they can't be properly categorized. This often comes down to normal or action lines being able to "spoof" any other type of line.

Unsupported logfile formats can probably be added to superseriousstats granted you provide all the neccessary information to extract the full syntax from.

---

Installation
============

Logs
----

One directory per channel, one log per day. The logfiles should have a date in their filename in the format Ymd or Y-m-d.

    #my_channel.1999-12-31.log
    1999-12-31.txt
    19991231

***Have a backup of your logs at a safe place.*** You may at some point decide to rebuild the database from scratch for whatever reason.

---

Compiling PHP
-------------

Easiest is to just grab the SQLite, zlib and mbstring/oniguruma dependencies from your distro's package repository.

If you opt to compile PHP yourself here's how i do it, for cli:

    cd /tmp
    wget https://www.php.net/distributions/php-7.4.7.tar.gz
    tar -zxvf php-7.4.7.tar.gz
    cd php-7.4.7
    ./configure --disable-all --disable-phpdbg --disable-ipv6 --disable-cgi --with-sqlite3 --enable-mbstring --with-zlib
    make
    cd sapi/cli
    ./php -v
    ./php -m

*If you really don't want zlib (and don't mind losing the ability to parse gzipped logs) you can compile without it and remove the extension check in `sss.php` manually.*

And for fpm-fcgi:

    cd /tmp
    wget https://www.php.net/distributions/php-7.4.7.tar.gz
    tar -zxvf php-7.4.7.tar.gz
    cd php-7.4.7
    ./configure --disable-all --disable-phpdbg --disable-ipv6 --disable-cgi --with-sqlite3 --enable-mbstring --disable-cli --enable-fpm
    make
    cd sapi/fpm
    ./php-fpm -v
    ./php-fpm -m

Webserver Configuration
-----------------------

The complete list of files and directories you should have on your webserver:

| File | Considerations | Accessibility |
|------|----------------|------------|
| banner.png | only if `show_banner = "true"` in `sss.conf` | global |
| bg.png | only if `show_banner = "true"` in `sss.conf` | global |
| common.php | only when serving `user.php` and/or `history.php` | local |
| common_html_history.php | only when serving `history.php` | local |
| common_html_user.php | only when serving `user.php` | local |
| common_user_history.php | only when serving `user.php` and/or `history.php` | local |
| favicon.ico | optional | global |
| history.php | optional | global |
| *index.html* | this is the static output file from `sss.php -o` | global |
| sss.css | | global |
| user.php | optional | global |
| userpics/ | optional | global |

php-fpm.ini, php.ini and other webserver settings are beyond the scope of this FAQ. That said, you might want to put `pcre.jit = 0` in your webserver's php.ini if you experience errors related to it.

---

Running the Program
===================

Refer to [MANUAL](MANUAL).

To automate parsing and generation via an hourly cronjob:

    crontab -e
    5 * * * * /usr/local/bin/php /home/you/superseriousstats/sss.php -q -i /home/you/irclogs/my_channel -o /var/www/my_channel/index.html

Use `-c my_channel.conf` if your config file is not `sss.conf`.

Deactivate (Hide) Invalid TLDs
------------------------------

When the maintenance routines of superseriousstats run they will check for the file `tlds-alpha-by-domain.txt` and "disable" all URLs in the database which don't have a TLD from that list. This feature is optional and you can disable it by deleting aforementioned file. I would keep it enabled and automatically update the list:

    crontab -e
    0 6 * * * /usr/local/bin/curl -s http://data.iana.org/TLD/tlds-alpha-by-domain.txt -o /home/you/superseriousstats/tlds-alpha-by-domain.txt

---

Managing Users
==============

Refer to [MANUAL](MANUAL).

By default nicks are automatically linked on some light rules that generally result in near zero false positives. This can be disabled in the config by putting `auto_link_nicks = "false"`.

To manually alter user relationships first create a (CSV) dump with `sss.php -e`, then open up the file with your editor of choice. The syntax is as follows:

    1,Bob,Bob2
    1,Jane,Jane^
    1,Wall-E,WallE
    *,Bobzzz,PartyBob,SPAMGUY,randomguy

These are the nicks superseriousstats found and automatically linked. The first value is the user status, it can be either:

    1 = Registered
    3 = Bot
    4 = Excluded
    * = Unlinked

All nicks on the same line belong to the same user. First in the list is the nick with the most lines typed. It will be the user's main nick, they will be referred to by this particular nick in the stats. All other nicks are considered aliases of the user.

Let's say you want to change a user to a **bot**. Just change the 1 into a 3. If you want Bobzzz and PartyBob to become aliases of Bob, put these nicks on the same line as Bob. Easy. To exclude certain nicks, like SPAMGUY, put their nick on a seperate line prefixed with a 4. We now have:

    1,Bob,Bob2,Bobzzz,PartyBob
    1,Jane,Jane^
    3,Wall-E,WallE
    4,SPAMGUY
    *,randomguy

Save your changes and run `sss.php -m` to import them. The line(s) beginning with an asterisk will be ignored when importing this file.

---

User Pictures
=============

You can put 90x90 pixel pictures, or avatars, of users in the `/userpics` dir on you webserver. The filename should be an exact match of one of the aliases of a user and the extension one of; bmp, gif, jpg, jpeg, png.

If `userpics_default` is set in your config it will default to that if there is no picture found for a user.
