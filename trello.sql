SET NAMES utf8;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `board`
-- ----------------------------
DROP TABLE IF EXISTS `board`;

CREATE TABLE `board` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `idOrganization` varchar(255) DEFAULT NULL,
    `closed` BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (`id`)) ENGINE = InnoDB DEFAULT CHARSET = utf8;

-- ----------------------------
--  Table structure for `card`
-- ----------------------------
DROP TABLE IF EXISTS `card`;

CREATE TABLE `card` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `shortUrl` varchar(255) DEFAULT NULL,
    `due` datetime DEFAULT NULL,
    `dateLastActivity` datetime DEFAULT NULL,
    `closed` BOOLEAN DEFAULT FALSE,
    `desc` text,
    `idBoard` varchar(255) DEFAULT NULL,
    `idList` varchar(255) DEFAULT NULL,
    `timeCreated` datetime DEFAULT NULL,
    `labels` text,
    `pos` int (11) DEFAULT NULL,
    PRIMARY KEY (`id`)) ENGINE = InnoDB DEFAULT CHARSET = utf8;

-- ----------------------------
--  Table structure for `cardLabel`
-- ----------------------------
DROP TABLE IF EXISTS `cardLabel`;

CREATE TABLE `cardLabel` (
    `idCard` varchar(255) NOT NULL,
    `idLabel` varchar(255) NOT NULL,
    PRIMARY KEY (`idCard`,
        `idLabel`)) ENGINE = InnoDB DEFAULT CHARSET = utf8;

-- ----------------------------
--  Table structure for `label`
-- ----------------------------
DROP TABLE IF EXISTS `label`;

CREATE TABLE `label` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `timeCreated` datetime DEFAULT NULL,
    `idBoard` varchar(255) DEFAULT NULL,
    `uses` varchar(255) DEFAULT NULL,
    `color` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)) ENGINE = InnoDB DEFAULT CHARSET = utf8;

-- ----------------------------
--  Table structure for `list`
-- ----------------------------
DROP TABLE IF EXISTS `list`;

CREATE TABLE `list` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `closed` BOOLEAN DEFAULT FALSE,
    `timeCreated` datetime DEFAULT NULL,
    `idBoard` varchar(255) DEFAULT NULL,
    `pos` int (11) DEFAULT NULL,
    PRIMARY KEY (`id`)) ENGINE = InnoDB DEFAULT CHARSET = utf8;

-- ----------------------------
--  Table structure for `cardAction`
-- ----------------------------
DROP TABLE IF EXISTS `cardAction`;

CREATE TABLE `cardAction` (
    `id` varchar(255) NOT NULL,
    `idCard` varchar(255) DEFAULT NULL,
    `data` longtext,
    `type` varchar(255) DEFAULT NULL,
    `date` datetime DEFAULT NULL,
    `memberCreator` text,
    PRIMARY KEY (`id`)) ENGINE = InnoDB DEFAULT CHARSET = utf8;

SET FOREIGN_KEY_CHECKS = 1;
