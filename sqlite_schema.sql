PRAGMA encoding = 'UTF-8';

BEGIN TRANSACTION;

CREATE TABLE channel_activity (
date TEXT PRIMARY KEY NOT NULL,
l_00 INT NOT NULL DEFAULT 0,
l_01 INT NOT NULL DEFAULT 0,
l_02 INT NOT NULL DEFAULT 0,
l_03 INT NOT NULL DEFAULT 0,
l_04 INT NOT NULL DEFAULT 0,
l_05 INT NOT NULL DEFAULT 0,
l_06 INT NOT NULL DEFAULT 0,
l_07 INT NOT NULL DEFAULT 0,
l_08 INT NOT NULL DEFAULT 0,
l_09 INT NOT NULL DEFAULT 0,
l_10 INT NOT NULL DEFAULT 0,
l_11 INT NOT NULL DEFAULT 0,
l_12 INT NOT NULL DEFAULT 0,
l_13 INT NOT NULL DEFAULT 0,
l_14 INT NOT NULL DEFAULT 0,
l_15 INT NOT NULL DEFAULT 0,
l_16 INT NOT NULL DEFAULT 0,
l_17 INT NOT NULL DEFAULT 0,
l_18 INT NOT NULL DEFAULT 0,
l_19 INT NOT NULL DEFAULT 0,
l_20 INT NOT NULL DEFAULT 0,
l_21 INT NOT NULL DEFAULT 0,
l_22 INT NOT NULL DEFAULT 0,
l_23 INT NOT NULL DEFAULT 0,
l_night INT NOT NULL DEFAULT 0,
l_morning INT NOT NULL DEFAULT 0,
l_afternoon INT NOT NULL DEFAULT 0,
l_evening INT NOT NULL DEFAULT 0,
l_total INT NOT NULL CHECK (l_total > 0)
) WITHOUT ROWID;

CREATE TABLE fqdns (
fid INTEGER PRIMARY KEY,
fqdn TEXT UNIQUE NOT NULL,
tld TEXT NOT NULL,
active BOOLEAN NOT NULL DEFAULT 1 CHECK (active IN (0,1)) --column affinity NUMERIC
);
CREATE INDEX fqdns_tld ON fqdns (tld);
CREATE INDEX fqdns_active ON fqdns (active);

CREATE TABLE parse_history (
date TEXT PRIMARY KEY NOT NULL,
lines_parsed INT NOT NULL CHECK (lines_parsed > 0)
) WITHOUT ROWID;

CREATE TABLE parse_state (
var TEXT PRIMARY KEY NOT NULL,
value TEXT NOT NULL
) WITHOUT ROWID;

CREATE TABLE ruid_activity_by_day ( --materialized view
ruid INT,
date TEXT,
l_night INT,
l_morning INT,
l_afternoon INT,
l_evening INT,
l_total INT,
PRIMARY KEY (ruid, date)
) WITHOUT ROWID;

CREATE TABLE ruid_activity_by_month ( --materialized view
ruid INT,
date TEXT,
l_night INT,
l_morning INT,
l_afternoon INT,
l_evening INT,
l_total INT,
PRIMARY KEY (ruid, date)
) WITHOUT ROWID;

CREATE TABLE ruid_activity_by_year ( --materialized view
ruid INT,
date TEXT,
l_night INT,
l_morning INT,
l_afternoon INT,
l_evening INT,
l_total INT,
PRIMARY KEY (ruid, date)
) WITHOUT ROWID;

CREATE TABLE ruid_events ( --materialized view
ruid INT PRIMARY KEY,
m_op INT,
m_opped INT,
m_voice INT,
m_voiced INT,
m_deop INT,
m_deopped INT,
m_devoice INT,
m_devoiced INT,
joins INT,
parts INT,
quits INT,
kicks INT,
kicked INT,
nickchanges INT,
topics INT,
ex_kicks TEXT,
ex_kicked TEXT
) WITHOUT ROWID;

