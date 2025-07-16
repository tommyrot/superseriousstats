![superseriousstats](banner.svg)

**A fast and efficient program to create statistics out of IRC chat logs.**

---

**Features**

- Cleanly written in PHP.
- Incremental processing of logs; progress is stored in an SQLite database.
- Generates a static HTML page without dependencies or embedded scripts.
- Optional dynamic pages for historical and user stats.
- Includes dark mode for all pages ([preview](https://raw.githubusercontent.com/tommyrot/superseriousstats/refs/heads/master/.github/themes.png)).
- Small configuration file with sane defaults and only a few required settings.

---

**Demos**

- [#ircv3](https://sss.dutnie.nl/ircv3/) (defaults settings)
- [#openbsd](https://sss.dutnie.nl/openbsd/) (XXL tables)

---

**Support 😻**

- Star ⭐ this repo!
- Donate BTC to `14Nd9sTUfQ88SfsazL5GJ99JjtaKGpS2Kx` 🍺🍕

---

**About the Project**

Started in 2002 as a fun way to visualize IRC chats between friends, this project has evolved from a single static page into a dynamic *piece of art* with user profiles and a browsable history. It’s released under a permissive, free, and open license so like-minded people can enjoy it as they wish. I consider the project feature-complete, though there will always be ideas floating around in my mind.

---

**Installation**

First, fullfil the following prerequisits:

- SQLite3.
- PHP 8.2+ CLI and FPM with **mbstring** and **sqlite** modules compiled in (optionally the **zlib** module if you want to parse gzipped logs, CLI only).
- A configured webserver.

Next, get the latest superseriousstats source via git or download the zipped archive.

1. Set up an empty database for your channel:

   ```
   $ cat sqlite_schema.sql | sqlite3 /path/to/www/databases/my_channel.db
   ```

2. Create a directory for your webserver to serve stats from and copy the necessary files to it:

   ```
   $ cp common* favicon.svg history.php sss.css user.php web.php /path/to/www/my_channel
   ```

   In the destination directory edit `web.php` and change the string `%CHANGEME%` to the location of the database you created in the previous step. *(NB: the location should be relative to the chroot jail your webserver operates in, if applicable)*.

3. Edit `sss.conf`. The available logfile parsers can be found in the `superseriousstats/parsers/` directory. Currently the following formats are supported: catgirl, eggdrop, hexchat (xchat), irssi (smuxi), limechat, mirc, nodelog, supybot, textual, thelounge, weechat, znc (muh2). Put the appropriate parser name in the config, e.g. `parser_eggdrop`.

Note that all logfiles **must** be written as one file per day and **must** have a date in their filename; either 'Ymd' or 'Y-m-d'. Additionally, each channel **must** be logged to a separate directory. Lastly, all parseable lines and events must be prefixed by a 24-hour clock timestamp. For example: `~/irclogs/my_channel/#my_channel.1999-12-31.log`.

4. The easiest way to keep your stats up to date is to make a cron job:

```
   $ crontab -e
   @hourly /usr/local/bin/php /path/to/superseriousstats/sss.php -qi /path/to/irclogs/my_channel/ \
   -o /path/to/www/my_channel/index.html
   ```

   For all options refer to the [manual](../MANUAL).

---

**Frequently Asked Questions**

***Q: How do i link different nicks to a single user? Or tag a bot? Can i hide a user from the stats?***

By default nicks are automatically linked in a simple manner that should result in minimal false positives. This behavior can be disabled in the config file by setting `auto_link_nicks = "false"`. To manually alter user relations, first create an export of all existing ones with `sss.php -e <file>`. This file can then be edited and imported back into the database.

Here is some example output with all nicks superseriousstats found and linked for a fictional channel:

```
1,Bob,Bob2
1,Jane,Jane^
1,Wall-E,WallE
*,Bobzzz,PartyBob,SPAMGUY,randomguy
```

The first value in each comma separated list is the user **status**, it can be either; 1 = registered, 3 = bot, 4 = excluded, * = unlinked. All nicks on the same line belong to the same user. First in the list is the nick with the most lines typed. It will be the user's main nick, they will be referred to by this particular nick in the stats. All ensuing nicks are considered aliases of that user.

*The nick with the most lines typed will always automatically become the main nick for a user, on every stats update. This behaviour cannot be changed.*

Let's say you want to change a user into a bot. Just change the 1 into a 3. If you want Bobzzz and PartyBob to become aliases of Bob, put these nicks on the same line as Bob. Easy. To exclude certain nicks, like SPAMGUY, put their nick on a seperate line prefixed with a 4. We now have:

```
1,Bob,Bob2,Bobzzz,PartyBob
1,Jane,Jane^
3,Wall-E,WallE
4,SPAMGUY
*,Bobzzz,PartyBob,SPAMGUY,randomguy
```

Save your changes and run `sss.php -m <file>` to import them. The line(s) beginning with an asterisk will be ignored so don't worry about those.

*Be aware that wildcards will not work in this file and made-up nicks will not be recognized either. If you plan to manually take control of user relations it is strongly advised to backup your efforts!*

***Q: Can users have avatars?***

Yes, on their profile page (user.php). First create a directory on your webserver:

```
$ mkdir /path/to/www/my_channel/userpics
```

Put any avatars in this directory and name them exactly as one of the aliases of the user they belong to. The extension can be one of; bmp, gif, jpg, jpeg, png and svg. Avatars are displayed with a 80x80 pixel dimension so for optimal scaling use a 1:1 aspect ratio for them.

In order to display a picture for users who don't have an avatar in aforementioned directory, use the `userpics_default` setting in your config file.

***Q: What is TLD validation?***

As you might have noticed there is a file called [tlds-alpha-by-domain.txt](https://raw.githubusercontent.com/tommyrot/superseriousstats/master/tlds-alpha-by-domain.txt) in the repository. This is a database of all valid TLDs (Top Level Domains) on the internet. By default superseriousstats flags all URLs with an invalid TLD as inactive so they won't show up in the stats. These hyperlinks normally lead nowhere so there is no point to include them.

To disable this behaviour just delete the file and superseriousstats will skip the validation and unflag all existing URLs.

If you want to keep the TLD database up to date here is an easy way:

```
$ crontab -e
@weekly /usr/local/bin/curl -s http://data.iana.org/TLD/tlds-alpha-by-domain.txt \
-o /path/to/superseriousstats/tlds-alpha-by-domain.txt
```

***Q: My channel was logged by different clients in the past, problem?***

This is not a problem. Take into account that logs must always be parsed in chronological order.

Working from oldest to newest, put all continuous logs with the same syntax in a separate directory. Edit your config file and set the correct parser for this batch. Now parse all logs in that directory. Next, edit your config file again and set the parser for the following set of logs. Parse the appropriate directory. Repeat this procedure as many times as necessary.

***Q: I have just one big log, now what?***

You will have to find a way to split it into separate per day logfiles. This is beyond the scope of this project.

***Q: The syntax of my logs is not recognized?***

The parsers that come with superseriousstats are made to work with the default logging syntax of each supported IRC client. When you customize your client, with a theme for instance, it sometimes causes the logging syntax to change as well and the parser won't recognize it anymore. This obviously is out of my control and therefore not supported. Feel free to open an issue if this is not the case.

***Q: What is the recommended procedure for upgrading?***

Make sure you have all your chat logs at hand and decide if you want to make an export of the user relations for the channel. Delete the current database and redo *step 1* (create an empty database for your channel) of the installation checklist. Now parse all logs like you would normally do and import the user relations if you made a backup earlier. Done.

***Q: Is there a mobile friendly version of the program?***

No, although good mobile web browsers are sufficiently capable to render the output pages in desktop mode.
