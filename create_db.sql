CREATE TABLE `division` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `match_guid` varchar(128) DEFAULT NULL,
 `Division` varchar(128) DEFAULT NULL,
 `PF` varchar(128) DEFAULT NULL,
 `Class` varchar(128) DEFAULT NULL,
 `Count` int(10) DEFAULT NULL,
 PRIMARY KEY (`id`)
) 


dq	CREATE TABLE `dq` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `match_guid` varchar(128) DEFAULT NULL,
 `rule` varchar(128) DEFAULT NULL,
 `description` varchar(128) DEFAULT NULL,
 `count` int(10) DEFAULT NULL,
 PRIMARY KEY (`id`)
) 


log	CREATE TABLE `log` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `match_guid` varchar(128) DEFAULT NULL,
 `Comment` varchar(128) DEFAULT NULL,
 `A` varchar(128) DEFAULT NULL,
 `B` varchar(128) DEFAULT NULL,
 `C` varchar(128) DEFAULT NULL,
 PRIMARY KEY (`id`)
) 


matches	CREATE TABLE `matches` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `match_guid` varchar(128) DEFAULT NULL,
 `date` date DEFAULT NULL,
 `ctime` varchar(128) DEFAULT NULL,
 `mtime` varchar(128) DEFAULT NULL,
 `name` varchar(128) DEFAULT NULL,
 `club` varchar(128) DEFAULT NULL,
 `club_code` varchar(128) DEFAULT NULL,
 `match_type` varchar(128) DEFAULT NULL,
 `match_subtype` varchar(128) DEFAULT NULL,
 `match_level` varchar(128) DEFAULT NULL,
 `device_arch` varchar(128) DEFAULT NULL,
 `device_model` varchar(128) DEFAULT NULL,
 `app_version` varchar(128) DEFAULT NULL,
 `os_version` varchar(128) DEFAULT NULL,
 `count_shooters` int(10) DEFAULT NULL,
 `count_stages` int(10) DEFAULT NULL,
 PRIMARY KEY (`id`)
) 


shooter	CREATE TABLE `shooter` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `match_guid` varchar(128) NOT NULL,
 `sh_grd` varchar(128) NOT NULL,
 `sh_ln` varchar(128) NOT NULL,
 `sh_fn` varchar(128) NOT NULL,
 `sh_dvp` varchar(128) NOT NULL,
 `sh_pf` varchar(128) NOT NULL,
 `sh_id` varchar(128) NOT NULL,
 `sh_dq` varchar(128) NOT NULL,
 `sh_dqrule` varchar(128) NOT NULL,
 PRIMARY KEY (`id`)
) 


