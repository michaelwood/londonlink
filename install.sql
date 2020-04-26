SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `submit_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `booking_person_name` text NOT NULL,
  `booking_person_email` text NOT NULL,
  `event_start_date` text NOT NULL,
  `event_end_date` text NOT NULL,
  `password` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `cost` float DEFAULT '0',
  `wp_page_id` int(11) DEFAULT NULL,
  `form_template` text,
  `listed` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=362;
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
ALTER TABLE `bookings` ADD `admin_notes` TEXT NULL DEFAULT NULL AFTER `data`;
