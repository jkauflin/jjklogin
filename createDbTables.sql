--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `UserId` int(7) NOT NULL AUTO_INCREMENT,
  `UserEmailAddr` varchar(100) NOT NULL,
  `UserPassword` varchar(100) NOT NULL,
  `UserName` varchar(80) NOT NULL DEFAULT 'guest',
  `UserLevel` int(2) NOT NULL DEFAULT 0,
  `UserLastLogin` datetime NOT NULL DEFAULT current_timestamp(),
  `RegistrationCode` varchar(100) NOT NULL,
  `EmailVerified` int(1) NOT NULL DEFAULT 0,
  `LastChangedBy` varchar(80) NOT NULL DEFAULT 'system',
  `LastChangedTs` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`UserId`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `jjkloginSettings`;
CREATE TABLE `jjkloginSettings` (
  `SettingsId` int(11) NOT NULL,
  `CookiePath` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `CookieName` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ServerKey` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DomainUrl` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MailServer` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MailPort` int(11) NOT NULL,
  `MailUser` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MailPass` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AutoRedirect` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `jjkloginSettings`
  ADD PRIMARY KEY (`SettingsId`);
ALTER TABLE `jjkloginSettings`
  MODIFY `SettingsId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;