CREATE TABLE ruid_lines ( --materialized view
ruid INT PRIMARY KEY,
l_00 INT,
l_01 INT,
l_02 INT,
l_03 INT,
l_04 INT,
l_05 INT,
l_06 INT,
l_07 INT,
l_08 INT,
l_09 INT,
l_10 INT,
l_11 INT,
l_12 INT,
l_13 INT,
l_14 INT,
l_15 INT,
l_16 INT,
l_17 INT,
l_18 INT,
l_19 INT,
l_20 INT,
l_21 INT,
l_22 INT,
l_23 INT,
l_night INT,
l_morning INT,
l_afternoon INT,
l_evening INT,
l_total INT,
l_mon_night INT,
l_mon_morning INT,
l_mon_afternoon INT,
l_mon_evening INT,
l_tue_night INT,
l_tue_morning INT,
l_tue_afternoon INT,
l_tue_evening INT,
l_wed_night INT,
l_wed_morning INT,
l_wed_afternoon INT,
l_wed_evening INT,
l_thu_night INT,
l_thu_morning INT,
l_thu_afternoon INT,
l_thu_evening INT,
l_fri_night INT,
l_fri_morning INT,
l_fri_afternoon INT,
l_fri_evening INT,
l_sat_night INT,
l_sat_morning INT,
l_sat_afternoon INT,
l_sat_evening INT,
l_sun_night INT,
l_sun_morning INT,
l_sun_afternoon INT,
l_sun_evening INT,
urls INT,
words INT,
characters INT,
monologues INT,
topmonologue INT,
activedays INT, --additional column created by view
slaps INT,
slapped INT,
exclamations INT,
questions INT,
actions INT,
uppercased INT,
quote TEXT,
ex_exclamations TEXT,
ex_questions TEXT,
ex_actions TEXT,
ex_uppercased TEXT,
lasttalked TEXT
) WITHOUT ROWID;

CREATE TABLE ruid_milestones (
ruid INT NOT NULL REFERENCES uid_details (uid),
milestone INT NOT NULL,
date TEXT NOT NULL,
PRIMARY KEY (ruid, milestone)
) WITHOUT ROWID;

CREATE TABLE ruid_smileys ( --materialized view
ruid INT,
sid INT,
total INT,
PRIMARY KEY (ruid, sid)
) WITHOUT ROWID;
CREATE INDEX ruid_smileys_sid ON ruid_smileys (sid);

CREATE TABLE ruid_urls ( --materialized view
ruid INT,
lid INT,
firstused TEXT,
lastused TEXT,
total INT,
PRIMARY KEY (ruid, lid)
) WITHOUT ROWID;
CREATE INDEX ruid_urls_lid ON ruid_urls (lid);

CREATE TABLE settings (
var TEXT PRIMARY KEY NOT NULL,
value TEXT NOT NULL
) WITHOUT ROWID;

CREATE TABLE smileys (
sid INTEGER PRIMARY KEY,
smiley TEXT UNIQUE NOT NULL,
category TEXT
);
CREATE INDEX smileys_category ON smileys (category);
INSERT INTO smileys (smiley, category) VALUES
(':)', 'smile'),
(';)', 'wink'),
(':(', 'sad'),
(':P', 'silly'),
(':D', 'happy'),
(';(', 'cry'),
(':/', 'concerned'),
('\o/', 'cheer'),
(':))', 'big smile'),
('<3', 'heart'),
(':o', 'surprised'),
('=)', 'smile'),
(':-)', 'smile'),
(':x', 'kiss'),
('=D', 'happy'),
('D:', 'distressed'),
(':|', 'neutral'),
(';-)', 'wink'),
(';p', 'silly'),
('=]', 'smile'),
(':3', 'cute'),
('8)', 'cool'),
(':<', 'sad'),
(':>', 'smile'),
('=p', 'silly'),
(':-P', 'silly'),
(':-D', 'happy'),
(':-(', 'sad'),
(':]', 'smile'),
('=(', 'sad'),
('-_-', 'annoyed'),
(':S', 'confused'),
(':[', 'sad'),
(':''(', 'cry'),
(':((', 'very sad'),
('o_O', 'stunned'),
(';_;', 'cry'),
('hehe', NULL),
('heh', NULL),
('haha', NULL),
('lol', NULL),
('hmm', NULL),
('wow', NULL),
('meh', NULL),
('ugh', NULL),
('pff', NULL),
('xD', 'happy'),
('rofl', NULL),
('lmao', NULL),
('huh', NULL),
('ahh', NULL),
('brr', NULL),
('ole', NULL),
('omg', NULL),
('bah', NULL),
('doh', NULL),
('duh', NULL),
('wtf', NULL),
('uhm', NULL),
('yum', NULL),
('woh', NULL),
('grr', NULL),
('ehh', NULL),
('tsk', NULL),
('ffs', NULL),
('uhh', NULL),
('yay', NULL),
('uhuh', NULL),
('ahem', NULL),
('woot', NULL),
('argh', NULL),
('urgh', NULL),
('whut', NULL);

