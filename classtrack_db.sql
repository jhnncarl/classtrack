-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 03:13 AM
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
(20, 61, 68, '2026-04-07 17:51:28', 'Present'),
(21, 62, 69, '2026-04-07 19:07:44', 'Present'),
(22, 62, 68, '2026-04-07 19:07:56', 'Present');

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
(59, 24, '2026-04-06', '19:43:09', '19:43:14', 'Closed'),
(60, 19, '2026-04-07', '15:12:02', '15:12:06', 'Closed'),
(61, 24, '2026-04-07', '17:50:52', '17:51:41', 'Closed'),
(62, 24, '2026-04-07', '19:07:36', '19:08:05', 'Closed'),
(63, 30, '2026-04-09', '08:28:16', '08:28:20', 'Closed'),
(64, 19, '2026-04-09', '08:55:08', '08:55:12', 'Closed');

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
(69, 75, '2022-78960', 'BSIT', 3, 'uploads/qrcodes/Student_2022-78960.png');

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
(31, 'nwn9ndpz', 'Data Mining', 6, 'Thursday, 1:00PM - 2:00PM', 'IT243', '3A');

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
(6, 74, 'Computer Studies');

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
(74, 'Rodmarc', 'Villaflores', 'rodmarcariza@gmail.com', '$2y$10$qZfNgLyadHnWfPdMsj5qdu9s2OerEethR7XdKe7.liRN03xOMpVIG', 'Teacher', 'Active', '2026-03-28 05:56:07', 'uploads/profiles/profile_74_1774677821.jpg'),
(75, 'Kean Andre', 'Maglasang', 'keanandre@gmail.com', '$2y$10$TPXXPAvk7wYdq1XBK72v9OgoJUkUq14lTV6Kxjps.QnAnxy0r0/cy', 'Student', 'Active', '2026-04-02 10:21:28', NULL);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `attendancerecords`
--
ALTER TABLE `attendancerecords`
  MODIFY `RecordID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `attendancesessions`
--
ALTER TABLE `attendancesessions`
  MODIFY `SessionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `StudentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `SubjectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `TeacherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
