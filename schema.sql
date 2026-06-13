USE team10_activity_planner;

CREATE TABLE CATEGORY (
    category_id VARCHAR(50) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (category_id)
);

CREATE TABLE USERS (
    user_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    PRIMARY KEY (user_id),
    UNIQUE (email)
);

CREATE TABLE ACTIVITY (
    activity_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    activity_time DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    source_platform VARCHAR(100),
    external_id VARCHAR(100),
    external_url VARCHAR(255),
    cache_status VARCHAR(50),
    last_sync_time DATETIME,
    category_id VARCHAR(50),
    PRIMARY KEY (activity_id),
    FOREIGN KEY (category_id)
        REFERENCES CATEGORY(category_id)
);

CREATE TABLE SCHEDULE (
    schedule_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    activity_id VARCHAR(50) NOT NULL,
    status VARCHAR(50),
    created_at DATETIME,
    PRIMARY KEY (schedule_id),
    FOREIGN KEY (user_id)
        REFERENCES USERS(user_id),
    FOREIGN KEY (activity_id)
        REFERENCES ACTIVITY(activity_id),
    UNIQUE(user_id, activity_id)
);

CREATE TABLE REMINDER (
    reminder_id VARCHAR(50) NOT NULL,
    schedule_id VARCHAR(50) NOT NULL,
    reminder_time DATETIME,
    notify_method VARCHAR(50),
    is_sent BOOLEAN,
    PRIMARY KEY (reminder_id),
    FOREIGN KEY (schedule_id)
        REFERENCES SCHEDULE(schedule_id)
);