CREATE TABLE table_state (
table_name TEXT PRIMARY KEY NOT NULL,
modified BOOLEAN NOT NULL DEFAULT 0 CHECK (modified IN (0,1)) --column affinity NUMERIC
) WITHOUT ROWID;
INSERT INTO table_state (table_name) VALUES
('uid_activity'),
('uid_details'),
('uid_events'),
('uid_lines'),
('uid_smileys'),
('uid_urls');

CREATE TABLE uid_activity (
uid INT NOT NULL REFERENCES uid_details (uid),
date TEXT NOT NULL,
l_night INT NOT NULL DEFAULT 0,
l_morning INT NOT NULL DEFAULT 0,
l_afternoon INT NOT NULL DEFAULT 0,
l_evening INT NOT NULL DEFAULT 0,
l_total INT NOT NULL CHECK (l_total > 0),
PRIMARY KEY (uid, date)
) WITHOUT ROWID;
CREATE TRIGGER uid_activity_update_modified_1 AFTER INSERT ON uid_activity
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_activity' AND modified = 0;
END;
CREATE TRIGGER uid_activity_update_modified_2 AFTER UPDATE ON uid_activity
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_activity' AND modified = 0;
END;

CREATE TABLE uid_details (
uid INTEGER PRIMARY KEY,
csnick TEXT COLLATE NOCASE UNIQUE NOT NULL, --case insensitive matching and sorting
firstseen TEXT NOT NULL,
lastseen TEXT NOT NULL,
ruid INT NOT NULL DEFAULT 0, --defaults to uid by trigger
status INT NOT NULL DEFAULT 0 CHECK (status IN (0,1,2,3,4))
);
CREATE INDEX uid_details_ruid ON uid_details (ruid);
CREATE INDEX uid_details_status ON uid_details (status);
CREATE TRIGGER uid_details_update_ruid AFTER INSERT ON uid_details
BEGIN
UPDATE uid_details SET ruid = uid WHERE uid = LAST_INSERT_ROWID();
END;
CREATE TRIGGER uid_details_update_modified AFTER UPDATE OF ruid ON uid_details
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_details' AND modified = 0;
END;

CREATE TABLE uid_events (
uid INT PRIMARY KEY NOT NULL REFERENCES uid_details (uid),
m_op INT NOT NULL DEFAULT 0,
m_opped INT NOT NULL DEFAULT 0,
m_voice INT NOT NULL DEFAULT 0,
m_voiced INT NOT NULL DEFAULT 0,
m_deop INT NOT NULL DEFAULT 0,
m_deopped INT NOT NULL DEFAULT 0,
m_devoice INT NOT NULL DEFAULT 0,
m_devoiced INT NOT NULL DEFAULT 0,
joins INT NOT NULL DEFAULT 0,
parts INT NOT NULL DEFAULT 0,
quits INT NOT NULL DEFAULT 0,
kicks INT NOT NULL DEFAULT 0,
kicked INT NOT NULL DEFAULT 0,
nickchanges INT NOT NULL DEFAULT 0,
topics INT NOT NULL DEFAULT 0,
ex_kicks TEXT,
ex_kicked TEXT
) WITHOUT ROWID;
CREATE TRIGGER uid_events_update_modified_1 AFTER INSERT ON uid_events
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_events' AND modified = 0;
END;
CREATE TRIGGER uid_events_update_modified_2 AFTER UPDATE ON uid_events
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_events' AND modified = 0;
END;

