CREATE TABLE tx_event_categories (
    title      VARCHAR(128) NOT NULL DEFAULT '',
    color_code VARCHAR(7)   NOT NULL DEFAULT '',

    INDEX title(title)
);

CREATE TABLE tx_event_locations (
    name                   VARCHAR(128) NOT NULL DEFAULT '',
    country_code           VARCHAR(2)   NOT NULL DEFAULT '',
    postal_code            VARCHAR(16)  NOT NULL DEFAULT '',
    city                   VARCHAR(64)  NOT NULL DEFAULT '',
    street                 VARCHAR(255) NOT NULL DEFAULT '',
    additional_information VARCHAR(255) NOT NULL DEFAULT '',
    phone_number           VARCHAR(64)  NOT NULL DEFAULT '',
    email_address          VARCHAR(128) NOT NULL DEFAULT '',
    website                VARCHAR(255) NOT NULL DEFAULT '',
    facebook_url           VARCHAR(255) NOT NULL DEFAULT '',
    instagram_account      VARCHAR(64)  NOT NULL DEFAULT '',
    online                 TINYINT(1)   NOT NULL DEFAULT 0,
    latitude               FLOAT                 DEFAULT NULL,
    longitude              FLOAT                 DEFAULT NULL,

    INDEX name(name),
    INDEX online(online)
);

CREATE TABLE tx_events (
    title                           VARCHAR(255) NOT NULL DEFAULT '',
    slug                            VARCHAR(255) NOT NULL DEFAULT '',
    short_description               TEXT,
    description                     MEDIUMTEXT,
    gallery                         INT(11)      NOT NULL DEFAULT 0,
    source                          VARCHAR(64)  NOT NULL DEFAULT '',
    external_url                    VARCHAR(255) NOT NULL DEFAULT '',
    ticket_url                      VARCHAR(255) NOT NULL DEFAULT '',
    start_date                      INT(11)      NOT NULL DEFAULT 0,
    end_date                        INT(11)      NOT NULL DEFAULT 0,
    location                        INT(11)      NOT NULL DEFAULT 0,
    additional_location_information TEXT,
    categories                      VARCHAR(255) NOT NULL DEFAULT '',
    price_information               VARCHAR(255) NOT NULL DEFAULT '',
    free_of_charge                  TINYINT(1)   NOT NULL DEFAULT 0,
    sold_out                        TINYINT(1)   NOT NULL DEFAULT 0,
    pre_registration                TINYINT(1)   NOT NULL DEFAULT 0,
    seated                          INT(4)       NOT NULL DEFAULT 0,
    insider                         TINYINT(1)   NOT NULL DEFAULT 0,

    INDEX slug(slug),
    INDEX start_date(start_date),
    INDEX end_date(end_date),
    INDEX location(location),
    INDEX insider(insider)
);
