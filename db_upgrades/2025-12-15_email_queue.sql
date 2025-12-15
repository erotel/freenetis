START TRANSACTION;

-- email_queue_attachments (chybí v produkci)
CREATE TABLE IF NOT EXISTS `email_queue_attachments` (
  `id` int(11) NOT NULL,
  `email_queue_id` int(11) NOT NULL,
  `path` varchar(1024) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email_queue_id` (`email_queue_id`),
  CONSTRAINT `email_queue_attachments_ibfk_1`
    FOREIGN KEY (`email_queue_id`)
    REFERENCES `email_queues` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

ALTER TABLE `invoices`
  ADD COLUMN `pdf_filename` varchar(64) NULL DEFAULT NULL
  AFTER `note`;

COMMIT;