CREATE TABLE uid_lines (
uid INT PRIMARY KEY NOT NULL REFERENCES uid_details (uid),
l_00 INT NOT NULL DEFAULT 0,
l_01 INT NOT NULL DEFAULT 0,
l_02 INT NOT NULL DEFAULT 0,
l_03 INT NOT NULL DEFAULT 0,
l_04 INT NOT NULL DEFAULT 0,
l_05 INT NOT NULL DEFAULT 0,
l_06 INT NOT NULL DEFAULT 0,
l_07 INT NOT NULL DEFAULT 0,
l_08 INT NOT NULL DEFAULT 0,
l_09 INT NOT NULL DEFAULT 0,
l_10 INT NOT NULL DEFAULT 0,
l_11 INT NOT NULL DEFAULT 0,
l_12 INT NOT NULL DEFAULT 0,
l_13 INT NOT NULL DEFAULT 0,
l_14 INT NOT NULL DEFAULT 0,
l_15 INT NOT NULL DEFAULT 0,
l_16 INT NOT NULL DEFAULT 0,
l_17 INT NOT NULL DEFAULT 0,
l_18 INT NOT NULL DEFAULT 0,
l_19 INT NOT NULL DEFAULT 0,
l_20 INT NOT NULL DEFAULT 0,
l_21 INT NOT NULL DEFAULT 0,
l_22 INT NOT NULL DEFAULT 0,
l_23 INT NOT NULL DEFAULT 0,
l_night INT NOT NULL DEFAULT 0,
l_morning INT NOT NULL DEFAULT 0,
l_afternoon INT NOT NULL DEFAULT 0,
l_evening INT NOT NULL DEFAULT 0,
l_total INT NOT NULL DEFAULT 0,
l_mon_night INT NOT NULL DEFAULT 0,
l_mon_morning INT NOT NULL DEFAULT 0,
l_mon_afternoon INT NOT NULL DEFAULT 0,
l_mon_evening INT NOT NULL DEFAULT 0,
l_tue_night INT NOT NULL DEFAULT 0,
l_tue_morning INT NOT NULL DEFAULT 0,
l_tue_afternoon INT NOT NULL DEFAULT 0,
l_tue_evening INT NOT NULL DEFAULT 0,
l_wed_night INT NOT NULL DEFAULT 0,
l_wed_morning INT NOT NULL DEFAULT 0,
l_wed_afternoon INT NOT NULL DEFAULT 0,
l_wed_evening INT NOT NULL DEFAULT 0,
l_thu_night INT NOT NULL DEFAULT 0,
l_thu_morning INT NOT NULL DEFAULT 0,
l_thu_afternoon INT NOT NULL DEFAULT 0,
l_thu_evening INT NOT NULL DEFAULT 0,
l_fri_night INT NOT NULL DEFAULT 0,
l_fri_morning INT NOT NULL DEFAULT 0,
l_fri_afternoon INT NOT NULL DEFAULT 0,
l_fri_evening INT NOT NULL DEFAULT 0,
l_sat_night INT NOT NULL DEFAULT 0,
l_sat_morning INT NOT NULL DEFAULT 0,
l_sat_afternoon INT NOT NULL DEFAULT 0,
l_sat_evening INT NOT NULL DEFAULT 0,
l_sun_night INT NOT NULL DEFAULT 0,
l_sun_morning INT NOT NULL DEFAULT 0,
l_sun_afternoon INT NOT NULL DEFAULT 0,
l_sun_evening INT NOT NULL DEFAULT 0,
urls INT NOT NULL DEFAULT 0,
words INT NOT NULL DEFAULT 0,
characters INT NOT NULL DEFAULT 0,
monologues INT NOT NULL DEFAULT 0,
topmonologue INT NOT NULL DEFAULT 0, --highest value seen
slaps INT NOT NULL DEFAULT 0,
slapped INT NOT NULL DEFAULT 0,
exclamations INT NOT NULL DEFAULT 0,
questions INT NOT NULL DEFAULT 0,
actions INT NOT NULL DEFAULT 0,
uppercased INT NOT NULL DEFAULT 0,
quote TEXT,
ex_exclamations TEXT,
ex_questions TEXT,
ex_actions TEXT,
ex_uppercased TEXT,
lasttalked TEXT NOT NULL DEFAULT '0000-00-00 00:00:00'
) WITHOUT ROWID;
CREATE TRIGGER uid_lines_update_modified_1 AFTER INSERT ON uid_lines
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_lines' AND modified = 0;
END;
CREATE TRIGGER uid_lines_update_modified_2 AFTER UPDATE ON uid_lines
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_lines' AND modified = 0;
END;

