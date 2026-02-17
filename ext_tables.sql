CREATE TABLE tx_leadmagnet_lead (
    email varchar(255) DEFAULT '' NOT NULL,
    token varchar(255) DEFAULT '' NOT NULL,
    content_element int(11) DEFAULT 0 NOT NULL,
    downloaded tinyint(1) DEFAULT 0 NOT NULL,

    KEY token (token)
);
