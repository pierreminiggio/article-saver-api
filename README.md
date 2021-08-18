Migration :
```sql
CREATE TABLE `article_saver`.`article` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `link` TEXT NOT NULL,
    `thumbnail` TEXT NOT NULL,
    `pub_date_string` VARCHAR(70) NOT NULL,
    `content` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE `article`.`video_to_render` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `article_id` INT NOT NULL,
    `remotion_props` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

CREATE TABLE `video_render_status` (
  `id` int NOT NULL,
  `video_id` int NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `fail_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `video_render_status`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `video_render_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
```