CREATE TABLE uid_smileys (
uid INT NOT NULL REFERENCES uid_details (uid),
sid INT NOT NULL REFERENCES smileys (sid),
total INT NOT NULL CHECK (total > 0),
PRIMARY KEY (uid, sid)
) WITHOUT ROWID;
CREATE INDEX uid_smileys_sid ON uid_smileys (sid);
CREATE TRIGGER uid_smileys_update_modified_1 AFTER INSERT ON uid_smileys
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_smileys' AND modified = 0;
END;
CREATE TRIGGER uid_smileys_update_modified_2 AFTER UPDATE ON uid_smileys
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_smileys' AND modified = 0;
END;

CREATE TABLE uid_topics ( --truncated to last 10 records by trigger
uid INT NOT NULL REFERENCES uid_details (uid),
topic TEXT NOT NULL,
datetime TEXT NOT NULL
);
CREATE INDEX uid_topics_uid ON uid_topics (uid);
CREATE TRIGGER uid_topics_truncate AFTER INSERT ON uid_topics
BEGIN
DELETE FROM uid_topics WHERE ROWID IN (SELECT ROWID FROM uid_topics ORDER BY ROWID DESC LIMIT -1 OFFSET 10);
END;

CREATE TABLE uid_urls (
uid INT NOT NULL REFERENCES uid_details (uid),
lid INT NOT NULL REFERENCES urls (lid),
firstused TEXT NOT NULL,
lastused TEXT NOT NULL,
total INT NOT NULL CHECK (total > 0),
PRIMARY KEY (uid, lid)
) WITHOUT ROWID;
CREATE INDEX uid_urls_lid ON uid_urls (lid);
CREATE TRIGGER uid_urls_update_modified_1 AFTER INSERT ON uid_urls
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_urls' AND modified = 0;
END;
CREATE TRIGGER uid_urls_update_modified_2 AFTER UPDATE ON uid_urls
BEGIN
UPDATE table_state SET modified = 1 WHERE table_name = 'uid_urls' AND modified = 0;
END;

CREATE TABLE urls (
lid INTEGER PRIMARY KEY,
url TEXT UNIQUE NOT NULL,
fid INT REFERENCES fqdns (fid)
);
CREATE INDEX urls_fid ON urls (fid);

CREATE TABLE words (
word TEXT PRIMARY KEY NOT NULL,
length INT NOT NULL CHECK (length > 0),
total INT NOT NULL CHECK (total > 0),
firstused TEXT NOT NULL
);
CREATE INDEX words_length ON words (length);
CREATE INDEX words_firstused ON words (firstused);

CREATE VIEW v_ruid_activity_by_day AS
SELECT ruid,
date,
SUM(l_night) AS l_night,
SUM(l_morning) AS l_morning,
SUM(l_afternoon) AS l_afternoon,
SUM(l_evening) AS l_evening,
SUM(l_total) AS l_total
FROM uid_activity JOIN uid_details ON uid_activity.uid = uid_details.uid GROUP BY ruid, date;

