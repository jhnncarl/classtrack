-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2026 at 04:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `classtrack_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `role`, `status`, `created_at`, `profile_pic`) VALUES
(1, 'AdminCarl', '$2y$10$6aqyu7.rIQ54HXfpdjxjl.dXTqc5eGkogR.GZUKWZbdg4T/rXJw/m', 'admin', 'active', '2026-04-14 11:20:35', 'uploads/profiles/admin_profile_1_1776221958.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `attendancerecords`
--

CREATE TABLE `attendancerecords` (
  `RecordID` int(11) NOT NULL,
  `SessionID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `ScanTime` datetime DEFAULT current_timestamp(),
  `AttendanceStatus` enum('Present','Late','Absent') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendancerecords`
--

INSERT INTO `attendancerecords` (`RecordID`, `SessionID`, `StudentID`, `ScanTime`, `AttendanceStatus`) VALUES
(139, 127, 69, '2026-04-13 15:07:27', 'Present'),
(140, 127, 68, '2026-04-13 15:08:01', 'Present'),
(141, 128, 68, '2026-04-13 15:15:19', 'Present'),
(142, 128, 69, '2026-04-13 15:18:28', 'Late'),
(143, 129, 69, '2026-04-13 15:26:14', 'Present'),
(144, 129, 68, '2026-04-13 15:30:26', 'Late'),
(145, 130, 68, '2026-04-13 15:41:32', 'Present'),
(146, 130, 69, '2026-04-13 15:41:48', 'Present'),
(147, 131, 69, '2026-04-13 15:42:37', 'Present'),
(148, 131, 68, '2026-04-13 15:47:51', 'Late'),
(149, 132, 68, '2026-04-13 21:56:27', 'Absent'),
(150, 132, 69, '2026-04-13 21:56:27', 'Absent'),
(151, 133, 68, '2026-04-13 16:13:28', 'Late'),
(152, 133, 69, '2026-04-13 16:13:38', 'Late');

-- --------------------------------------------------------

--
-- Table structure for table `attendancesessions`
--

CREATE TABLE `attendancesessions` (
  `SessionID` int(11) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  `SessionDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time DEFAULT NULL,
  `Status` enum('Active','Closed') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendancesessions`
--

INSERT INTO `attendancesessions` (`SessionID`, `SubjectID`, `SessionDate`, `StartTime`, `EndTime`, `Status`) VALUES
(127, 24, '2026-04-13', '21:02:20', '21:09:43', 'Closed'),
(128, 24, '2026-04-13', '21:09:49', '21:22:40', 'Closed'),
(129, 24, '2026-04-13', '21:22:43', '21:39:37', 'Closed'),
(130, 24, '2026-04-13', '21:41:19', '21:42:21', 'Closed'),
(131, 24, '2026-04-13', '21:42:25', '21:48:51', 'Closed'),
(132, 24, '2026-04-13', '21:49:50', '21:56:27', 'Closed'),
(133, 24, '2026-04-13', '22:06:31', '22:13:47', 'Closed');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `EnrollmentID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  `DateEnrolled` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`EnrollmentID`, `StudentID`, `SubjectID`, `DateEnrolled`) VALUES
(3, 69, 24, '2026-04-02'),
(4, 69, 19, '2026-04-03'),
(6, 68, 24, '2026-04-05');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `otp`, `expires_at`, `created_at`) VALUES
(21, 'nudalojhoncarloe@gmail.com', '776822', '2026-04-01 14:11:04', '2026-04-01 14:06:04'),
(22, 'rodmarcariza@gmail.com', '462481', '2026-04-01 14:11:22', '2026-04-01 14:06:22');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` enum('Student','Teacher','Administrator') NOT NULL,
  `createClass` tinyint(1) DEFAULT 0,
  `joinClass` tinyint(1) DEFAULT 0,
  `manageClass` tinyint(1) DEFAULT 0,
  `takeAttendance` tinyint(1) DEFAULT 0,
  `viewReports` tinyint(1) DEFAULT 0,
  `exportReports` tinyint(1) DEFAULT 0,
  `editProfile` tinyint(1) DEFAULT 0,
  `student_unenrollClass` tinyint(1) DEFAULT 0,
  `student_viewAttendanceRecord` tinyint(1) DEFAULT 0,
  `student_viewAttendanceHistory` tinyint(1) DEFAULT 0,
  `approveTeacherAccounts` tinyint(1) DEFAULT 0,
  `rejectTeacherAccounts` tinyint(1) DEFAULT 0,
  `createAdminUser` tinyint(1) DEFAULT 0,
  `editAdminProfile` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `createClass`, `joinClass`, `manageClass`, `takeAttendance`, `viewReports`, `exportReports`, `editProfile`, `student_unenrollClass`, `student_viewAttendanceRecord`, `student_viewAttendanceHistory`, `approveTeacherAccounts`, `rejectTeacherAccounts`, `createAdminUser`, `editAdminProfile`, `updated_at`) VALUES
('Student', 0, 1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, '2026-04-21 02:39:42'),
('Teacher', 1, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, '2026-04-21 02:39:42'),
('Administrator', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-21 02:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `StudentID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `StudentNumber` varchar(50) NOT NULL,
  `Course` varchar(100) NOT NULL,
  `YearLevel` int(11) NOT NULL,
  `QRCodePath` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`StudentID`, `UserID`, `StudentNumber`, `Course`, `YearLevel`, `QRCodePath`) VALUES
