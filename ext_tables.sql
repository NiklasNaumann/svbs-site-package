CREATE TABLE tx_svbadschwartau_ticker_message (
    uid int unsigned NOT NULL auto_increment,
    pid int unsigned DEFAULT 0 NOT NULL,
    tstamp int unsigned DEFAULT 0 NOT NULL,
    crdate int unsigned DEFAULT 0 NOT NULL,
    deleted smallint unsigned DEFAULT 0 NOT NULL,
    hidden smallint unsigned DEFAULT 0 NOT NULL,
    sorting int unsigned DEFAULT 0 NOT NULL,
    message text NOT NULL,

    PRIMARY KEY (uid),
    KEY storage_messages (pid, hidden, crdate)
);