CREATE VIEW v_ruid_activity_by_month AS
SELECT ruid,
SUBSTR(date, 1, 7) AS date,
SUM(l_night) AS l_night,
SUM(l_morning) AS l_morning,
SUM(l_afternoon) AS l_afternoon,
SUM(l_evening) AS l_evening,
SUM(l_total) AS l_total
FROM ruid_activity_by_day GROUP BY ruid, SUBSTR(date, 1, 7);

CREATE VIEW v_ruid_activity_by_year AS
SELECT ruid,
SUBSTR(date, 1, 4) AS date,
SUM(l_night) AS l_night,
SUM(l_morning) AS l_morning,
SUM(l_afternoon) AS l_afternoon,
SUM(l_evening) AS l_evening,
SUM(l_total) AS l_total
FROM ruid_activity_by_month GROUP BY ruid, SUBSTR(date, 1, 4);

CREATE VIEW v_ruid_events AS
SELECT ruid,
SUM(m_op) AS m_op,
SUM(m_opped) AS m_opped,
SUM(m_voice) AS m_voice,
SUM(m_voiced) AS m_voiced,
SUM(m_deop) AS m_deop,
SUM(m_deopped) AS m_deopped,
SUM(m_devoice) AS m_devoice,
SUM(m_devoiced) AS m_devoiced,
SUM(joins) AS joins,
SUM(parts) AS parts,
SUM(quits) AS quits,
SUM(kicks) AS kicks,
SUM(kicked) AS kicked,
SUM(nickchanges) AS nickchanges,
SUM(topics) AS topics,
(SELECT ex_kicks FROM uid_events JOIN uid_details ON uid_events.uid = uid_details.uid WHERE ruid = t1.ruid AND ex_kicks IS NOT NULL ORDER BY RANDOM() LIMIT 1) AS ex_kicks,
(SELECT ex_kicked FROM uid_events JOIN uid_details ON uid_events.uid = uid_details.uid WHERE ruid = t1.ruid AND ex_kicked IS NOT NULL ORDER BY RANDOM() LIMIT 1) AS ex_kicked
FROM uid_events JOIN uid_details AS t1 ON uid_events.uid = t1.uid GROUP BY ruid;

