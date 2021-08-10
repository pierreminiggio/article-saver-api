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
    `content` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
```
