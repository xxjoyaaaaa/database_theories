USE team10_activity_planner;

-- CATEGORY

INSERT INTO CATEGORY
VALUES ('C001', '演唱會');

INSERT INTO CATEGORY
VALUES ('C002', '展覽');

INSERT INTO CATEGORY
VALUES ('C003', '講座');

INSERT INTO CATEGORY
VALUES ('C004', '運動賽事');


-- USERS

INSERT INTO USERS
VALUES (
'U001',
'Charlotte',
'charlotte@gmail.com',
'123456',
'user'
);

INSERT INTO USERS
VALUES (
'U002',
'Tom',
'tom@gmail.com',
'123456',
'user'
);

INSERT INTO USERS
VALUES (
'U003',
'Admin',
'admin@gmail.com',
'admin123',
'admin'
);


-- ACTIVITY

INSERT INTO ACTIVITY
VALUES (
'A001',
'周杰倫演唱會',
'2026-08-01 19:00:00',
'台北小巨蛋',
'KKTIX',
'K001',
'https://kktix.com',
'熱賣中',
NOW(),
'C001'
);

INSERT INTO ACTIVITY
VALUES (
'A002',
'漫畫博覽會',
'2026-07-15 10:00:00',
'台北世貿',
'ACCUPASS',
'A002',
'https://www.accupass.com',
'有名額',
NOW(),
'C002'
);

INSERT INTO ACTIVITY
VALUES (
'A003',
'AI 技術講座',
'2026-07-20 14:00:00',
'台灣師範大學',
'Eventbrite',
'E003',
'https://www.eventbrite.com',
'有名額',
NOW(),
'C003'
);

INSERT INTO ACTIVITY
VALUES (
'A004',
'瓦豆演講',
'2026-06-15 08:00:00',
'瓦豆大學',
'Manual',
'WD001',
'https://wadou.example.com',
'已結束',
NOW(),
'C003'
);


-- SCHEDULE

INSERT INTO SCHEDULE
VALUES (
'S001',
'U001',
'A001',
'已購票',
NOW()
);

INSERT INTO SCHEDULE
VALUES (
'S002',
'U001',
'A003',
'感興趣',
NOW()
);

INSERT INTO SCHEDULE
VALUES (
'S003',
'U002',
'A002',
'預定參加',
NOW()
);


-- REMINDER

INSERT INTO REMINDER
VALUES (
'R001',
'S001',
'2026-07-31 19:00:00',
'email',
FALSE
);

INSERT INTO REMINDER
VALUES (
'R002',
'S002',
'2026-07-19 14:00:00',
'push',
FALSE
);

INSERT INTO REMINDER
VALUES (
'R003',
'S003',
'2026-07-14 10:00:00',
'email',
FALSE
);