CREATE VIEW v_ruid_lines AS
SELECT ruid,
SUM(l_00) AS l_00,
SUM(l_01) AS l_01,
SUM(l_02) AS l_02,
SUM(l_03) AS l_03,
SUM(l_04) AS l_04,
SUM(l_05) AS l_05,
SUM(l_06) AS l_06,
SUM(l_07) AS l_07,
SUM(l_08) AS l_08,
SUM(l_09) AS l_09,
SUM(l_10) AS l_10,
SUM(l_11) AS l_11,
SUM(l_12) AS l_12,
SUM(l_13) AS l_13,
SUM(l_14) AS l_14,
SUM(l_15) AS l_15,
SUM(l_16) AS l_16,
SUM(l_17) AS l_17,
SUM(l_18) AS l_18,
SUM(l_19) AS l_19,
SUM(l_20) AS l_20,
SUM(l_21) AS l_21,
SUM(l_22) AS l_22,
SUM(l_23) AS l_23,
SUM(l_night) AS l_night,
SUM(l_morning) AS l_morning,
SUM(l_afternoon) AS l_afternoon,
SUM(l_evening) AS l_evening,
SUM(l_total) AS l_total,
SUM(l_mon_night) AS l_mon_night,
SUM(l_mon_morning) AS l_mon_morning,
SUM(l_mon_afternoon) AS l_mon_afternoon,
SUM(l_mon_evening) AS l_mon_evening,
SUM(l_tue_night) AS l_tue_night,
SUM(l_tue_morning) AS l_tue_morning,
SUM(l_tue_afternoon) AS l_tue_afternoon,
SUM(l_tue_evening) AS l_tue_evening,
SUM(l_wed_night) AS l_wed_night,
SUM(l_wed_morning) AS l_wed_morning,
SUM(l_wed_afternoon) AS l_wed_afternoon,
SUM(l_wed_evening) AS l_wed_evening,
SUM(l_thu_night) AS l_thu_night,
SUM(l_thu_morning) AS l_thu_morning,
SUM(l_thu_afternoon) AS l_thu_afternoon,
SUM(l_thu_evening) AS l_thu_evening,
SUM(l_fri_night) AS l_fri_night,
SUM(l_fri_morning) AS l_fri_morning,
SUM(l_fri_afternoon) AS l_fri_afternoon,
SUM(l_fri_evening) AS l_fri_evening,
SUM(l_sat_night) AS l_sat_night,
SUM(l_sat_morning) AS l_sat_morning,
SUM(l_sat_afternoon) AS l_sat_afternoon,
SUM(l_sat_evening) AS l_sat_evening,
SUM(l_sun_night) AS l_sun_night,
SUM(l_sun_morning) AS l_sun_morning,
SUM(l_sun_afternoon) AS l_sun_afternoon,
SUM(l_sun_evening) AS l_sun_evening,
SUM(urls) AS urls,
SUM(words) AS words,
SUM(characters) AS characters,
SUM(monologues) AS monologues,
MAX(topmonologue) AS topmonologue,
(SELECT COUNT(DISTINCT date) FROM ruid_activity_by_day WHERE ruid = t1.ruid) AS activedays,
SUM(slaps) AS slaps,
SUM(slapped) AS slapped,
SUM(exclamations) AS exclamations,
SUM(questions) AS questions,
SUM(actions) AS actions,
SUM(uppercased) AS uppercased,
(SELECT quote FROM uid_lines JOIN uid_details ON uid_lines.uid = uid_details.uid WHERE ruid = t1.ruid AND quote IS NOT NULL ORDER BY lasttalked DESC, uid_lines.uid ASC LIMIT 1) AS quote,
(SELECT ex_exclamations FROM uid_lines JOIN uid_details ON uid_lines.uid = uid_details.uid WHERE ruid = t1.ruid AND ex_exclamations IS NOT NULL ORDER BY lasttalked DESC, uid_lines.uid ASC LIMIT 1) AS ex_exclamations,
(SELECT ex_questions FROM uid_lines JOIN uid_details ON uid_lines.uid = uid_details.uid WHERE ruid = t1.ruid AND ex_questions IS NOT NULL ORDER BY lasttalked DESC, uid_lines.uid ASC LIMIT 1) AS ex_questions,
(SELECT ex_actions FROM uid_lines JOIN uid_details ON uid_lines.uid = uid_details.uid WHERE ruid = t1.ruid AND ex_actions IS NOT NULL ORDER BY lasttalked DESC, lastseen DESC, uid_lines.uid ASC LIMIT 1) AS ex_actions,
(SELECT ex_uppercased FROM uid_lines JOIN uid_details ON uid_lines.uid = uid_details.uid WHERE ruid = t1.ruid AND ex_uppercased IS NOT NULL ORDER BY lasttalked DESC, uid_lines.uid ASC LIMIT 1) AS ex_uppercased,
MAX(lasttalked) AS lasttalked
FROM uid_lines JOIN uid_details AS t1 ON uid_lines.uid = t1.uid GROUP BY ruid;

CREATE VIEW v_ruid_smileys AS
SELECT ruid,
sid,
SUM(total) AS total
FROM uid_smileys JOIN uid_details ON uid_smileys.uid = uid_details.uid GROUP BY ruid, sid;

CREATE VIEW v_ruid_urls AS
SELECT ruid,
lid,
MIN(firstused) AS firstused,
MAX(lastused) AS lastused,
SUM(total) AS total
FROM uid_urls JOIN uid_details ON uid_urls.uid = uid_details.uid GROUP BY ruid, lid;

COMMIT;
