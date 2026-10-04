-- 1% SaaS — Schema complet
-- A exécuter dans phpMyAdmin → onglet SQL

CREATE TABLE IF NOT EXISTS `users` (
  `id`            int(11)      NOT NULL AUTO_INCREMENT,
  `name`          varchar(100) NOT NULL,
  `email`         varchar(150) NOT NULL UNIQUE,
  `password`      varchar(255) NOT NULL,
  `is_premium`    tinyint(1)   NOT NULL DEFAULT 0,
  `is_vip`        tinyint(1)   NOT NULL DEFAULT 0,
  `plan_expires`  date                  DEFAULT NULL,
  `notif_weekly`  tinyint(1)   NOT NULL DEFAULT 1,
  `notif_daily`   tinyint(1)   NOT NULL DEFAULT 1,
  `created_at`    timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `goals` (
  `id`             int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`        int(11)      NOT NULL,
  `title`          varchar(255) NOT NULL,
  `hour_slot`      tinyint(2)            DEFAULT NULL,
  `end_hour`       tinyint(2)            DEFAULT NULL,
  `difficulty`     tinyint(1)   NOT NULL DEFAULT 1,
  `done`           tinyint(1)   NOT NULL DEFAULT 0,
  `repeat_daily`   tinyint(1)   NOT NULL DEFAULT 0,
  `repeat_weekly`  tinyint(1)   NOT NULL DEFAULT 0,
  `repeat_dow`     tinyint(1)            DEFAULT NULL,
  `recurrence_id`  varchar(36)           DEFAULT NULL,
  `date_created`   date         NOT NULL DEFAULT (CURDATE()),
  `created_at`     timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_date`   (`user_id`, `date_created`),
  KEY `idx_recurrence`  (`recurrence_id`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
