CREATE TABLE `bell_logs` (
  `id` int(11) NOT NULL,
  `mac_address` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `triggered_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bell_logs`
--



-- --------------------------------------------------------

--
-- Table structure for table `bell_queue`
--

CREATE TABLE `bell_queue` (
  `id` int(11) NOT NULL,
  `mac_address` varchar(50) NOT NULL,
  `duration` int(11) DEFAULT 5,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `bell_type` varchar(20) DEFAULT 'kontinu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday`
--

CREATE TABLE `holiday` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL DEFAULT '0000-00-00',
  `nama` varchar(40) NOT NULL,
  `keterangan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `holiday`
--

INSERT INTO `holiday` (`id`, `tanggal`, `nama`, `keterangan`) VALUES
(5, '2026-01-01', 'Tahun Baru 2026 Masehi', 'Libur Nasional 2026'),
(6, '2026-01-16', 'Isra Mikraj Nabi Muhammad SAW', 'Libur Nasional 2026'),
(7, '2026-02-17', 'Tahun Baru Imlek 2577 Kongzili', 'Libur Nasional 2026'),
(8, '2026-03-19', 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'Libur Nasional 2026'),
(9, '2026-03-20', 'Hari Raya Idul Fitri 1447 Hijriah', 'Libur Nasional 2026'),
(10, '2026-03-21', 'Hari Raya Idul Fitri 1447 Hijriah', 'Libur Nasional 2026'),
(11, '2026-04-03', 'Wafat Yesus Kristus', 'Libur Nasional 2026'),
(12, '2026-04-05', 'Hari Paskah', 'Libur Nasional 2026'),
(13, '2026-05-01', 'Hari Buruh Internasional', 'Libur Nasional 2026'),
(14, '2026-05-14', 'Kenaikan Yesus Kristus', 'Libur Nasional 2026'),
(15, '2026-05-27', 'Hari Raya Idul Adha 1447 Hijriah', 'Libur Nasional 2026'),
(16, '2026-05-31', 'Hari Raya Waisak 2570 BE', 'Libur Nasional 2026'),
(17, '2026-06-01', 'Hari Lahir Pancasila', 'Libur Nasional 2026'),
(18, '2026-06-16', 'Tahun Baru Islam 1448 Hijriah', 'Libur Nasional 2026'),
(19, '2026-08-17', 'Proklamasi Kemerdekaan RI', 'Libur Nasional 2026'),
(20, '2026-08-25', 'Maulid Nabi Muhammad SAW', 'Libur Nasional 2026'),
(22, '2026-12-25', 'Hari Raya Natal', 'Libur Nasional 2026');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `jam` time NOT NULL,
  `keterangan` varchar(100) NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 5,
  `bell_type` varchar(20) DEFAULT 'kontinu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `jam`, `keterangan`, `duration`, `bell_type`) VALUES
(1, '08:31:00', 'Masuk Kerja', 30, 'kontinu'),
(2, '12:00:00', 'Istirahat Siang', 15, 'putus'),
(3, '13:00:00', 'Masuk Kembali', 30, 'putus'),
(4, '17:00:00', 'Jam Pulang', 5, 'kontinu');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bell_logs`
--
ALTER TABLE `bell_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bell_queue`
--
ALTER TABLE `bell_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holiday`
--
ALTER TABLE `holiday`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bell_logs`
--
ALTER TABLE `bell_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `bell_queue`
--
ALTER TABLE `bell_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `holiday`
--
ALTER TABLE `holiday`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