(68, 73, '2022-31559', 'BSIT', 3, 'uploads/qrcodes/student_2022-31559_1774665399.png'),
(69, 75, '2022-78960', 'BSIT', 3, 'uploads/qrcodes/Student_2022-78960.png'),
(85, 107, '2026-12762', 'BEED', 1, 'uploads/qrcodes/Student_2026-12762.png'),
(95, 133, '2022-35614', '', 0, 'uploads/qrcodes/Student_2022-35614.png'),
(96, 134, '12345', 'BSCE', 2, 'uploads/qrcodes/Student_12345.png');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `SubjectID` int(11) NOT NULL,
  `SubjectCode` varchar(20) NOT NULL,
  `SubjectName` varchar(100) NOT NULL,
  `TeacherID` int(11) NOT NULL,
  `Schedule` varchar(100) DEFAULT NULL,
  `ClassName` varchar(100) NOT NULL,
  `SectionName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`SubjectID`, `SubjectCode`, `SubjectName`, `TeacherID`, `Schedule`, `ClassName`, `SectionName`) VALUES
(19, 'k7ceo3m2', 'Web Development', 6, 'Fri, 9:00AM - 11:00PM', 'IT101', '3B'),
(24, 'xzxm0fb', 'App Development', 6, 'Monday, 9:00AM - 10:00AM', 'IT103', '3B'),
(30, 'xq2edl', 'Test Subject for Join', 6, 'MWF 10:00-11:00', 'TEST101', '1A'),
(31, 'nwn9ndpz', 'Data Mining', 6, 'Thursday, 1:00PM - 2:00PM', 'IT243', '3A'),
(32, '83sh5k6j', 'SAMPLE', 6, 'MONDAY, 9:00PM', 'SAMPLE', 'SAMPLE');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `TeacherID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`TeacherID`, `UserID`, `Department`) VALUES
(6, 74, 'Computer Studies'),
(14, 132, NULL),
(15, 135, 'Industrial Tech Department');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('Student','Teacher','Administrator') NOT NULL,
  `AccountStatus` enum('Pending','Active','Rejected') DEFAULT 'Pending',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ProfilePicture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `first_name`, `last_name`, `Email`, `PasswordHash`, `Role`, `AccountStatus`, `CreatedAt`, `ProfilePicture`) VALUES
