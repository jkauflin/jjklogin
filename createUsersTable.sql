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

