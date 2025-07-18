![superseriousstats](banner.svg)

---

**A fast and efficient program to create statistics out of IRC chat logs.**

## Features

- Cleanly written in PHP.
- Incremental log processing with progress stored in an SQLite database.
- Generates a static HTML page with no dependencies or embedded scripts.
- Optional dynamic (.php) pages for historical and user stats.
- Built-in dark mode for all pages ([preview](https://raw.githubusercontent.com/tommyrot/superseriousstats/refs/heads/master/.github/themes.png)).
- Small config file with sane defaults and only a few required settings.

## Demos

- [#ircv3](https://stats.dutnie.nl/ircv3/) (default settings)
- [#openbsd](https://stats.dutnie.nl/openbsd/) (XXL tables)

## Support 😻

- Star ⭐ this repo!
- Donate BTC to `14Nd9sTUfQ88SfsazL5GJ99JjtaKGpS2Kx` 🍺🍕

## About the Project

Started in 2002 as a fun way to visualize IRC chats between friends, this project has evolved from a single static page into a dynamic ***piece of art*** with user profiles and a browsable history. It's released under a permissive, free, and open license so like-minded people can enjoy it as they wish. I consider the project feature-complete, though there will always be ideas floating around in my mind.

## Installation

### Prerequisites:

- Recent version of SQLite3
- PHP 8.2+ CLI and FPM with the following modules:
  - `mbstring`
  - `sqlite`
  - `zlib` (CLI only, for gzipped logs)
- Properly configured web server

---

### 1. Get the latest stable source from GitHub
```
git clone https://github.com/tommyrot/superseriousstats.git
```
Or download the zipped archive and extract it.

---

### 2. Create an empty database for your channel
```
cat sqlite_schema.sql | sqlite3 /path/to/www/databases/my_channel.db
```
Change to the actual path and chosen db name.

---

### 3. Copy files to the web directory
```
cp common* favicon.svg history.php sss.css user.php web.php /path/to/www/my_channel
```
Change the path as needed.

---

### 4. Edit the copied `web.php` file
```
$database = '%CHANGEME%';
```
Change to the actual path of the db. Note: make sure the path is relative to the server's chroot environment.

---

### 5. Go over the config file `sss.conf`
```
parser_name = "parser_weechat"  # Example; choose appropriate parser (see below)
```
Supported formats can be found in the `parsers/` directory: catgirl, Eggdrop, HexChat (XChat), Irssi (Smuxi), LimeChat, mIRC, nodelog, Supybot, Textual, The Lounge, WeeChat, ZNC (muh2). Specify the filename without the extension.

Note: use one logfile per day, with the date in the filename (either Ymd or Y-m-d format), and store logs for each channel in a separate directory. Example: `~/irclogs/my_channel/#my_channel.1999-12-31.log`. All parseable lines and events must begin with a 24-hour timestamp.

---

### 6. Set up a cron job to create stats every hour
```
$ crontab -e

@hourly /usr/local/bin/php /path/to/superseriousstats/sss.php \
-qi /path/to/irclogs/my_channel/ \
-o /path/to/www/my_channel/index.html
```
Change paths accordingly.

---

### 7. Manual

For more detailed instructions, refer to the [manual](https://raw.githubusercontent.com/tommyrot/superseriousstats/refs/heads/master/MANUAL).

---

## Frequently Asked Questions

### Q: How do I link different nicks to a single user, tag a bot, or hide a user from the stats?

By default, superseriousstats automatically links nicks in a simple manner that should result in minimal false positives. This behavior can be disabled by setting `auto_link_nicks = "false"`. To manually manage user relations, first create an export of all existing nicks by running `sss.php -e <file>`. This file can then be edited and imported back into the database.

Here is an example export for a fictional channel:
```
1,Bob,Bob2
1,Jane,Jane^
1,Wall-E,WallE
*,Bobzzz,PartyBob,SPAMGUY,randomguy
```
Each line is a comma-separated list of nicks that belong to the same user. The first value is the user status:
- `1` = registered user
- `3` = bot
- `4` = excluded
- `*` = unlinked

The first nick in the list is the one with the most lines typed and becomes the main nick shown in the stats. *This is automatically recalculated with every stats update and cannot be changed.*

To group nicks, place them on the same line with the desired status. For example:
```
1,Bob,Bob2,Bobzzz,PartyBob
1,Jane,Jane^
3,Wall-E,WallE
4,SPAMGUY
```
- This groups Bobzzz and PartyBob under Bob
- Tags Wall-E as a bot
- Hides SPAMGUY from the stats

Save your changes and run `sss.php -m <file>` to import them. Lines starting with an asterisk are ignored on import, no need to remove them.

> ⚠️ Wildcards won't work in this file, and made-up nicks will be ignored. If you're manually managing user relations, it is strongly advised to backup your efforts!

---

### Q: How do I add avatars to users' profile pages `user.php`?

Create a `userpics` directory on your web server, using the appropriate path:
```
mkdir /path/to/www/my_channel/userpics
```
Place avatar images in this directory, named after one of the user's aliases. The file extension must be one of: bmp, gif, jpg, jpeg, png, or svg.

---

### Q: What is TLD validation?

URLs with an invalid TLD (top-level domain) are flagged as inactive and excluded from the stats. Valid TLDs are listed in [tlds-alpha-by-domain.txt](https://raw.githubusercontent.com/tommyrot/superseriousstats/master/tlds-alpha-by-domain.txt), included in the repository.

To disable this feature, delete the aforementioned file, and superseriousstats will skip validation and unflag all existing URLs.

Automatically keep the TLD file up to date:
```
$ crontab -e

@weekly /usr/local/bin/curl -s http://data.iana.org/TLD/tlds-alpha-by-domain.txt \
-o /path/to/superseriousstats/tlds-alpha-by-domain.txt
```
Change paths accordingly.

---

### Q: My channel was logged by different clients in the past. How do I parse them?

It is important to parse logs in chronological order. Working from oldest to newest, put all continuous logs with the same syntax in a separate directory. Edit `sss.conf` and set the correct parser for this batch. Now parse all logs in the directory. Next, edit the config file again and set the parser for the next set of logs and parse them. Repeat this procedure as many times as necessary.

---

### Q: I have just one big log file. What should I do?

You will have to split it into daily logfiles before parsing.

---

### Q: The syntax of my logs is not recognized..

First, make sure it's not caused by your client's theming. If the issue persists, feel free to open an issue on GitHub and include a sample log.

---

### Q: What is the recommended procedure for upgrading?

The clean way is to make a new, empty database and re-parse all logfiles. Before doing so, export the user relations for the channel, which can be imported again afterwards.

---

### Q: Is there a mobile-friendly version?

No, but modern mobile browsers can render the output pages well in desktop mode.