(73, 'Jhon Carlo', 'Nudalo', 'nudalojhoncarloe@gmail.com', '$2y$10$hz4hIzN9TQvVz9FtM1p26eu8/T0B9fz3H5aHFla4soZEsR3ebVFdu', 'Student', 'Active', '2026-03-28 02:36:36', 'uploads/profiles/profile_73_1775094155.jpg'),
(74, 'Rodmarc', 'Villaflores', 'rodmarcariza@gmail.com', '$2y$10$RtDTODndlcoz32fnA155q.zxG/j73nh9GWsj5fdSaTnUL2ilJnIJ6', 'Teacher', 'Active', '2026-03-28 05:56:07', 'uploads/profiles/profile_74_1774677821.jpg'),
(75, 'Kean Andre', 'Maglasang', 'keanandre@gmail.com', '$2y$10$TPXXPAvk7wYdq1XBK72v9OgoJUkUq14lTV6Kxjps.QnAnxy0r0/cy', 'Student', 'Active', '2026-04-02 10:21:28', NULL),
(107, 'Kathlene', 'Nudalo', 'kathlenenudalo2008@gmail.com', '$2y$10$ruyefCCSDuio4uTDe01umuZDxjCpmlqTCgzHtKRYyzAdJvbdSHvna', 'Student', 'Active', '2026-04-21 00:48:58', NULL),
(132, 'Jhon Carlo', 'Nudalo', 'nudalojhoncarlo2003@gmail.com', '$2y$10$dHf1ou8.YE/lNfN18V6mjeeoKvxqmX6YSNoPmcxUY.YgR5d1ODIne', 'Teacher', 'Active', '2026-04-21 01:30:31', NULL),
(133, 'Jason', 'Dawg', 'dawgjason@gmail.com', '$2y$10$dGDGd3uuAUkGnJRLKCXUD.3bTbSN44Sx9YER1GmKB/S7Iwc4sKNqW', 'Student', 'Active', '2026-04-21 01:31:56', NULL),
(134, 'Jona', 'Magdalina', 'jonamagdalina@gmail.com', '$2y$10$Z.cdpJGRy6WH2rG1qkfQge4pA6BDM0AcKoc1CoJBZQ4TvHeh.RadC', 'Student', 'Active', '2026-04-21 01:35:56', NULL),
(135, 'Jonas', 'Castro', 'jonascastro@gmail.com', '$2y$10$VxLuaZBTUtTFJvdzmpG96.Grc52q1CYTCtMsDooqhs1Ri7yo9mPGK', 'Teacher', 'Pending', '2026-04-21 01:37:01', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendancerecords`
--
ALTER TABLE `attendancerecords`
  ADD PRIMARY KEY (`RecordID`),
  ADD UNIQUE KEY `SessionID` (`SessionID`,`StudentID`),
  ADD KEY `StudentID` (`StudentID`);

--
-- Indexes for table `attendancesessions`
--
ALTER TABLE `attendancesessions`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `SubjectID` (`SubjectID`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD UNIQUE KEY `StudentID` (`StudentID`,`SubjectID`),
  ADD KEY `SubjectID` (`SubjectID`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_email_otp` (`email`,`otp`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`StudentID`),
  ADD UNIQUE KEY `StudentNumber` (`StudentNumber`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`SubjectID`),
  ADD UNIQUE KEY `SubjectCode` (`SubjectCode`),
  ADD KEY `TeacherID` (`TeacherID`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`TeacherID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendancerecords`
--
ALTER TABLE `attendancerecords`
  MODIFY `RecordID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `attendancesessions`
--
ALTER TABLE `attendancesessions`
  MODIFY `SessionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `StudentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `SubjectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `TeacherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendancerecords`
--
ALTER TABLE `attendancerecords`
  ADD CONSTRAINT `attendancerecords_ibfk_1` FOREIGN KEY (`SessionID`) REFERENCES `attendancesessions` (`SessionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendancerecords_ibfk_2` FOREIGN KEY (`StudentID`) REFERENCES `students` (`StudentID`) ON DELETE CASCADE;

--
-- Constraints for table `attendancesessions`
--
ALTER TABLE `attendancesessions`
  ADD CONSTRAINT `attendancesessions_ibfk_1` FOREIGN KEY (`SubjectID`) REFERENCES `subjects` (`SubjectID`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `students` (`StudentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`SubjectID`) REFERENCES `subjects` (`SubjectID`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_email` FOREIGN KEY (`email`) REFERENCES `users` (`Email`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`TeacherID`) REFERENCES `teachers` (`TeacherID`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
