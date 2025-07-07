-- Script para crear la tabla ssn_tokens
-- Ejecutar este script en tu base de datos MySQL

CREATE TABLE `ssn_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiration` datetime DEFAULT NULL,
  `is_mock` tinyint(1) NOT NULL DEFAULT 0,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ssn_tokens_expiration_is_mock_index` (`expiration`,`is_mock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 