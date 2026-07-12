-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2026 at 03:10 PM
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
-- Database: `restaurant_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_categories`
--

DROP TABLE IF EXISTS `about_categories`;
CREATE TABLE `about_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_categories`
--

INSERT INTO `about_categories` (`id`, `name`, `slug`) VALUES
(1, 'Câu chuyện', 'cau-chuyen'),
(2, 'Đội ngũ', 'doi-ngu');

-- --------------------------------------------------------

--
-- Table structure for table `about_comments`
--

DROP TABLE IF EXISTS `about_comments`;
CREATE TABLE `about_comments` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 0,
  `author_name` varchar(100) NOT NULL DEFAULT 'Ẩn danh',
  `author_ip` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `about_comment_bans`
--

DROP TABLE IF EXISTS `about_comment_bans`;
CREATE TABLE `about_comment_bans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ban_type` enum('ip','account') NOT NULL DEFAULT 'ip',
  `user_ip` varchar(45) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `banned_until` datetime NOT NULL,
  `banned_by` varchar(100) DEFAULT 'Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `about_comment_likes`
--

DROP TABLE IF EXISTS `about_comment_likes`;
CREATE TABLE `about_comment_likes` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `about_comment_reports`
--

DROP TABLE IF EXISTS `about_comment_reports`;
CREATE TABLE `about_comment_reports` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `user_ip` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_comment_reports`
--

INSERT INTO `about_comment_reports` (`id`, `comment_id`, `user_id`, `reason`, `user_ip`, `created_at`, `status`) VALUES
(1, 33, 2, 'aaaaaaaaaaa', '::1', '2026-05-17 14:37:50', 'processed'),
(2, 35, 2, '...........', '::1', '2026-05-17 15:38:18', 'processed');

-- --------------------------------------------------------

--
-- Table structure for table `about_content`
--

DROP TABLE IF EXISTS `about_content`;
CREATE TABLE `about_content` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `milestone_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `publish_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_content`
--

INSERT INTO `about_content` (`id`, `category_id`, `title`, `slug`, `content`, `thumbnail`, `display_order`, `is_pinned`, `status`, `milestone_text`, `created_at`, `publish_date`) VALUES
(1, 1, 'Khởi Nguồn Đam Mê', 'khoi-nguon-dam-me', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">M</span>ọi câu chuyện vĩ đại đều bắt đầu từ một khoảnh khắc nhỏ bé, và hành trình của Restaurantly cũng không ngoại lệ. Vào một buổi chiều muộn năm 2015, khi ánh hoàng hôn đổ những vệt dài qua khung cửa sổ của một căn bếp nhỏ bé tại Paris, nhóm sáng lập của chúng tôi đã cùng nhau chia sẻ một bữa ăn tự nấu. Đó không phải là một bữa tiệc xa hoa, mà chỉ là những món ăn đơn giản được chuẩn bị bằng cả trái tim, cùng với một chai vang đỏ tuyệt hảo.</p>\n<p>Chính trong không gian ấm cúng và đầy cảm hứng ấy, một ý tưởng táo bạo đã nảy mầm: Tại sao không tạo ra một không gian ẩm thực nơi mọi thực khách đều có thể cảm nhận được sự ấm áp, tinh tế và trọn vẹn như chính bữa ăn này? Một nơi không chỉ phục vụ thức ăn, mà còn kiến tạo những ký ức.</p>\n<blockquote style=\"border-left: 4px solid #cda45e; padding-left: 20px; margin: 30px 0; font-style: italic; color: #cda45e; font-size: 1.2em; font-family: \'Playfair Display\', serif;\">\n\"Ẩm thực thực thụ không chỉ nằm ở hương vị đánh thức vị giác, mà còn ở cách nó chạm đến tận cùng cảm xúc của con người.\"\n</blockquote>\n<p>Từ ý tưởng ban đầu đó, chúng tôi đã dành vô số ngày đêm để lên kế hoạch, nghiên cứu nghệ thuật ẩm thực Pháp và châu Âu, tìm kiếm những nguồn nguyên liệu tinh túy nhất. Chúng tôi khao khát mang đến một trải nghiệm Fine Dining hoàn toàn khác biệt: sang trọng, đẳng cấp nhưng không hề xa cách. Khởi nguồn đam mê ấy chính là ngọn lửa cháy rực rỡ nhất, thắp sáng con đường mà Restaurantly sẽ bước đi trong suốt những năm tháng tiếp theo.</p>', 'journey_2015_1782804583746.png', 0, 0, 1, '2015', '2026-06-30 07:33:04', '2026-06-30'),
(2, 1, 'Đặt Viên Gạch Đầu Tiên', 'dat-vien-gach-dau-tien', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">N</span>ăm 2016 đánh dấu một cột mốc mang tính bước ngoặt khi Restaurantly chính thức khai trương cơ sở đầu tiên. Vượt qua vô vàn khó khăn của những ngày đầu khởi nghiệp—từ việc thiết kế không gian sao cho toát lên vẻ cổ điển vượt thời gian, đến việc tuyển chọn đội ngũ nhân sự không chỉ giỏi chuyên môn mà còn có chung nhịp đập đam mê.</p>\n<p>Chúng tôi đã chọn một mặt bằng khiêm tốn nhưng đậm chất thơ, nằm ẩn mình yên tĩnh giữa nhịp sống đô thị hối hả. Quá trình cải tạo kéo dài hơn 6 tháng ròng rã. Từng viên gạch ốp lát, từng mảng tường màu tối, cho đến hệ thống đèn chùm vàng ấm áp đều được chăm chút tỉ mỉ, nhằm đảm bảo mỗi góc nhỏ đều toát lên sự sang trọng và ấm cúng.</p>\n<p>Ngày mở cửa đầu tiên, không có những chiến dịch quảng cáo rầm rộ hay những bữa tiệc khai trương ồn ào. Chúng tôi chỉ lặng lẽ mở cửa đón những vị khách đầu tiên bằng nụ cười chân thành và những món ăn được chuẩn bị hoàn hảo nhất. Chính chất lượng vượt trội và dịch vụ tận tâm đã dần tạo nên một làn sóng truyền miệng mạnh mẽ. Viên gạch đầu tiên đã được đặt xuống vô cùng vững chãi, trở thành nền móng kiên cố cho tòa tháp thành công sau này.</p>', 'journey_2016_1782804604959.png', 1, 0, 1, '2016', '2026-06-30 07:33:04', '2026-06-30'),
(3, 1, 'Món Ăn Signature Ra Đời', 'mon-an-signature-ra-doi', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">Đ</span>ối với bất kỳ nhà hàng Fine Dining nào, việc sở hữu một món ăn \"Signature\" (món ăn biểu tượng) không chỉ là lời khẳng định về đẳng cấp tay nghề, mà còn là linh hồn của thương hiệu. Năm 2017, Bếp trưởng tài năng của chúng tôi đã quyết định tạo ra một kiệt tác ẩm thực thực sự, một món ăn mà bất cứ ai nếm thử một lần sẽ không bao giờ quên.</p>\n<p>Sau hàng trăm lần thử nghiệm thất bại, từ việc điều chỉnh tỷ lệ gia vị, thời gian áp chảo, cho đến việc tìm kiếm nguồn thịt bò Wagyu hảo hạng nhất từ Nhật Bản kết hợp cùng nấm Truffle đen quý hiếm, món Bò Wellington phong cách Restaurantly đã chính thức ra đời.</p>\n<ul style=\"list-style: none; padding-left: 0; margin-top: 20px;\">\n<li style=\"margin-bottom: 10px; display: flex; align-items: flex-start;\"><span style=\"color: #cda45e; margin-right: 10px;\">✦</span> Lớp vỏ bánh ngàn lớp vàng óng, giòn rụm đến mức tan ngay trên đầu lưỡi.</li>\n<li style=\"margin-bottom: 10px; display: flex; align-items: flex-start;\"><span style=\"color: #cda45e; margin-right: 10px;\">✦</span> Lớp nấm Duxelles bùi béo, thơm lừng quyện chặt lấy thớ thịt bò mềm mại, mọng nước.</li>\n<li style=\"margin-bottom: 10px; display: flex; align-items: flex-start;\"><span style=\"color: #cda45e; margin-right: 10px;\">✦</span> Nước sốt vang đỏ cô đặc sóng sánh, tạo nên bản giao hưởng hương vị hoàn hảo.</li>\n</ul>\n<p style=\"margin-top: 20px;\">Sự tinh tế trong từng chi tiết đã biến món ăn này thành một hiện tượng. Món Ăn Signature không chỉ chinh phục được những vị thực khách khó tính nhất, mà còn nhanh chóng đưa tên tuổi của Restaurantly phủ sóng rộng rãi khắp giới mộ điệu ẩm thực.</p>', 'journey_2017_1782804614736.png', 2, 0, 1, '2017', '2026-06-30 07:33:04', '2026-06-30'),
(4, 1, 'Giải Thưởng Ẩm Thực Đầu Tiên', 'giai-thuong-am-thuc-dau-tien', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">N</span>ăm 2018 là một năm ngập tràn niềm vui và những thành quả ngọt ngào. Những nỗ lực không mệt mỏi của toàn bộ đội ngũ Restaurantly đã chính thức được giới chuyên môn công nhận khi nhà hàng được vinh danh tại lễ trao giải Ẩm Thực Vàng khu vực.</p>\n<p>Đó là một đêm không thể nào quên. Khi tên của Restaurantly được xướng lên ở hạng mục \"Nhà Hàng Fine Dining Có Dịch Vụ Tốt Nhất Năm\", cả khán phòng như vỡ òa trong tiếng vỗ tay. Giải thưởng này không chỉ là một chiếc cúp pha lê lấp lánh để trưng bày trên kệ, mà nó là minh chứng rõ ràng nhất cho triết lý kinh doanh mà chúng tôi luôn theo đuổi: Lấy sự hài lòng tuyệt đối của khách hàng làm trung tâm.</p>\n<blockquote style=\"border-left: 4px solid #cda45e; padding-left: 20px; margin: 30px 0; font-style: italic; color: #cda45e; font-size: 1.2em; font-family: \'Playfair Display\', serif;\">\n\"Vinh quang không làm chúng tôi tự mãn, nó chỉ nhắc nhở chúng tôi rằng: Tiêu chuẩn của ngày hôm nay phải là bước đệm cho sự hoàn hảo của ngày mai.\"\n</blockquote>\n<p>Giải thưởng đầu tiên này đã mở ra những cánh cửa mới, thu hút không chỉ những tín đồ ẩm thực địa phương mà còn cả những thực khách quốc tế. Nó tiếp thêm một nguồn động lực vô cùng to lớn để đội ngũ đầu bếp và nhân viên phục vụ không ngừng hoàn thiện bản thân, vươn tới những đỉnh cao mới.</p>', 'journey_2018_1782804657697.png', 3, 0, 1, '2018', '2026-06-30 07:33:04', '2026-06-30'),
(5, 1, 'Mở Rộng Không Gian', 'mo-rong-khong-gian', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">S</span>ự bùng nổ về danh tiếng và lượng khách hàng đã đặt Restaurantly trước một bài toán vô cùng nan giải vào năm 2019: Làm sao để phục vụ số lượng thực khách ngày càng đông đảo mà vẫn giữ nguyên được sự tĩnh lặng, riêng tư và chất lượng dịch vụ đẳng cấp?</p>\n<p>Quyết định mở rộng không gian đã được đưa ra. Tuy nhiên, thay vì chỉ đơn thuần là cơi nới hoặc thuê một mặt bằng rộng lớn hơn, chúng tôi chọn cách mua lại không gian liền kề và thực hiện một cuộc đại tu kiến trúc. Từng chi tiết trong khu vực mới đều được thiết kế để liền mạch hoàn hảo với không gian cũ, tạo nên một tổng thể bề thế nhưng vẫn vô cùng duyên dáng.</p>\n<p>Đặc biệt, trong lần mở rộng này, chúng tôi đã bổ sung thêm khu vực <strong>Private Dining Room (Phòng tiệc riêng tư)</strong> dành riêng cho các buổi gặp mặt thượng lưu, những cuộc đàm phán kinh doanh hoặc những bữa tiệc cầu hôn lãng mạn. Hầm rượu vang (Wine Cellar) cũng được nâng cấp đáng kể, quy tụ hàng ngàn chai vang hảo hạng từ các vùng đất trứ danh trên thế giới, sẵn sàng làm hài lòng những vị khách am tường nhất.</p>', 'journey_2019_1782804667920.png', 4, 0, 1, '2019', '2026-06-30 07:33:04', '2026-06-30'),
(6, 1, 'Vượt Qua Thử Thách', 'vuot-qua-thu-thach', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">K</span>hông có con đường nào trải đầy hoa hồng mà không có chông gai. Năm 2020 ập đến mang theo những biến động toàn cầu chưa từng có tiền lệ. Ngành F&B nói chung và mảng Fine Dining nói riêng phải đối mặt với những thử thách sinh tử.</p>\n<p>Nhà hàng buộc phải tạm đóng cửa để đảm bảo an toàn cho cộng đồng. Đó là những ngày tháng tĩnh lặng đến nao lòng, những bộ bàn ghế phủ khăn, ánh đèn chùm tắt lịm. Nhưng trong chính khoảng thời gian u tối nhất, tinh thần đoàn kết và ý chí kiên cường của gia đình Restaurantly lại tỏa sáng rực rỡ hơn bao giờ hết. Chúng tôi quyết định không ngồi yên chờ đợi.</p>\n<p>Ban lãnh đạo cam kết giữ lại toàn bộ 100% nhân sự. Các bếp trưởng tận dụng thời gian này để nghiên cứu sâu hơn về kỹ thuật lên men, bảo quản thực phẩm và sáng tạo ra những công thức hoàn toàn mới. Chúng tôi cũng khởi xướng mô hình \"Fine Dining Tại Gia\", mang những set menu cao cấp cùng hướng dẫn trình bày chi tiết đến tận bàn ăn của thực khách. Thử thách khắc nghiệt không quật ngã được chúng tôi, ngược lại, nó rèn giũa Restaurantly trở thành một tập thể mạnh mẽ, sắc bén và linh hoạt hơn bao giờ hết.</p>', 'journey_2020_1782804725707.png', 5, 0, 1, '2020', '2026-06-30 07:33:04', '2026-06-30'),
(7, 1, 'Trở Lại Mạnh Mẽ', 'tro-lai-manh-me', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">N</span>ăm 2021, khi những tia nắng ấm áp của cuộc sống bình thường mới bắt đầu le lói, Restaurantly đã sẵn sàng cho một sự trở lại bùng nổ. Chúng tôi mở cửa đón khách trở lại không chỉ với một diện mạo được làm mới, mà còn mang theo một tinh thần cống hiến mãnh liệt.</p>\n<p>Thực đơn \"Tái Sinh\" (The Renaissance Menu) được ra mắt như một lời tri ân sâu sắc gửi đến những khách hàng đã luôn đồng hành và chờ đợi chúng tôi. Mỗi món ăn trong thực đơn là một câu chuyện về sức sống mãnh liệt của thiên nhiên, sử dụng những nguyên liệu địa phương tươi ngon nhất, kết hợp cùng kỹ thuật chế biến hiện đại của châu Âu.</p>\n<p>Sự trở lại của Restaurantly đã tạo ra một tiếng vang lớn. Hàng ngàn lượt đặt bàn đổ về, những buổi tối kín chỗ kéo dài hàng tháng trời. Hơn cả việc thưởng thức đồ ăn, thực khách đến với chúng tôi để ăn mừng sự sống, ăn mừng những cuộc hội ngộ, và đắm chìm trong thứ cảm giác xa xỉ, bình yên mà họ đã khao khát bấy lâu. Chúng tôi đã trở lại, mạnh mẽ, trưởng thành và rực rỡ hơn bao giờ hết.</p>', 'journey_2021_1782804708461.png', 6, 0, 1, '2021', '2026-06-30 07:33:04', '2026-06-30'),
(8, 1, 'Vươn Tới Ngôi Sao Michelin', 'vuon-toi-ngoi-sao-michelin', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">Đ</span>ỉnh cao của nghệ thuật ẩm thực thế giới luôn gọi tên những Ngôi sao Michelin danh giá. Từ những ngày đầu tiên thành lập, đó vẫn luôn là một giấc mơ, một ngọn hải đăng soi sáng con đường chúng tôi đi. Và năm 2022, khoảnh khắc lịch sử ấy cuối cùng cũng đã đến.</p>\n<p>Khi thư thông báo chính thức từ cẩm nang Michelin danh giá được gửi tới, cả nhà hàng như chìm trong một sự vỡ òa không thể kìm nén. Ngôi sao Michelin đầu tiên đã được trao cho Restaurantly, đánh dấu sự công nhận ở đẳng cấp cao nhất trên bản đồ ẩm thực quốc tế.</p>\n<blockquote style=\"border-left: 4px solid #cda45e; padding-left: 20px; margin: 30px 0; font-style: italic; color: #cda45e; font-size: 1.2em; font-family: \'Playfair Display\', serif;\">\n\"Ngôi sao này không thuộc về một cá nhân nào. Nó thuộc về tập thể những người đã cống hiến tuổi thanh xuân bên chảo lửa, những người tỉ mỉ lau từng chiếc ly pha lê, và cả những thực khách đã luôn tin tưởng chúng tôi.\"\n</blockquote>\n<p>Ngôi sao Michelin mang theo một áp lực vô hình nhưng vô cùng ngọt ngào. Nó nhắc nhở chúng tôi rằng sự hoàn hảo không phải là đích đến, mà là một hành trình liên tục. Kể từ khoảnh khắc đó, chất lượng món ăn, phong cách phục vụ và sự sáng tạo tại Restaurantly lại tiếp tục được nâng lên một tầm cao hoàn toàn khác biệt.</p>', 'journey_2022_1782804736072.png', 7, 0, 1, '2022', '2026-06-30 07:33:04', '2026-06-30'),
(9, 1, 'Trải Nghiệm Đẳng Cấp Thượng Lưu', 'trai-nghiem-dang-cap-thuong-luu', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">N</span>ăm 2023, chúng tôi quyết định định nghĩa lại khái niệm về sự xa xỉ trong ẩm thực bằng việc ra mắt dịch vụ <strong>Bespoke Dining</strong> – Trải nghiệm ẩm thực được cá nhân hóa đến mức tuyệt đối. Tại Restaurantly, chúng tôi hiểu rằng giới thượng lưu không chỉ tìm kiếm một bữa ăn ngon, họ tìm kiếm một trải nghiệm độc bản, phản ánh đúng cá tính và đẳng cấp của chính họ.</p>\n<p>Với Bespoke Dining, thực khách có quyền can thiệp vào toàn bộ quá trình thiết kế thực đơn. Bếp trưởng điều hành sẽ có những cuộc gặp gỡ riêng để tìm hiểu về sở thích, dị ứng, và câu chuyện mà thực khách muốn truyền tải qua bữa tiệc. Chúng tôi sẵn sàng nhập khẩu những nguyên liệu hiếm có nhất trên thế giới chỉ trong vòng 48 giờ để phục vụ cho một bàn tiệc duy nhất.</p>\n<p>Từ việc thêu tên khách hàng lên khăn ăn, thiết kế thực đơn bọc da dập nổi mạ vàng, cho đến việc mời các chuyên gia Sommelier đẳng cấp quốc tế trực tiếp tư vấn và ghép nối rượu vang (Wine Pairing)... Mỗi chi tiết đều được thiết kế tỉ mỉ, tạo nên một bản giao hưởng của sự phồn hoa và nghệ thuật hiếu khách đỉnh cao.</p>', 'journey_2023_1782804764540.png', 8, 0, 1, '2023', '2026-06-30 07:33:04', '2026-06-30'),
(10, 1, 'Tầm Nhìn Quốc Tế', 'tam-nhin-quoc-te', '<p><span style=\"font-size: 2.5em; float: left; margin-top: -0.1em; margin-right: 0.1em; color: #cda45e; font-family: \'Playfair Display\', serif; line-height: 1;\">G</span>ần một thập kỷ không ngừng vươn lên, Restaurantly giờ đây không chỉ là một nhà hàng, mà đã trở thành một biểu tượng của phong cách sống tinh hoa. Năm 2024, chúng tôi chính thức công bố chiến lược \"Tầm Nhìn Quốc Tế\", mang triết lý ẩm thực độc đáo của mình vượt ra khỏi biên giới.</p>\n<p>Sự thành công vang dội tại thị trường nội địa là bàn đạp vững chắc để chúng tôi tự tin bước ra thế giới. Những kế hoạch nhượng quyền thương hiệu cao cấp, hợp tác cùng các khách sạn 5 sao quốc tế và những chương trình trao đổi đầu bếp toàn cầu đang dần được hiện thực hóa.</p>\n<p>Nhưng dù có phát triển lớn mạnh đến đâu, vươn xa đến phương trời nào, giá trị cốt lõi của Restaurantly vẫn sẽ mãi vẹn nguyên như bữa tối đơn giản của những nhà sáng lập vào năm 2015: <strong>Ẩm thực là sự kết nối chân thành nhất giữa người với người.</strong> Chúng tôi tự hào mang theo di sản ấy, tiếp tục kiến tạo nên những kỳ quan ẩm thực mới, để mỗi thực khách bước qua cánh cửa Restaurantly đều tìm thấy cho mình một trải nghiệm đáng giá đến từng giây phút.</p>', 'journey_2024_1782804776034.png', 9, 0, 1, '2024', '2026-06-30 07:33:04', '2026-06-30');

-- --------------------------------------------------------

--
-- Table structure for table `about_likes`
--

DROP TABLE IF EXISTS `about_likes`;
CREATE TABLE `about_likes` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_ip` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_likes`
--

INSERT INTO `about_likes` (`id`, `content_id`, `user_ip`, `user_id`, `created_at`) VALUES
(17, 1, '::1', 2, '2026-06-05 03:36:38');

-- --------------------------------------------------------

--
-- Table structure for table `about_saved_posts`
--

DROP TABLE IF EXISTS `about_saved_posts`;
CREATE TABLE `about_saved_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `about_shares`
--

DROP TABLE IF EXISTS `about_shares`;
CREATE TABLE `about_shares` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `platform` varchar(50) DEFAULT 'link',
  `user_ip` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_shares`
--

INSERT INTO `about_shares` (`id`, `content_id`, `platform`, `user_ip`, `created_at`) VALUES
(1, 1, 'view', '::1', '2026-05-14 14:28:16'),
(2, 1, 'view', '::1', '2026-05-14 14:28:20'),
(3, 1, 'view', '::1', '2026-05-14 14:28:24'),
(4, 1, 'view', '::1', '2026-05-14 14:38:21'),
(5, 1, 'facebook', '::1', '2026-05-14 14:38:28'),
(6, 1, 'link', '::1', '2026-05-14 14:38:36'),
(7, 1, 'facebook', '::1', '2026-05-14 14:38:39'),
(8, 1, 'view', '::1', '2026-05-14 14:39:34'),
(9, 1, 'view', '::1', '2026-05-14 14:39:40'),
(10, 1, 'view', '::1', '2026-05-14 14:39:46'),
(11, 1, 'view', '::1', '2026-05-14 14:39:48'),
(12, 1, 'link', '::1', '2026-05-14 14:40:19'),
(13, 1, 'link', '::1', '2026-05-14 14:40:20'),
(14, 1, 'link', '::1', '2026-05-14 14:40:22'),
(15, 1, 'view', '::1', '2026-05-14 14:40:32'),
(16, 1, 'view', '::1', '2026-05-14 14:40:41'),
(17, 1, 'facebook', '::1', '2026-05-14 14:40:42'),
(18, 1, 'view', '::1', '2026-05-14 14:41:12'),
(19, 1, 'view', '::1', '2026-05-14 14:41:48'),
(20, 1, 'view', '::1', '2026-05-14 14:43:32'),
(21, 1, 'view', '::1', '2026-05-14 14:44:03'),
(22, 1, 'view', '::1', '2026-05-14 14:48:33'),
(23, 1, 'view', '::1', '2026-05-14 14:49:28'),
(24, 1, 'view', '::1', '2026-05-14 14:52:03'),
(25, 1, 'view', '::1', '2026-05-14 14:52:10'),
(26, 1, 'view', '::1', '2026-05-14 14:52:12'),
(27, 1, 'view', '::1', '2026-05-14 14:52:14'),
(28, 1, 'view', '::1', '2026-05-14 14:52:20'),
(29, 1, 'view', '::1', '2026-05-14 14:52:53'),
(30, 1, 'view', '::1', '2026-05-14 14:53:35'),
(31, 1, 'facebook', '::1', '2026-05-14 14:53:41'),
(32, 1, 'link', '::1', '2026-05-14 14:53:44'),
(33, 1, 'view', '::1', '2026-05-14 14:54:07'),
(34, 1, 'view', '::1', '2026-05-14 14:56:15'),
(35, 1, 'view', '::1', '2026-05-14 14:56:36'),
(36, 1, 'view', '::1', '2026-05-14 14:56:41'),
(37, 1, 'view', '::1', '2026-05-14 15:01:09'),
(38, 1, 'view', '::1', '2026-05-14 15:02:41'),
(39, 1, 'view', '::1', '2026-05-14 15:10:44'),
(40, 1, 'view', '::1', '2026-05-14 18:24:09'),
(41, 1, 'view', '::1', '2026-05-14 18:24:13'),
(42, 1, 'view', '::1', '2026-05-14 18:24:16'),
(43, 1, 'view', '::1', '2026-05-14 18:26:30'),
(44, 1, 'view', '::1', '2026-05-14 18:31:05'),
(45, 1, 'view', '::1', '2026-05-14 18:32:02'),
(46, 1, 'view', '::1', '2026-05-14 18:49:45'),
(47, 1, 'view', '::1', '2026-05-14 18:50:44'),
(48, 1, 'view', '::1', '2026-05-14 18:51:33'),
(49, 1, 'view', '::1', '2026-05-14 18:51:58'),
(50, 1, 'view', '::1', '2026-05-14 18:52:08'),
(51, 1, 'view', '::1', '2026-05-14 18:52:46'),
(52, 1, 'view', '::1', '2026-05-14 18:54:15'),
(53, 1, 'view', '::1', '2026-05-14 18:54:57'),
(54, 1, 'view', '::1', '2026-05-14 18:56:23'),
(55, 1, 'view', '::1', '2026-05-14 18:56:43'),
(56, 1, 'view', '::1', '2026-05-14 18:59:11'),
(57, 1, 'view', '::1', '2026-05-14 18:59:57'),
(58, 1, 'view', '::1', '2026-05-14 19:01:02'),
(59, 1, 'view', '::1', '2026-05-14 19:01:04'),
(60, 1, 'view', '::1', '2026-05-14 19:01:20'),
(61, 1, 'view', '::1', '2026-05-14 19:01:22'),
(62, 1, 'view', '::1', '2026-05-14 19:02:52'),
(63, 1, 'view', '::1', '2026-05-14 19:03:17'),
(64, 1, 'view', '::1', '2026-05-14 19:03:31'),
(65, 1, 'view', '::1', '2026-05-14 19:05:35'),
(66, 1, 'view', '::1', '2026-05-14 19:05:38'),
(67, 1, 'view', '::1', '2026-05-14 19:08:04'),
(68, 1, 'view', '::1', '2026-05-14 19:08:06'),
(69, 1, 'view', '::1', '2026-05-14 19:08:27'),
(70, 1, 'view', '::1', '2026-05-14 19:08:28'),
(71, 1, 'view', '::1', '2026-05-14 19:09:53'),
(72, 1, 'view', '::1', '2026-05-14 19:10:07'),
(73, 1, 'view', '::1', '2026-05-14 19:10:22'),
(74, 1, 'view', '::1', '2026-05-14 19:10:28'),
(75, 1, 'view', '::1', '2026-05-14 19:10:35'),
(76, 1, 'view', '::1', '2026-05-14 19:10:39'),
(77, 1, 'view', '::1', '2026-05-14 19:11:00'),
(78, 1, 'view', '::1', '2026-05-14 19:12:11'),
(79, 1, 'view', '::1', '2026-05-14 19:12:13'),
(80, 1, 'view', '::1', '2026-05-14 19:12:45'),
(81, 1, 'view', '::1', '2026-05-14 19:12:51'),
(82, 1, 'view', '::1', '2026-05-14 19:12:52'),
(83, 1, 'view', '::1', '2026-05-14 19:12:54'),
(84, 1, 'view', '::1', '2026-05-14 19:13:08'),
(85, 1, 'view', '::1', '2026-05-14 19:13:37'),
(86, 1, 'view', '::1', '2026-05-14 19:21:47'),
(87, 1, 'view', '::1', '2026-05-14 19:21:50'),
(88, 1, 'view', '::1', '2026-05-14 19:24:17'),
(89, 1, 'view', '::1', '2026-05-14 19:24:27'),
(90, 1, 'view', '::1', '2026-05-14 19:25:18'),
(91, 1, 'view', '::1', '2026-05-14 19:27:21'),
(92, 1, 'view', '::1', '2026-05-14 19:27:23'),
(93, 1, 'view', '::1', '2026-05-14 19:30:40'),
(94, 1, 'view', '::1', '2026-05-14 19:31:31'),
(95, 1, 'view', '::1', '2026-05-14 19:31:41'),
(96, 1, 'view', '::1', '2026-05-14 19:32:36'),
(97, 1, 'view', '::1', '2026-05-14 19:40:10'),
(98, 1, 'view', '::1', '2026-05-14 19:40:30'),
(99, 1, 'view', '::1', '2026-05-14 19:48:43'),
(100, 1, 'view', '::1', '2026-05-14 19:53:09'),
(101, 1, 'view', '::1', '2026-05-14 19:55:48'),
(102, 1, 'view', '::1', '2026-05-14 20:00:46'),
(103, 1, 'view', '::1', '2026-05-14 20:00:54'),
(104, 1, 'view', '::1', '2026-05-14 20:01:23'),
(105, 1, 'view', '::1', '2026-05-14 20:03:02'),
(106, 1, 'view', '::1', '2026-05-14 20:06:08'),
(107, 1, 'view', '::1', '2026-05-14 20:07:49'),
(108, 1, 'view', '::1', '2026-05-14 20:08:06'),
(109, 1, 'view', '::1', '2026-05-14 20:08:20'),
(110, 1, 'view', '::1', '2026-05-14 20:10:03'),
(111, 1, 'view', '::1', '2026-05-14 20:12:41'),
(112, 1, 'view', '::1', '2026-05-14 20:14:27'),
(113, 1, 'view', '::1', '2026-05-14 20:15:43'),
(114, 1, 'view', '::1', '2026-05-17 13:53:04'),
(115, 1, 'view', '::1', '2026-05-17 13:53:52'),
(116, 1, 'view', '::1', '2026-05-17 13:56:19'),
(117, 1, 'view', '::1', '2026-05-17 13:57:07'),
(118, 1, 'view', '::1', '2026-05-17 14:07:32'),
(119, 1, 'view', '::1', '2026-05-17 14:08:22'),
(120, 1, 'view', '::1', '2026-05-17 14:08:54'),
(121, 1, 'view', '::1', '2026-05-17 14:10:22'),
(122, 1, 'view', '::1', '2026-05-17 14:10:25'),
(123, 1, 'view', '::1', '2026-05-17 14:16:36'),
(124, 1, 'view', '::1', '2026-05-17 14:16:40'),
(125, 1, 'view', '::1', '2026-05-17 14:17:58'),
(126, 1, 'view', '::1', '2026-05-17 14:20:42'),
(127, 1, 'view', '::1', '2026-05-17 14:21:54'),
(128, 1, 'view', '::1', '2026-05-17 14:23:55'),
(129, 1, 'view', '::1', '2026-05-17 14:23:58'),
(130, 1, 'view', '::1', '2026-05-17 14:29:27'),
(131, 1, 'view', '::1', '2026-05-17 14:37:03'),
(132, 1, 'view', '::1', '2026-05-17 14:44:00'),
(133, 1, 'view', '::1', '2026-05-17 14:45:04'),
(134, 1, 'view', '::1', '2026-05-17 15:37:40'),
(135, 1, 'view', '::1', '2026-05-17 15:37:44'),
(136, 1, 'view', '::1', '2026-05-17 15:43:01'),
(137, 1, 'view', '::1', '2026-05-17 15:46:03'),
(138, 1, 'view', '::1', '2026-05-17 15:49:44'),
(139, 1, 'view', '::1', '2026-05-17 15:55:06'),
(140, 1, 'view', '::1', '2026-05-20 09:19:50'),
(141, 1, 'view', '::1', '2026-05-20 09:20:00'),
(142, 1, 'view', '::1', '2026-05-20 09:21:25'),
(143, 1, 'view', '::1', '2026-05-20 09:24:19'),
(144, 1, 'view', '::1', '2026-05-20 09:26:02'),
(145, 1, 'view', '::1', '2026-05-20 09:26:05'),
(146, 1, 'view', '::1', '2026-05-20 09:27:41'),
(147, 1, 'view', '::1', '2026-05-20 09:27:52'),
(148, 1, 'view', '::1', '2026-05-20 09:28:11'),
(149, 1, 'view', '::1', '2026-05-20 13:27:47'),
(150, 1, 'view', '::1', '2026-05-20 13:27:57'),
(151, 1, 'view', '::1', '2026-05-21 03:44:49'),
(152, 1, 'view', '::1', '2026-05-21 03:44:54'),
(153, 1, 'view', '::1', '2026-05-21 03:44:58'),
(154, 1, 'view', '::1', '2026-05-23 08:06:35'),
(155, 1, 'view', '::1', '2026-05-24 14:26:22'),
(156, 1, 'view', '::1', '2026-06-05 03:33:28'),
(157, 1, 'view', '::1', '2026-06-05 03:33:44'),
(158, 1, 'view', '::1', '2026-06-05 03:34:49'),
(159, 1, 'view', '::1', '2026-06-05 03:36:34'),
(160, 1, 'view', '::1', '2026-06-05 03:39:13'),
(161, 1, 'view', '::1', '2026-06-07 21:21:17'),
(162, 1, 'view', '::1', '2026-06-08 00:09:47'),
(163, 1, 'view', '::1', '2026-06-08 00:09:57'),
(164, 1, 'view', '::1', '2026-06-08 00:10:04'),
(165, 1, 'view', '::1', '2026-06-09 07:03:31'),
(166, 1, 'view', '::1', '2026-06-09 07:13:53'),
(167, 1, 'view', '::1', '2026-06-11 14:07:17'),
(168, 1, 'view', '::1', '2026-06-11 14:07:25'),
(169, 1, 'view', '::1', '2026-06-13 03:15:55'),
(170, 1, 'view', '::1', '2026-06-16 02:53:58'),
(171, 1, 'view', '::1', '2026-06-28 11:23:40'),
(172, 7, 'view', '::1', '2026-06-29 07:58:00'),
(173, 7, 'view', '::1', '2026-06-29 08:21:39'),
(174, 7, 'view', '::1', '2026-06-29 08:27:39'),
(175, 7, 'view', '::1', '2026-06-29 12:33:49'),
(176, 2, 'view', '::1', '2026-06-29 12:57:05'),
(177, 1, 'view', '::1', '2026-06-30 07:12:15'),
(178, 1, 'view', '::1', '2026-06-30 07:20:23'),
(179, 6, 'view', '::1', '2026-06-30 07:27:50'),
(180, 1, 'view', '::1', '2026-06-30 07:29:40'),
(181, 1, 'view', '::1', '2026-06-30 07:31:47'),
(182, 1, 'view', '::1', '2026-06-30 07:32:12'),
(183, 3, 'view', '::1', '2026-06-30 10:40:32'),
(184, 1, 'view', '::1', '2026-06-30 10:43:23'),
(185, 2, 'view', '::1', '2026-06-30 10:44:31'),
(186, 1, 'view', '::1', '2026-06-30 10:53:14'),
(187, 2, 'view', '::1', '2026-06-30 10:53:18'),
(188, 1, 'view', '::1', '2026-06-30 11:02:50'),
(189, 1, 'view', '::1', '2026-07-01 12:24:26'),
(190, 6, 'view', '::1', '2026-07-01 12:24:56'),
(191, 1, 'view', '::1', '2026-07-01 12:27:19'),
(192, 1, 'view', '::1', '2026-07-01 12:28:52'),
(193, 1, 'view', '::1', '2026-07-01 12:29:10'),
(194, 1, 'view', '::1', '2026-07-01 12:30:16'),
(195, 1, 'view', '::1', '2026-07-01 12:33:26'),
(196, 1, 'view', '::1', '2026-07-01 12:33:39'),
(197, 2, 'view', '::1', '2026-07-01 12:33:53'),
(198, 1, 'view', '::1', '2026-07-01 12:34:57'),
(199, 2, 'view', '::1', '2026-07-01 12:37:42'),
(200, 1, 'view', '::1', '2026-07-01 14:28:25'),
(201, 1, 'view', '::1', '2026-07-06 04:24:40'),
(202, 1, 'view', '::1', '2026-07-06 13:27:26'),
(203, 1, 'view', '::1', '2026-07-06 13:54:00'),
(204, 3, 'view', '::1', '2026-07-07 03:55:00'),
(205, 2, 'view', '::1', '2026-07-07 03:55:07'),
(206, 10, 'view', '::1', '2026-07-09 02:30:00'),
(207, 1, 'view', '::1', '2026-07-12 12:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `font_family` varchar(50) DEFAULT 'Poppins',
  `text_color` varchar(20) DEFAULT '#ffffff',
  `text_align` varchar(20) DEFAULT 'center',
  `font_style` varchar(50) DEFAULT 'normal',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `desc_color` varchar(20) DEFAULT '#eeeeee',
  `desc_font_family` varchar(100) DEFAULT '''Poppins'', sans-serif',
  `desc_font_style` varchar(50) DEFAULT 'normal',
  `title_font_size` int(11) DEFAULT 48,
  `desc_font_size` int(11) DEFAULT 24,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `button_text` varchar(255) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `button_color` varchar(20) DEFAULT '#cda45e',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image_url`, `title`, `description`, `font_family`, `text_color`, `text_align`, `font_style`, `display_order`, `created_at`, `desc_color`, `desc_font_family`, `desc_font_style`, `title_font_size`, `desc_font_size`, `is_active`, `button_text`, `button_link`, `button_color`, `start_date`, `end_date`) VALUES
(2, 'a190d40c2c4231db57f8.jpg', 'Chào mừng bạn đến với Restaurantly', 'Cung cấp những món ăn tuyệt vời trong hơn 18 năm!', '\'Poppins\', sans-serif', '#ffffff', 'left', 'bold', 1, '2026-04-20 12:14:02', '#cda45e', '\'Poppins\', sans-serif', 'normal', 72, 24, 1, 'đặt bàn', 'http://localhost/restaurant-project/booking_service.php?type=table', '#ffffff', NULL, NULL),
(7, '24ee77bb5cbf529050ff.jpg', 'Trãi nghiệm menu thiết kế độc bản', 'Tinh hoa ẩm thực', '\'Poppins\', sans-serif', '#fdfdfc', 'left', 'bold', 2, '2026-04-20 12:20:10', '#ffffff', '\'Poppins\', sans-serif', 'normal', 56, 24, 1, 'Thiết kế riêng', 'http://localhost/restaurant-project/booking_service.php?type=bespoke', '#cda45e', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bespoke_budgets`
--

DROP TABLE IF EXISTS `bespoke_budgets`;
CREATE TABLE `bespoke_budgets` (
  `id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `price_value` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bespoke_budgets`
--

INSERT INTO `bespoke_budgets` (`id`, `label`, `price_value`, `sort_order`) VALUES
(1, 'Thỏa thuận sau khi thiết kế thực đơn', 0, 1),
(2, 'Dưới 1.500.000 đ / khách', 1500000, 2),
(3, '1.500.000 đ - 3.000.000 đ / khách', 2000000, 3),
(4, '3.000.000 đ - 5.000.000 đ / khách', 4000000, 4),
(5, 'Trên 5.000.000 đ / khách (Siêu cao cấp)', 5000000, 5);

-- --------------------------------------------------------

--
-- Table structure for table `bespoke_occasions`
--

DROP TABLE IF EXISTS `bespoke_occasions`;
CREATE TABLE `bespoke_occasions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bespoke_occasions`
--

INSERT INTO `bespoke_occasions` (`id`, `name`, `sort_order`) VALUES
(1, 'Kỷ niệm', 1),
(2, 'Sinh nhật', 2),
(3, 'Cầu hôn', 3),
(4, 'Tiếp khách', 4),
(5, 'Tiệc doanh nghiệp', 5),
(6, 'Khác', 6);

-- --------------------------------------------------------

--
-- Table structure for table `bespoke_styles`
--

DROP TABLE IF EXISTS `bespoke_styles`;
CREATE TABLE `bespoke_styles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bespoke_styles`
--

INSERT INTO `bespoke_styles` (`id`, `name`, `sort_order`) VALUES
(1, 'Tùy Bếp trưởng đề xuất', 1),
(2, 'Ẩm thực Việt Nam Đương Đại ', 2),
(3, 'Ẩm thực Việt Nam Cổ Điển (Traditional Vietnamese)', 3),
(4, 'Ẩm thực Pháp - Việt Đông Dương (Indochine Fusion)', 4),
(5, 'Hải sản Cao cấp (Premium Seafood)', 5),
(6, 'Thực dưỡng & Chay Thượng hạng (Fine Vegetarian)', 6);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `booking_date` datetime NOT NULL,
  `number_of_guests` int(11) NOT NULL DEFAULT 1,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_details`
--

DROP TABLE IF EXISTS `booking_details`;
CREATE TABLE `booking_details` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `excluded_combo_items` varchar(255) DEFAULT NULL,
  `toppings_info` varchar(500) DEFAULT NULL,
  `item_type` enum('food','combo','service') NOT NULL DEFAULT 'food',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','preparing','cooking','ready','served') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_details`
--

INSERT INTO `booking_details` (`id`, `booking_id`, `menu_id`, `excluded_combo_items`, `toppings_info`, `item_type`, `quantity`, `notes`, `price`, `created_at`, `status`) VALUES
(1, 1, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-13 08:17:11', 'pending'),
(2, 2, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-13 08:18:22', 'pending'),
(3, 3, 1, NULL, NULL, 'food', 1, NULL, 400000.00, '2026-05-14 10:00:00', 'pending'),
(4, 3, 2, NULL, NULL, 'food', 1, NULL, 120000.00, '2026-05-14 10:00:00', 'pending'),
(5, 5, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-14 15:07:47', 'pending'),
(6, 5, 2, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-14 15:07:47', 'pending'),
(7, 5, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-14 15:07:47', 'pending'),
(8, 5, 2, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-14 15:07:47', 'pending'),
(9, 6, 8, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-23 06:58:54', 'pending'),
(10, 7, 11, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-24 12:37:30', 'pending'),
(11, 7, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-24 12:37:30', 'pending'),
(12, 8, 10, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-24 12:38:32', 'pending'),
(13, 9, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-24 12:42:37', 'pending'),
(14, 10, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-05-24 13:11:20', 'pending'),
(15, 12, 11, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-01 04:26:57', 'pending'),
(16, 14, 11, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-01 04:36:48', 'pending'),
(17, 15, 11, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-01 09:02:55', 'pending'),
(18, 16, 11, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-01 09:04:01', 'pending'),
(19, 17, 1, NULL, NULL, 'food', 1, 'Độ chín: Medium Rare (Tái vừa).', 0.00, '2026-06-03 12:56:04', 'pending'),
(20, 18, 1, NULL, NULL, 'food', 1, 'Độ chín: Medium Rare (Tái vừa).', 0.00, '2026-06-03 12:57:41', 'pending'),
(21, 19, 11, NULL, NULL, 'food', 1, 'ít ngọt', 0.00, '2026-06-04 02:57:44', 'pending'),
(22, 19, 1, NULL, NULL, 'food', 1, 'Độ chín: Medium Rare (Tái vừa).', 0.00, '2026-06-04 02:57:44', 'pending'),
(23, 20, 11, NULL, NULL, 'food', 1, '', 0.00, '2026-06-04 03:06:56', 'pending'),
(24, 20, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-04 03:06:56', 'pending'),
(25, 21, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-04 03:10:16', 'pending'),
(26, 22, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-04 03:10:48', 'pending'),
(27, 23, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-05 01:21:07', 'pending'),
(28, 24, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-05 01:22:32', 'pending'),
(29, 47, 11, NULL, NULL, 'food', 1, '', 0.00, '2026-06-06 07:40:35', 'pending'),
(30, 48, 11, NULL, NULL, 'food', 1, '', 0.00, '2026-06-06 07:50:57', 'pending'),
(31, 48, 12, NULL, NULL, 'food', 1, '', 0.00, '2026-06-06 07:50:57', 'pending'),
(32, 49, 8, NULL, NULL, 'food', 1, '', 0.00, '2026-06-06 07:51:56', 'pending'),
(33, 50, 4, NULL, NULL, 'food', 1, '', 0.00, '2026-06-06 07:57:08', 'pending'),
(34, 77, 11, NULL, NULL, 'food', 1, '', 0.00, '2026-06-07 20:08:55', 'pending'),
(35, 78, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-07 20:10:39', 'pending'),
(36, 80, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-07 20:29:40', 'pending'),
(37, 81, 12, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-07 20:33:27', 'pending'),
(38, 81, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-07 20:33:27', 'pending'),
(39, 81, 3, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-07 20:33:27', 'pending'),
(40, 82, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-07 23:39:32', 'pending'),
(41, 83, 17, NULL, NULL, 'food', 1, '', 0.00, '2026-06-07 23:43:32', 'pending'),
(42, 84, 11, NULL, NULL, 'food', 1, '', 0.00, '2026-06-08 09:58:47', 'pending'),
(43, 85, 3, NULL, NULL, 'food', 1, '', 0.00, '2026-06-12 02:46:20', 'pending'),
(44, 86, 4, NULL, NULL, 'food', 1, '', 0.00, '2026-06-12 02:52:46', 'pending'),
(45, 86, 16, NULL, NULL, 'food', 1, '', 0.00, '2026-06-12 02:52:46', 'pending'),
(46, 87, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-06-12 02:53:42', 'pending'),
(47, 88, 17, NULL, '40', 'food', 1, '[Topping: Thêm trứng cá hồi (Ikura)]', 0.00, '2026-06-13 05:14:13', 'pending'),
(48, 89, 17, NULL, '40', 'food', 1, '[Topping: Thêm trứng cá hồi (Ikura)]', 0.00, '2026-06-13 05:27:22', 'pending'),
(49, 90, 17, NULL, '40', 'food', 1, '[Topping: Thêm trứng cá hồi (Ikura)]', 0.00, '2026-06-13 05:30:23', 'pending'),
(50, 91, 17, NULL, '40', 'food', 1, '[Topping: Thêm trứng cá hồi (Ikura)]', 0.00, '2026-06-13 05:31:34', 'pending'),
(51, 92, 22, NULL, '7,8', 'food', 1, '[Topping: Ít đá, Ít ngọt (Less Sugar)]', 0.00, '2026-06-15 04:13:33', 'pending'),
(52, 93, 22, NULL, '7,8', 'food', 1, '[Topping: Ít đá, Ít ngọt (Less Sugar)]', 0.00, '2026-06-15 04:21:21', 'pending'),
(53, 94, 15, NULL, '18', 'food', 1, '[Topping: Thêm Trứng cá hồi Ikura]', 0.00, '2026-06-15 04:23:59', 'pending'),
(54, 94, 21, NULL, '6,9', 'food', 1, '[Topping: Không đá, Không đường (No Sugar)]', 0.00, '2026-06-15 04:23:59', 'pending'),
(55, 95, 16, NULL, NULL, 'food', 1, '', 0.00, '2026-06-16 06:33:39', 'pending'),
(56, 95, 12, NULL, '1,19,11', 'food', 1, '[Topping: Tái (Rare), Bánh mì bơ tỏi thêm, Thêm Sốt Tiêu đen]', 0.00, '2026-06-16 06:33:39', 'pending'),
(57, 95, 12, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-16 06:33:39', 'pending'),
(58, 95, 16, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-16 06:33:39', 'pending'),
(1055, 1095, 16, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 07:36:26', 'pending'),
(1056, 1095, 22, NULL, '7', 'food', 1, '[Topping: Ít đá]', 0.00, '2026-06-15 07:36:26', 'pending'),
(1057, 1096, 8, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 07:43:37', 'pending'),
(1058, 1096, 13, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 07:43:37', 'pending'),
(1059, 1097, 3, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 07:49:42', 'pending'),
(1060, 1097, 21, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 07:49:42', 'pending'),
(1061, 1098, 5, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 07:51:04', 'pending'),
(1062, 1098, 20, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 07:51:04', 'pending'),
(1063, 1099, 16, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 09:22:54', 'pending'),
(1064, 1099, 20, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 09:22:54', 'pending'),
(1065, 1100, 3, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 09:24:44', 'pending'),
(1066, 1100, 20, NULL, NULL, 'food', 1, '', 0.00, '2026-06-15 09:24:44', 'pending'),
(1067, 1101, 9, NULL, NULL, 'food', 1, '', 0.00, '2026-06-23 06:50:28', 'pending'),
(1068, 1102, 8, NULL, '17', 'food', 1, '[Topping: Thêm Nấm mỡ tươi]', 0.00, '2026-06-23 07:39:22', 'pending'),
(1069, 1103, 1, NULL, NULL, 'food', 1, 'Độ chín: Medium Rare (Tái vừa).', 0.00, '2026-06-25 12:11:26', 'pending'),
(1070, 1104, 12, NULL, NULL, 'food', 1, '', 0.00, '2026-06-25 13:06:35', 'pending'),
(1071, 1105, 4, NULL, NULL, 'food', 1, '', 0.00, '2026-06-26 11:45:36', 'pending'),
(1072, 1106, 13, NULL, NULL, 'food', 1, '', 0.00, '2026-06-26 11:49:06', 'pending'),
(1073, 1107, 5, NULL, NULL, 'food', 1, '', 0.00, '2026-06-26 12:11:45', 'pending'),
(1074, 1108, 3, NULL, NULL, 'food', 1, '', 0.00, '2026-06-26 12:19:05', 'pending'),
(1075, 1109, 14, NULL, NULL, 'food', 1, '', 0.00, '2026-06-26 12:30:44', 'cooking'),
(1076, 1110, 8, NULL, NULL, 'food', 1, '', 0.00, '2026-06-26 12:37:55', 'cooking'),
(1077, 1111, 9, NULL, NULL, 'food', 1, '', 0.00, '2026-06-26 14:34:53', 'cooking'),
(1078, 1113, 11, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-30 02:40:48', 'pending'),
(1079, 1113, 1, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-30 02:40:48', 'pending'),
(1080, 1113, 13, NULL, NULL, 'food', 1, NULL, 0.00, '2026-06-30 02:40:48', 'pending'),
(1081, 1114, 13, NULL, NULL, 'food', 1, '', 0.00, '2026-06-30 10:45:21', 'pending'),
(1082, 1115, 13, NULL, NULL, 'food', 1, '', 0.00, '2026-06-30 10:46:43', 'pending'),
(1083, 1116, 12, NULL, NULL, 'food', 1, NULL, 0.00, '2026-07-01 14:34:44', 'pending'),
(1084, 1116, 16, NULL, NULL, 'food', 1, NULL, 0.00, '2026-07-01 14:34:44', 'pending'),
(1085, 1122, 1, NULL, '5', 'food', 1, '[Topping: Chín hoàn toàn (Well Done)]', 0.00, '2026-07-02 01:43:07', 'cooking'),
(1086, 1123, 1, NULL, '5', 'food', 1, '[Topping: Chín hoàn toàn (Well Done)]', 0.00, '2026-07-02 01:45:50', 'pending'),
(1087, 1124, 1, NULL, NULL, 'food', 1, '', 0.00, '2026-07-02 01:47:30', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `booking_inventory_deductions`
--

DROP TABLE IF EXISTS `booking_inventory_deductions`;
CREATE TABLE `booking_inventory_deductions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `quantity` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_inventory_deductions`
--

INSERT INTO `booking_inventory_deductions` (`id`, `booking_id`, `ingredient_id`, `warehouse_id`, `quantity`, `created_at`) VALUES
(1, 1, 3, 2, 0.50, '2026-05-13 08:17:20'),
(2, 2, 3, 2, 0.50, '2026-05-13 08:18:30'),
(3, 3, 3, 2, 0.50, '2026-05-21 03:49:48'),
(4, 6, 7, 2, 0.00, '2026-05-23 06:59:04'),
(5, 6, 6, 4, 0.50, '2026-05-23 06:59:04'),
(28, 19, 21, 2, 0.00, '2026-06-04 03:04:17'),
(29, 19, 1, 2, 1.00, '2026-06-04 03:04:17'),
(30, 19, 2, 2, 0.04, '2026-06-04 03:04:17'),
(31, 19, 12, 2, 0.00, '2026-06-04 03:04:17'),
(32, 19, 14, 2, 0.45, '2026-06-04 03:04:17'),
(33, 19, 12, 2, 0.00, '2026-06-04 03:04:17'),
(34, 19, 2, 2, 0.02, '2026-06-04 03:04:17'),
(35, 19, 3, 2, 0.01, '2026-06-04 03:04:17'),
(36, 19, 15, 2, 0.03, '2026-06-04 03:04:17'),
(37, 20, 21, 2, 0.00, '2026-06-04 03:07:36'),
(38, 20, 1, 2, 1.00, '2026-06-04 03:07:36'),
(39, 20, 2, 2, 0.04, '2026-06-04 03:07:36'),
(40, 20, 12, 2, 0.00, '2026-06-04 03:07:36'),
(41, 20, 14, 2, 0.45, '2026-06-04 03:07:36'),
(42, 20, 12, 2, 0.00, '2026-06-04 03:07:36'),
(43, 20, 2, 2, 0.02, '2026-06-04 03:07:36'),
(44, 20, 3, 2, 0.01, '2026-06-04 03:07:36'),
(45, 20, 15, 2, 0.03, '2026-06-04 03:07:36'),
(46, 21, 14, 2, 0.45, '2026-06-04 03:10:27'),
(47, 21, 12, 2, 0.00, '2026-06-04 03:10:27'),
(48, 21, 2, 2, 0.02, '2026-06-04 03:10:27'),
(49, 21, 3, 2, 0.01, '2026-06-04 03:10:27'),
(50, 21, 15, 2, 0.03, '2026-06-04 03:10:27'),
(51, 22, 14, 2, 0.45, '2026-06-04 03:10:55'),
(52, 22, 12, 2, 0.00, '2026-06-04 03:10:55'),
(53, 22, 2, 2, 0.02, '2026-06-04 03:10:55'),
(54, 22, 3, 2, 0.01, '2026-06-04 03:10:55'),
(55, 22, 15, 2, 0.03, '2026-06-04 03:10:55'),
(56, 23, 14, 2, 0.45, '2026-06-05 01:21:54'),
(57, 23, 12, 2, 0.00, '2026-06-05 01:21:54'),
(58, 23, 2, 2, 0.02, '2026-06-05 01:21:54'),
(59, 23, 3, 2, 0.01, '2026-06-05 01:21:54'),
(60, 23, 15, 2, 0.03, '2026-06-05 01:21:54'),
(61, 24, 14, 2, 0.45, '2026-06-05 01:34:31'),
(62, 24, 12, 2, 0.00, '2026-06-05 01:34:31'),
(63, 24, 2, 2, 0.02, '2026-06-05 01:34:31'),
(64, 24, 3, 2, 0.01, '2026-06-05 01:34:31'),
(65, 24, 15, 2, 0.03, '2026-06-05 01:34:31'),
(66, 47, 21, 2, 0.00, '2026-06-06 07:48:07'),
(67, 47, 1, 2, 1.00, '2026-06-06 07:48:07'),
(68, 47, 2, 2, 0.04, '2026-06-06 07:48:07'),
(69, 47, 12, 2, 0.00, '2026-06-06 07:48:07'),
(70, 48, 21, 2, 0.00, '2026-06-06 07:54:29'),
(71, 48, 1, 2, 1.00, '2026-06-06 07:54:29'),
(72, 48, 2, 2, 0.04, '2026-06-06 07:54:29'),
(73, 48, 12, 2, 0.00, '2026-06-06 07:54:29'),
(74, 48, 33, 2, 0.01, '2026-06-06 07:54:29'),
(75, 48, 14, 2, 0.20, '2026-06-06 07:54:29'),
(76, 48, 28, 2, 0.02, '2026-06-06 07:54:29'),
(78, 50, 9, 2, 0.40, '2026-06-06 08:00:31'),
(79, 50, 10, 2, 0.01, '2026-06-06 08:00:31'),
(80, 50, 13, 2, 0.01, '2026-06-06 08:00:31'),
(83, 78, 14, 2, 0.45, '2026-06-07 20:10:55'),
(84, 78, 12, 2, 0.00, '2026-06-07 20:10:55'),
(85, 78, 2, 2, 0.02, '2026-06-07 20:10:55'),
(86, 78, 3, 2, 0.01, '2026-06-07 20:10:55'),
(87, 78, 15, 2, 0.03, '2026-06-07 20:10:55'),
(88, 80, 14, 2, 0.45, '2026-06-07 20:29:48'),
(89, 80, 12, 2, 0.00, '2026-06-07 20:29:48'),
(90, 80, 2, 2, 0.02, '2026-06-07 20:29:48'),
(91, 80, 3, 2, 0.01, '2026-06-07 20:29:48'),
(92, 80, 15, 2, 0.03, '2026-06-07 20:29:48'),
(109, 81, 33, 2, 0.01, '2026-06-07 20:34:24'),
(110, 81, 14, 2, 0.20, '2026-06-07 20:34:24'),
(111, 81, 28, 2, 0.02, '2026-06-07 20:34:24'),
(112, 81, 14, 2, 0.45, '2026-06-07 20:34:24'),
(113, 81, 12, 2, 0.00, '2026-06-07 20:34:24'),
(114, 81, 2, 2, 0.02, '2026-06-07 20:34:24'),
(115, 81, 3, 2, 0.01, '2026-06-07 20:34:24'),
(116, 81, 15, 2, 0.03, '2026-06-07 20:34:24'),
(117, 81, 17, 2, 0.25, '2026-06-07 20:34:24'),
(118, 81, 12, 2, 0.00, '2026-06-07 20:34:25'),
(124, 83, 34, 2, 0.20, '2026-06-07 23:43:51'),
(125, 84, 21, 2, 0.00, '2026-06-08 09:59:18'),
(126, 84, 2, 2, 0.04, '2026-06-08 09:59:18'),
(127, 84, 12, 2, 0.00, '2026-06-08 09:59:18'),
(128, 85, 17, 2, 0.25, '2026-06-12 02:49:03'),
(129, 85, 12, 2, 0.00, '2026-06-12 02:49:03'),
(141, 87, 14, 2, 0.45, '2026-06-12 02:57:19'),
(142, 87, 2, 2, 0.02, '2026-06-12 02:57:19'),
(143, 87, 12, 2, 0.00, '2026-06-12 02:57:19'),
(144, 87, 3, 2, 0.01, '2026-06-12 02:57:19'),
(145, 88, 34, 2, 0.20, '2026-06-13 05:19:24'),
(150, 91, 34, 2, 0.20, '2026-06-13 05:31:48'),
(151, 91, 20, 2, 0.20, '2026-06-13 05:31:48'),
(152, 93, 87, 3, 5.00, '2026-06-15 04:21:32'),
(153, 93, 88, 3, 45.00, '2026-06-15 04:21:32'),
(154, 93, 89, 3, 20.00, '2026-06-15 04:21:32'),
(155, 94, 36, 2, 0.04, '2026-06-15 04:24:17'),
(156, 94, 37, 2, 0.01, '2026-06-15 04:24:17'),
(157, 94, 2, 2, 0.01, '2026-06-15 04:24:17'),
(158, 94, 49, 2, 0.02, '2026-06-15 04:24:17'),
(159, 94, 85, 2, 50.00, '2026-06-15 04:24:17'),
(160, 94, 86, 2, 1.00, '2026-06-15 04:24:17'),
(161, 94, 84, 3, 150.00, '2026-06-15 04:24:17'),
(162, 95, 22, 2, 0.02, '2026-06-15 07:38:25'),
(163, 95, 22, 2, 0.02, '2026-06-15 07:38:25'),
(164, 95, 35, 2, 1.00, '2026-06-15 07:38:25'),
(165, 95, 35, 2, 1.00, '2026-06-15 07:38:25'),
(166, 95, 87, 3, 5.00, '2026-06-15 07:38:25'),
(167, 95, 88, 3, 45.00, '2026-06-15 07:38:25'),
(168, 95, 89, 3, 20.00, '2026-06-15 07:38:25'),
(184, 98, 42, 2, 0.10, '2026-06-15 08:01:46'),
(185, 98, 41, 2, 0.12, '2026-06-15 08:01:46'),
(186, 98, 5, 2, 0.08, '2026-06-15 08:01:46'),
(187, 98, 55, 3, 0.02, '2026-06-15 08:01:46'),
(188, 98, 56, 3, 15.00, '2026-06-15 08:01:46'),
(189, 98, 52, 3, 45.00, '2026-06-15 08:01:47'),
(190, 98, 53, 3, 15.00, '2026-06-15 08:01:47'),
(191, 98, 54, 3, 0.05, '2026-06-15 08:01:47'),
(231, 1101, 5, 2, 0.30, '2026-06-23 06:53:24'),
(232, 1101, 3, 2, 0.01, '2026-06-23 06:53:24'),
(237, 1102, 7, 2, 0.00, '2026-06-23 07:40:16'),
(238, 1102, 11, 2, 0.05, '2026-06-23 07:40:16'),
(239, 1102, 7, 2, 0.00, '2026-06-23 07:40:16'),
(240, 1102, 11, 2, 0.05, '2026-06-23 07:40:16'),
(241, 1102, 38, 2, 0.05, '2026-06-23 07:40:16'),
(242, 1103, 14, 2, 0.45, '2026-06-25 12:12:03'),
(243, 1103, 2, 2, 0.02, '2026-06-25 12:12:03'),
(244, 1103, 12, 2, 0.00, '2026-06-25 12:12:03'),
(245, 1103, 3, 2, 0.01, '2026-06-25 12:12:03'),
(246, 1104, 33, 2, 0.01, '2026-06-25 13:07:56'),
(247, 1104, 14, 2, 0.20, '2026-06-25 13:07:56'),
(249, 1105, 9, 2, 0.40, '2026-06-26 11:46:08'),
(250, 1105, 10, 2, 0.01, '2026-06-26 11:46:08'),
(251, 1105, 13, 2, 0.01, '2026-06-26 11:46:08'),
(252, 1106, 30, 2, 0.40, '2026-06-26 11:59:51'),
(253, 1106, 29, 2, 0.20, '2026-06-26 11:59:51'),
(254, 1107, 42, 2, 0.10, '2026-06-26 12:11:58'),
(255, 1107, 5, 2, 0.08, '2026-06-26 12:11:58'),
(256, 1107, 41, 2, 0.12, '2026-06-26 12:11:58'),
(257, 1108, 17, 2, 0.25, '2026-06-26 12:19:14'),
(258, 1108, 12, 2, 0.00, '2026-06-26 12:19:14'),
(259, 1109, 33, 2, 0.02, '2026-06-26 12:30:54'),
(260, 1109, 31, 2, 0.40, '2026-06-26 12:30:54'),
(261, 1109, 16, 2, 0.20, '2026-06-26 12:30:54'),
(262, 1110, 7, 2, 0.00, '2026-06-26 12:38:23'),
(263, 1110, 11, 2, 0.05, '2026-06-26 12:38:23'),
(264, 1110, 7, 2, 0.00, '2026-06-26 12:38:23'),
(265, 1110, 11, 2, 0.05, '2026-06-26 12:38:23'),
(266, 1111, 5, 2, 0.30, '2026-06-26 14:35:17'),
(267, 1111, 3, 2, 0.01, '2026-06-26 14:35:17'),
(286, 1113, 21, 2, 0.00, '2026-06-30 02:47:05'),
(287, 1113, 21, 2, 0.00, '2026-06-30 02:47:05'),
(288, 1113, 2, 2, 0.04, '2026-06-30 02:47:05'),
(289, 1113, 12, 2, 0.00, '2026-06-30 02:47:05'),
(290, 1113, 2, 2, 0.04, '2026-06-30 02:47:05'),
(291, 1113, 12, 2, 0.00, '2026-06-30 02:47:05'),
(292, 1113, 2, 2, 0.02, '2026-06-30 02:47:05'),
(293, 1113, 14, 2, 0.45, '2026-06-30 02:47:05'),
(294, 1113, 12, 2, 0.00, '2026-06-30 02:47:05'),
(295, 1113, 3, 2, 0.01, '2026-06-30 02:47:05'),
(296, 1113, 30, 2, 0.40, '2026-06-30 02:47:06'),
(297, 1113, 29, 2, 0.20, '2026-06-30 02:47:06'),
(300, 1115, 30, 2, 0.40, '2026-06-30 10:46:51'),
(301, 1115, 29, 2, 0.20, '2026-06-30 10:46:51'),
(302, 1116, 33, 2, 0.01, '2026-07-01 14:35:10'),
(303, 1116, 14, 2, 0.20, '2026-07-01 14:35:10'),
(304, 1116, 22, 2, 0.02, '2026-07-01 14:35:10'),
(305, 1116, 22, 2, 0.02, '2026-07-01 14:35:10'),
(306, 1116, 35, 2, 1.00, '2026-07-01 14:35:10'),
(307, 1116, 35, 2, 1.00, '2026-07-01 14:35:10'),
(308, 1122, 2, 2, 0.02, '2026-07-02 01:43:31'),
(309, 1122, 14, 2, 0.45, '2026-07-02 01:43:31'),
(310, 1122, 12, 2, 0.00, '2026-07-02 01:43:31'),
(311, 1122, 3, 2, 0.01, '2026-07-02 01:43:31'),
(312, 1123, 2, 2, 0.02, '2026-07-02 01:46:20'),
(313, 1123, 14, 2, 0.45, '2026-07-02 01:46:20'),
(314, 1123, 12, 2, 0.00, '2026-07-02 01:46:20'),
(315, 1123, 3, 2, 0.01, '2026-07-02 01:46:20'),
(316, 1124, 2, 2, 0.02, '2026-07-02 01:47:47'),
(317, 1124, 14, 2, 0.45, '2026-07-02 01:47:47'),
(318, 1124, 12, 2, 0.00, '2026-07-02 01:47:47'),
(319, 1124, 3, 2, 0.01, '2026-07-02 01:47:47');

-- --------------------------------------------------------

--
-- Table structure for table `bot_context_logs`
--

DROP TABLE IF EXISTS `bot_context_logs`;
CREATE TABLE `bot_context_logs` (
  `id` int(11) NOT NULL,
  `keyword_searched` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bot_context_logs`
--

INSERT INTO `bot_context_logs` (`id`, `keyword_searched`, `created_at`) VALUES
(1, 'chào bạn', '2026-06-20 11:19:03'),
(2, 'gặp nhân viên', '2026-06-20 11:19:28'),
(3, 'ở đâu', '2026-06-20 11:42:02'),
(4, '.', '2026-06-20 11:43:27'),
(5, 'nhân viên', '2026-06-20 11:43:33'),
(6, 'chào bạn', '2026-06-23 09:43:56'),
(7, 'chào bạn', '2026-06-23 11:17:33'),
(8, 'chào', '2026-06-23 11:50:43'),
(9, 'chào', '2026-06-26 14:40:31'),
(10, 'chào bạn', '2026-06-26 14:51:20'),
(11, 'tôi muốn đặt bàn', '2026-07-01 12:18:06'),
(12, 'hi', '2026-07-01 12:18:09'),
(13, 'hi', '2026-07-02 00:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `bot_responses`
--

DROP TABLE IF EXISTS `bot_responses`;
CREATE TABLE `bot_responses` (
  `id` int(11) NOT NULL,
  `keywords` text NOT NULL,
  `answer` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bot_responses`
--

INSERT INTO `bot_responses` (`id`, `keywords`, `answer`) VALUES
(1, 'chào,hello,hi,hola,xin chào', 'Chào bạn! Mình có thể giúp gì cho bạn?'),
(2, 'địa chỉ,ở đâu,vị trí,địa điểm,nơi tổ chức', 'Nhà hàng Restaurantly nằm tại số 123 Đường ABC, Quận XYZ, TP. HCM bạn nhé.'),
(3, 'giờ mở cửa,thời gian,mấy giờ', 'Nhà hàng mở cửa từ 8:00 sáng đến 22:00 tối các ngày trong tuần ạ.'),
(4, 'thực đơn, menu, món ăn, đồ ăn, bán gì', 'Nhà hàng phục vụ các món Âu và Á cao cấp. Bạn có thể tham khảo Menu trực tuyến trên trang web ở mục \"Khám Phá Menu\", bao gồm các món Signature, Bespoke Menu và đồ uống bạn nhé.'),
(5, 'đặt bàn, book bàn, reserve, giữ chỗ, muốn đặt bàn', 'Bạn có thể tự đặt bàn nhanh chóng qua mục \"Đặt Bàn\" trên website. Hệ thống sẽ tự động xác nhận và giữ chỗ cho bạn. Cần hỗ trợ thêm, bạn cứ nhắn nhé!'),
(6, 'giá, bao nhiêu tiền, chi phí, bảng giá', 'Mức giá các món lẻ dao động từ 150k - 800k. Các Set Combo có giá từ 400k - 2.500k. Đối với tiệc Bespoke thì sẽ tùy thuộc vào thiết kế thực đơn riêng bạn nhé.'),
(7, 'thanh toán, trả tiền, chuyển khoản, tiền mặt, quẹt thẻ', 'Nhà hàng hỗ trợ thanh toán bằng Tiền mặt, Chuyển khoản ngân hàng (quét mã QR) và Quẹt thẻ tín dụng/ghi nợ (Visa, Mastercard, JCB).'),
(8, 'đầu bếp tại gia, bespoke, thuê đầu bếp, đầu bếp tới nhà, nấu tại nhà', 'Dịch vụ \"Đầu Bếp Tại Gia (Bespoke Dining)\" mang không gian nhà hàng cao cấp về tận nhà bạn! Bếp trưởng sẽ thiết kế thực đơn riêng và trực tiếp đến phục vụ. Bạn chọn mục \"Đầu Bếp Tại Gia\" ở trang Đặt Bàn nhé.'),
(9, 'sinh nhật, sự kiện, tiệc, anniversary, kỷ niệm, trang trí', 'Tuyệt vời! Nhà hàng có các gói trang trí Sinh nhật, Kỷ niệm, Tiệc Cầu hôn... với hoa tươi, nến, bánh kem và thiệp viết tay. Bạn có thể tick chọn lúc Đặt Bàn, hệ thống sẽ chuẩn bị chu đáo cho bạn.'),
(12, 'phòng riêng, phòng vip, không gian riêng, họp', 'Nhà hàng có các phòng VIP với sức chứa từ 4 đến 20 khách, không gian riêng tư, cách âm tốt. Bạn có thể chọn loại bàn \"VIP\" khi Đặt bàn tiêu chuẩn.'),
(13, 'cảm ơn, thanks, tks, thank you', 'Rất vui vì được hỗ trợ bạn! Chúc bạn một ngày tốt lành và hy vọng sớm được đón tiếp bạn tại Restaurantly!');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 99
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES
(1, 'Khai vị', 1),
(2, 'Món chính', 2),
(3, 'Tráng miệng', 6),
(4, 'Đồ uống không cồn', 5),
(5, 'Món ăn kèm', 3),
(6, 'Đồ uống có cồn', 4);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `session_id` varchar(50) NOT NULL,
  `sender_type` enum('customer','bot','admin') NOT NULL,
  `message_type` enum('text','image') DEFAULT 'text',
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_hidden` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `session_id`, `sender_type`, `message_type`, `content`, `is_read`, `created_at`, `is_hidden`) VALUES
(4, 'sess_1781954337712_976', 'bot', 'text', 'Chào Quản trị viên! Mình là Trợ lý ảo của Restaurantly. Mình có thể giúp gì cho bạn hôm nay?', 0, '2026-06-20 11:18:57', 0),
(5, 'sess_1781954337712_976', 'customer', 'text', 'chào bạn', 1, '2026-06-20 11:19:03', 0),
(6, 'sess_1781954337712_976', 'bot', 'text', 'Xin lỗi, mình chưa hiểu ý bạn. Bạn có muốn \'gặp nhân viên\' không?', 0, '2026-06-20 11:19:03', 0),
(7, 'sess_1781954337712_976', 'customer', 'text', 'gặp nhân viên', 1, '2026-06-20 11:19:28', 0),
(8, 'sess_1781954337712_976', 'bot', 'text', 'Vui lòng đợi giây lát, nhân viên sẽ hỗ trợ bạn ngay.', 0, '2026-06-20 11:19:28', 0),
(9, 'sess_1781954337712_976', 'admin', 'text', 'xin chào', 0, '2026-06-20 11:19:34', 0),
(10, 'sess_1781954337712_976', 'customer', 'text', 'Thực đơn nhà hàng có gì?', 1, '2026-06-20 11:31:31', 0),
(11, 'sess_1781954337712_976', 'customer', 'text', 'Gặp nhân viên hỗ trợ', 1, '2026-06-20 11:31:33', 0),
(12, 'sess_1781954337712_976', 'customer', 'text', 'Tôi muốn đặt bàn', 1, '2026-06-20 11:31:34', 0),
(13, 'sess_1781954337712_976', 'customer', 'text', 'Thực đơn nhà hàng có gì?', 1, '2026-06-20 11:31:35', 0),
(14, 'sess_1781954337712_976', 'admin', 'text', 'Dạ, bạn có thể tham khảo thực đơn tại Website của nhà hàng ạ.', 0, '2026-06-20 11:32:28', 0),
(15, 'sess_1781954337712_976', 'customer', 'text', 'Thực đơn nhà hàng có gì?', 1, '2026-06-20 11:32:55', 0),
(16, 'sess_1781954337712_976', 'customer', 'text', 'ở đâu', 1, '2026-06-20 11:39:20', 0),
(17, 'sess_1781954337712_976', 'customer', 'text', 'ơ đâu', 1, '2026-06-20 11:40:56', 0),
(18, 'sess_1781954337712_976', 'customer', 'text', 'ở đâu', 1, '2026-06-20 11:41:02', 0),
(19, 'sess_1781954337712_976', 'customer', 'text', 'ở đâu', 1, '2026-06-20 11:41:11', 0),
(20, 'sess_1781954337712_976', 'customer', 'text', 'ở đâu', 1, '2026-06-20 11:42:02', 0),
(21, 'sess_1781954337712_976', 'bot', 'text', 'Nhà hàng Restaurantly nằm tại số 123 Đường ABC, Quận XYZ, TP. HCM bạn nhé.', 0, '2026-06-20 11:42:02', 0),
(22, 'sess_1781954337712_976', 'customer', 'text', '.', 1, '2026-06-20 11:43:15', 0),
(23, 'sess_1781954337712_976', 'customer', 'text', '.', 1, '2026-06-20 11:43:27', 0),
(24, 'sess_1781954337712_976', 'bot', 'text', 'Xin lỗi, mình chưa hiểu ý bạn. Bạn có muốn \'gặp nhân viên\' không?', 0, '2026-06-20 11:43:27', 0),
(25, 'sess_1781954337712_976', 'customer', 'text', 'nhân viên', 1, '2026-06-20 11:43:33', 0),
(26, 'sess_1781954337712_976', 'bot', 'text', 'Vui lòng đợi giây lát, nhân viên sẽ hỗ trợ bạn ngay.', 0, '2026-06-20 11:43:33', 0),
(27, 'sess_1781954337712_976', 'admin', 'text', 'chào bạn', 0, '2026-06-22 06:56:23', 0),
(28, 'sess_1782207829541_780', 'bot', 'text', 'Chào Vương Tuấn Anh! Mình là Trợ lý ảo của Restaurantly. Mình có thể giúp gì cho bạn hôm nay?', 0, '2026-06-23 09:43:49', 0),
(29, 'sess_1782207829541_780', 'customer', 'text', 'chào bạn', 1, '2026-06-23 09:43:56', 0),
(30, 'sess_1782207829541_780', 'bot', 'text', 'Chào bạn! Mình có thể giúp gì cho bạn?', 0, '2026-06-23 09:43:56', 0),
(31, 'sess_1781954337712_976', 'customer', 'text', 'chào bạn', 1, '2026-06-23 11:17:33', 0),
(32, 'sess_1781954337712_976', 'bot', 'text', 'Chào bạn! Mình có thể giúp gì cho bạn?', 0, '2026-06-23 11:17:33', 0),
(33, 'sess_1781954337712_976', 'customer', 'text', 'chào', 1, '2026-06-23 11:50:43', 0),
(34, 'sess_1781954337712_976', 'bot', 'text', 'Chào bạn! Mình có thể giúp gì cho bạn?', 0, '2026-06-23 11:50:43', 0),
(35, 'sess_1782207829541_780', 'admin', 'text', 'chào bạn', 0, '2026-06-23 11:51:00', 0),
(36, 'sess_1781954337712_976', 'admin', 'text', 'chào', 0, '2026-06-23 11:51:10', 0),
(37, 'sess_1781954337712_976', 'customer', 'text', 'chào', 1, '2026-06-26 14:40:31', 0),
(38, 'sess_1781954337712_976', 'bot', 'text', 'Chào bạn! Mình có thể giúp gì cho bạn?', 0, '2026-06-26 14:40:31', 0),
(39, 'sess_1781954337712_976', 'admin', 'text', 'hi', 0, '2026-06-26 14:40:44', 0),
(40, 'sess_1781954337712_976', 'admin', 'text', 'chào bạn', 0, '2026-06-26 14:41:00', 0),
(41, 'sess_1781954337712_976', 'admin', 'text', 'chào bạn', 0, '2026-06-26 14:44:42', 0),
(42, 'sess_1782485470645_973', 'bot', 'text', 'Chào Quản trị viên! Mình là Trợ lý ảo của Restaurantly. Mình có thể giúp gì cho bạn hôm nay?', 0, '2026-06-26 14:51:10', 0),
(43, 'sess_1782485470645_973', 'customer', 'text', 'chào bạn', 1, '2026-06-26 14:51:20', 0),
(44, 'sess_1782485470645_973', 'bot', 'text', 'Chào bạn! Mình có thể giúp gì cho bạn?', 0, '2026-06-26 14:51:20', 0),
(45, 'sess_1782485470645_973', 'admin', 'text', 'chào bạn', 0, '2026-06-26 14:51:30', 0),
(46, 'sess_1781954337712_976', 'customer', 'text', 'Tôi muốn đặt bàn', 1, '2026-07-01 12:18:06', 0),
(47, 'sess_1781954337712_976', 'bot', 'text', 'Để kiểm tra tình trạng bàn chính xác, bạn vui lòng chọn phần Đặt bàn trên Website hoặc nhắn \'gặp nhân viên\' để được hỗ trợ kiểm tra ngay nhé.', 0, '2026-07-01 12:18:06', 0),
(48, 'sess_1781954337712_976', 'customer', 'text', 'hi', 1, '2026-07-01 12:18:09', 0),
(49, 'sess_1781954337712_976', 'bot', 'text', 'Chào bạn! Mình có thể giúp gì cho bạn?', 0, '2026-07-01 12:18:09', 0),
(50, 'sess_1781954337712_976', 'customer', 'text', 'hi', 0, '2026-07-02 00:53:19', 0),
(51, 'sess_1781954337712_976', 'bot', 'text', 'Chào bạn! Mình có thể giúp gì cho bạn?', 0, '2026-07-02 00:53:19', 0);

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

DROP TABLE IF EXISTS `chat_sessions`;
CREATE TABLE `chat_sessions` (
  `session_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `status` enum('bot_handling','waiting_agent','agent_handling','closed') DEFAULT 'bot_handling',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_response_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`session_id`, `customer_name`, `customer_phone`, `status`, `created_at`, `first_response_at`, `closed_at`) VALUES
('sess_1781954337712_976', 'Quản trị viên', '0000000000', 'bot_handling', '2026-06-20 11:18:57', '2026-06-20 11:19:34', '2026-06-23 11:17:10'),
('sess_1782207829541_780', 'Vương Tuấn Anh', '0956789012', 'bot_handling', '2026-06-23 09:43:49', NULL, NULL),
('sess_1782485470645_973', 'Quản trị viên', '0000000000', 'bot_handling', '2026-06-26 14:51:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chefs`
--

DROP TABLE IF EXISTS `chefs`;
CREATE TABLE `chefs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `experience` int(11) DEFAULT 0,
  `specialty` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quote` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `awards` text DEFAULT NULL,
  `signature_dishes` varchar(255) DEFAULT NULL,
  `signature_technique` text DEFAULT NULL,
  `signature_technique_specs` text DEFAULT NULL,
  `signature_technique_process` text DEFAULT NULL,
  `signature_technique_quote` text DEFAULT NULL,
  `signature_technique_difficulty` varchar(50) DEFAULT 'Khó',
  `signature_technique_final_result` text DEFAULT NULL,
  `gallery_images` text DEFAULT NULL,
  `service_fee` int(11) DEFAULT 250000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chefs`
--

INSERT INTO `chefs` (`id`, `name`, `position`, `image`, `experience`, `specialty`, `description`, `quote`, `facebook`, `instagram`, `email`, `is_active`, `is_featured`, `sort_order`, `awards`, `signature_dishes`, `signature_technique`, `signature_technique_specs`, `signature_technique_process`, `signature_technique_quote`, `signature_technique_difficulty`, `signature_technique_final_result`, `gallery_images`, `service_fee`) VALUES
(1, 'Vũ Văn Chính', 'Bếp trưởng', '1782717189_6a421b0509aed.png', 7, 'Hải sản Cao cấp & Sốt thủ công', 'Được mệnh danh là \'Bậc thầy của biển cả\', Chef Vũ Văn Chính sở hữu hơn 15 năm kinh nghiệm làm việc tại các nhà hàng hải sản cao cấp bậc nhất Đông Nam Á. Với triết lý \'Tôn trọng nguyên bản\', anh luôn đặt sự tươi ngon của nguyên liệu lên hàng đầu. Kỹ thuật phi lê cá điêu luyện cùng khả năng kết hợp các loại nước sốt độc quyền từ thảo mộc địa phương giúp Chef Chính biến những nguyên liệu hải sản quen thuộc trở thành những tuyệt tác ẩm thực mang đẳng cấp thế giới.', 'Sự vĩ đại của ẩm thực không nằm ở sự phức tạp, mà ở khả năng tôn vinh những gì tự nhiên nhất.', '', '', '', 1, 1, 1, '', '3,21', 'Kỹ thuật ủ lạnh cá (Dry-aged Fish) chuẩn Nhật Bản kết hợp nhiệt phân đoạn, giúp khử hoàn toàn mùi tanh, làm săn chắc thớ thịt và cô đặc hương vị umami tự nhiên của hải sản.', 'Nhiệt độ ủ: 1°C - 3°C\nĐộ ẩm (Humidity): 75% - 80%\nThời gian ủ: 7 - 14 ngày tuỳ loại cá\nCông cụ: Tủ Dry-Ager chuyên dụng', '1. Xử lý Ikejime (chọc tuỷ) ngay khi cá còn sống để giữ độ tươi\n2. Làm sạch hoàn toàn máu và nội tạng\n3. Treo cá trong tủ ủ lạnh với môi trường vô trùng\n4. Theo dõi độ ẩm và nhiệt độ nghiêm ngặt mỗi ngày\n5. Phi-lê và cắt Sashimi hoặc nướng than Binchotan', 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', '[\"gallery_1782714932_chef_interacting_1782714744039.png\",\"gallery_1782714932_chef_presenting_1782714772402.png\",\"gallery_1782714932_chef_signing_1782714800223.png\",\"gallery_1782714932_chef_private_dining_1782714827402.png\",\"gallery_1782714932_chef_masterclass_1782714861204.png\",\"gallery_1782714932_chef_ingredients_1782714888807.png\"]', 270000),
(2, 'Huỳnh Quốc Dương', 'Bếp chính', '1782717163_6a421aeb4415d.jpg', 5, 'Ẩm thực Á-Âu Đương Đại', 'Sinh ra và lớn lên tại vùng đất miền Trung đầy nắng gió, Chef Huỳnh Quốc Dương đã sớm hình thành tình yêu mãnh liệt với các nguyên liệu địa phương. Hành trình ẩm thực của anh bắt đầu từ những khu bếp nhỏ mang đậm hương vị truyền thống, sau đó vươn ra quốc tế khi anh tu nghiệp tại Pháp và Nhật Bản. Trở về Việt Nam, anh mang theo sứ mệnh kết hợp tinh hoa ẩm thực Á Đông với kỹ thuật chế biến hiện đại của phương Tây. Các món ăn của Chef Dương không chỉ đánh thức vị giác mà còn là những bức tranh nghệ thuật đầy cảm xúc, kể về câu chuyện giao thoa văn hóa đầy tinh tế.', 'Mỗi món ăn là một bản giao hưởng của hương vị, nơi truyền thống và hiện đại cùng cất lên tiếng hát.', 'https://www.facebook.com/friends/suggestions/?profile_id=61576317195911', '', '', 1, 1, 2, '', '3,14', 'Kỹ thuật áp chảo Sous-vide kết hợp khò lửa trực tiếp (Blowtorch), giúp giữ trọn vẹn độ mọng nước tự nhiên của nguyên liệu trong khi vẫn tạo ra lớp vỏ ngoài giòn rụm hoàn hảo, dậy mùi khói đặc trưng.', 'Nhiệt độ Sous-vide: 54°C trong 2 giờ\nNhiệt độ áp chảo: 280°C\nNhiệt độ khò lửa: 800°C (Sử dụng đuốc khò chuyên dụng)\nCông cụ: Máy Sous-vide Anova Pro, Đuốc khò Iwatani', '1. Tẩm ướp thịt với muối biển nguyên cám và tiêu đen xay vỡ\n2. Hút chân không cùng bơ và cỏ xạ hương\n3. Nấu chậm bằng phương pháp Sous-vide để đạt độ chín hoàn hảo từ lõi\n4. Lau khô bề mặt thịt hoàn toàn\n5. Áp chảo gang siêu tốc ở nhiệt độ cao để tạo phản ứng Maillard\n6. Kết thúc bằng thao tác khò lửa trực tiếp để tạo lớp vỏ ngoài giòn và hương khói đặc trưng', 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', NULL, 250000),
(3, 'Lê Hữu Thuận Phát', 'Bếp chính', '1782198284_6a3a300c93829.jpg', 4, 'Ẩm thực Âu & Tráng miệng Nghệ thuật', 'Chef Lê Hữu Thuận Phát là một người kể chuyện bằng hương vị đích thực. Anh từng có 8 năm làm việc tại các nhà hàng Michelin ở Ý trước khi trở về nước. Sự kết hợp nhuần nhuyễn giữa phong cách lãng mạn của ẩm thực Châu Âu và nét mộc mạc của nguyên liệu Việt Nam đã tạo nên dấu ấn riêng biệt mang tên Thuận Phát. Anh đặc biệt đam mê nghệ thuật làm bánh và các món tráng miệng, nơi anh có thể thỏa sức sáng tạo và mang lại những cái kết ngọt ngào khó quên cho thực khách.', 'Một bữa ăn hoàn hảo giống như một cuốn tiểu thuyết hay, và món tráng miệng chính là chương cuối vương vấn mãi.', 'https://www.facebook.com/friends/suggestions/?profile_id=61576317195911', '', '', 1, 1, 3, '', '6,15', 'Kỹ thuật lên men tự nhiên (Fermentation) và trích ly hương vị bằng sóng siêu âm (Ultrasonic Extraction), mang lại những tầng hương vị độc bản từ thảo mộc địa phương.', 'Nhiệt độ lên men: 28°C\nThời gian: 3 - 6 tháng\nTần số siêu âm: 20 kHz - 40 kHz\nCông cụ: Máy Homogenizer siêu âm, Thùng gỗ sồi', '1. Thu hoạch thảo mộc và nấm truffle tươi vào sáng sớm\n2. Kích hoạt quá trình lên men bằng nấm men Koji tự nhiên\n3. Phá vỡ tế bào nguyên liệu bằng sóng siêu âm để giải phóng tối đa tinh dầu\n4. Lọc cẩn thận qua màng lọc nano\n5. Ủ lạnh để ổn định hương vị trước khi phục vụ', 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', NULL, 250000),
(10, 'Dương  Lê Hoàng Long', 'Bếp chính', '1782198292_6a3a301482ad4.webp', 1, 'Ẩm thực Thuần chay & Lên men tự nhiên', 'Là thành viên trẻ tuổi nhất nhưng cũng mang đầy nhiệt huyết, Chef Dương Lê Hoàng Long đại diện cho thế hệ đầu bếp Gen Z đầy phá cách và sáng tạo. Anh nổi bật với tư duy ẩm thực tối giản (Minimalism) và xu hướng Farm to Table (từ nông trại đến bàn ăn). Chú trọng vào tính bền vững và sự cân bằng dinh dưỡng, Chef Long không ngừng thử nghiệm việc lên men tự nhiên và sử dụng các nguyên liệu thuần chay để tạo ra những món ăn không chỉ đẹp mắt, ngon miệng mà còn tốt cho sức khỏe.', 'Nấu ăn là quá trình thấu hiểu thiên nhiên và gửi gắm sự trân trọng đó vào từng nguyên liệu.', '', '', '', 1, 0, 4, '', '15,18,7', 'Kỹ thuật lên men Koji truyền thống của Nhật Bản ứng dụng trên các loại nấm và rau củ bản địa, tạo ra các tầng hương vị phức hợp sâu sắc mà không cần sử dụng phụ gia công nghiệp.', NULL, NULL, 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', NULL, 250000),
(12, 'Lê Văn C', 'Phụ bếp', NULL, 1, 'Chuẩn bị nguyên liệu', 'Hỗ trợ khu vực bếp nóng.', 'Cẩn thận trong từng công đoạn.', NULL, NULL, NULL, 1, 0, 6, NULL, NULL, NULL, NULL, NULL, 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', NULL, 250000),
(13, 'Phạm Văn D', 'Phụ bếp', NULL, 3, 'Bếp nóng', 'Hỗ trợ đầu bếp chính.', 'Làm việc bằng đam mê.', NULL, NULL, NULL, 1, 0, 7, NULL, NULL, NULL, NULL, NULL, 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', NULL, 250000),
(14, 'Hoàng Văn E', 'Phụ bếp', NULL, 2, 'Bếp lạnh', 'Chuẩn bị món khai vị.', 'Tỉ mỉ tạo nên sự khác biệt.', NULL, NULL, NULL, 1, 0, 8, NULL, NULL, NULL, NULL, NULL, 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', NULL, 250000),
(15, 'Đỗ Văn F', 'Phụ bếp', NULL, 1, 'Sơ chế hải sản', 'Chuẩn bị nguyên liệu hải sản.', 'Nguyên liệu tốt tạo nên món ăn ngon.', NULL, NULL, NULL, 1, 0, 9, NULL, NULL, NULL, NULL, NULL, 'Sự hoàn hảo không đến từ những nguyên liệu đắt tiền nhất, mà đến từ sự kiên nhẫn và tôn trọng nguyên bản của từng thành phần.', 'Nghệ Nhân (Master)', 'Thịt mềm tan trong miệng, hương vị umami bùng nổ, màu sắc rực rỡ óng ánh sắc vàng của lớp mỡ tự nhiên, để lại hậu vị ngọt thanh khó quên.', NULL, 250000);

-- --------------------------------------------------------

--
-- Table structure for table `chef_certificates`
--

DROP TABLE IF EXISTS `chef_certificates`;
CREATE TABLE `chef_certificates` (
  `id` int(11) NOT NULL,
  `chef_id` int(11) NOT NULL,
  `certificate_name` varchar(255) NOT NULL,
  `issuer` varchar(255) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `certificate_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chef_certificates`
--

INSERT INTO `chef_certificates` (`id`, `chef_id`, `certificate_name`, `issuer`, `issue_date`, `certificate_image`, `created_at`) VALUES
(1, 1, 'Chứng chỉ Culinary Arts', 'Le Cordon Bleu Paris', '2020-05-15', 'cert_chef1_1.png', '2026-06-30 15:45:06'),
(2, 1, 'Chứng nhận An toàn thực phẩm', 'Bộ Y Tế Việt Nam', '2022-09-10', 'cert_chef1_2.png', '2026-06-30 15:45:06'),
(3, 1, 'Michelin Star Training Certificate', 'Michelin Guide Academy', '2024-03-01', 'cert_chef1_3.png', '2026-06-30 15:45:06'),
(4, 2, 'Chứng chỉ Chuyên Môn Bếp Trưởng Huỳnh Quốc Dương', 'Hiệp hội Ẩm thực Châu Á', '2021-06-15', 'cert_chef_2_1.png', '2026-06-30 15:45:06'),
(5, 2, 'Chứng nhận An toàn vệ sinh thực phẩm', 'Sở Y Tế', '2023-11-20', 'cert_chef_2_2.png', '2026-06-30 15:45:06'),
(6, 3, 'Chứng chỉ Chuyên Môn Bếp Trưởng Lê Hữu Thuận Phát', 'Hiệp hội Ẩm thực Châu Á', '2021-06-15', 'cert_chef_3_1.png', '2026-06-30 15:45:06'),
(7, 3, 'Chứng nhận An toàn vệ sinh thực phẩm', 'Sở Y Tế', '2023-11-20', 'cert_chef_3_2.png', '2026-06-30 15:45:06'),
(8, 10, 'Chứng chỉ Chuyên Môn Bếp Trưởng Dương  Lê Hoàng Long', 'Hiệp hội Ẩm thực Châu Á', '2021-06-15', 'cert_chef_10_1.png', '2026-06-30 15:45:06'),
(9, 10, 'Chứng nhận An toàn vệ sinh thực phẩm', 'Sở Y Tế', '2023-11-20', 'cert_chef_10_2.png', '2026-06-30 15:45:06'),
(10, 12, 'Chứng chỉ Chuyên Môn Bếp Trưởng Lê Văn C', 'Hiệp hội Ẩm thực Châu Á', '2021-06-15', 'cert_chef_12_1.png', '2026-06-30 15:45:06'),
(11, 12, 'Chứng nhận An toàn vệ sinh thực phẩm', 'Sở Y Tế', '2023-11-20', 'cert_chef_12_2.png', '2026-06-30 15:45:06'),
(12, 13, 'Chứng chỉ Chuyên Môn Bếp Trưởng Phạm Văn D', 'Hiệp hội Ẩm thực Châu Á', '2021-06-15', 'cert_chef_13_1.png', '2026-06-30 15:45:06'),
(13, 13, 'Chứng nhận An toàn vệ sinh thực phẩm', 'Sở Y Tế', '2023-11-20', 'cert_chef_13_2.png', '2026-06-30 15:45:06'),
(14, 14, 'Chứng chỉ Chuyên Môn Bếp Trưởng Hoàng Văn E', 'Hiệp hội Ẩm thực Châu Á', '2021-06-15', 'cert_chef_14_1.png', '2026-06-30 15:45:06'),
(15, 14, 'Chứng nhận An toàn vệ sinh thực phẩm', 'Sở Y Tế', '2023-11-20', 'cert_chef_14_2.png', '2026-06-30 15:45:06'),
(16, 15, 'Chứng chỉ Chuyên Môn Bếp Trưởng Đỗ Văn F', 'Hiệp hội Ẩm thực Châu Á', '2021-06-15', 'cert_chef_15_1.png', '2026-06-30 15:45:06'),
(17, 15, 'Chứng nhận An toàn vệ sinh thực phẩm', 'Sở Y Tế', '2023-11-20', 'cert_chef_15_2.png', '2026-06-30 15:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `chef_gallery`
--

DROP TABLE IF EXISTS `chef_gallery`;
CREATE TABLE `chef_gallery` (
  `id` int(11) NOT NULL,
  `chef_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chef_gallery`
--

INSERT INTO `chef_gallery` (`id`, `chef_id`, `image`, `sort_order`, `created_at`) VALUES
(25, 12, 'gallery_chef_12_0.webp', 0, '2026-06-30 15:45:06'),
(26, 12, 'gallery_chef_12_1.webp', 1, '2026-06-30 15:45:06'),
(27, 12, 'gallery_chef_12_2.png', 2, '2026-06-30 15:45:06'),
(28, 12, 'gallery_chef_12_3.webp', 3, '2026-06-30 15:45:06'),
(29, 12, 'gallery_chef_12_4.jpg', 4, '2026-06-30 15:45:06'),
(30, 12, 'gallery_chef_12_5.jpg', 5, '2026-06-30 15:45:06'),
(31, 13, 'gallery_chef_13_0.jpg', 0, '2026-06-30 15:45:06'),
(32, 13, 'gallery_chef_13_1.jpg', 1, '2026-06-30 15:45:06'),
(33, 13, 'gallery_chef_13_2.jpg', 2, '2026-06-30 15:45:06'),
(34, 13, 'gallery_chef_13_3.jpg', 3, '2026-06-30 15:45:06'),
(35, 13, 'gallery_chef_13_4.png', 4, '2026-06-30 15:45:06'),
(36, 13, 'gallery_chef_13_5.jpg', 5, '2026-06-30 15:45:06'),
(37, 14, 'gallery_chef_14_0.webp', 0, '2026-06-30 15:45:06'),
(38, 14, 'gallery_chef_14_1.jpg', 1, '2026-06-30 15:45:06'),
(39, 14, 'gallery_chef_14_2.jpg', 2, '2026-06-30 15:45:06'),
(40, 14, 'gallery_chef_14_3.jpg', 3, '2026-06-30 15:45:06'),
(41, 14, 'gallery_chef_14_4.jpg', 4, '2026-06-30 15:45:06'),
(42, 14, 'gallery_chef_14_5.jpg', 5, '2026-06-30 15:45:06'),
(43, 15, 'gallery_chef_15_0.webp', 0, '2026-06-30 15:45:06'),
(44, 15, 'gallery_chef_15_1.webp', 1, '2026-06-30 15:45:06'),
(45, 15, 'gallery_chef_15_2.png', 2, '2026-06-30 15:45:06'),
(46, 15, 'gallery_chef_15_3.webp', 3, '2026-06-30 15:45:06'),
(47, 15, 'gallery_chef_15_4.jpg', 4, '2026-06-30 15:45:06'),
(48, 15, 'gallery_chef_15_5.jpg', 5, '2026-06-30 15:45:06'),
(89, 1, 'chef_1_presenting.png', 0, '2026-07-01 05:25:38'),
(90, 1, 'chef_1_interacting.png', 1, '2026-07-01 05:25:38'),
(91, 1, 'chef_1_signing.png', 2, '2026-07-01 05:25:38'),
(92, 1, 'chef_1_private_dining.png', 3, '2026-07-01 05:25:38'),
(93, 2, 'chef_2_presenting.png', 0, '2026-07-01 05:25:38'),
(94, 2, 'chef_2_interacting.png', 1, '2026-07-01 05:25:38'),
(95, 2, 'chef_2_signing.png', 2, '2026-07-01 05:25:38'),
(96, 2, 'chef_2_private_dining.png', 3, '2026-07-01 05:25:38'),
(97, 3, 'chef_3_presenting.png', 0, '2026-07-01 05:25:38'),
(98, 3, 'chef_3_interacting.png', 1, '2026-07-01 05:25:38'),
(99, 3, 'chef_3_signing.png', 2, '2026-07-01 05:25:38'),
(100, 3, 'chef_3_private_dining.png', 3, '2026-07-01 05:25:38'),
(101, 10, 'chef_10_presenting.png', 0, '2026-07-01 05:25:38'),
(102, 10, 'chef_10_interacting.png', 1, '2026-07-01 05:25:38'),
(103, 10, 'chef_10_signing.png', 2, '2026-07-01 05:25:38'),
(104, 10, 'chef_10_private_dining.png', 3, '2026-07-01 05:25:38'),
(105, 1, '1783101206_6a47f7167e6b4.png', 4, '2026-07-01 05:31:39'),
(106, 1, '1783101213_6a47f71d3b09a.png', 5, '2026-07-01 05:31:48'),
(107, 2, '1783101248_6a47f740e87c4.png', 4, '2026-07-03 17:53:49'),
(108, 2, '1783101241_6a47f7396e422.png', 5, '2026-07-03 17:54:01'),
(109, 3, '1783101263_6a47f74fa96d4.png', 4, '2026-07-03 17:54:23'),
(110, 3, '1783101271_6a47f75736316.png', 5, '2026-07-03 17:54:31'),
(111, 10, '1783101286_6a47f766a5201.png', 4, '2026-07-03 17:54:46'),
(112, 10, '1783101297_6a47f771385b8.png', 5, '2026-07-03 17:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `chef_reviews`
--

DROP TABLE IF EXISTS `chef_reviews`;
CREATE TABLE `chef_reviews` (
  `id` int(11) NOT NULL,
  `chef_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `author_name` varchar(100) DEFAULT 'Khách ẩn danh',
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `experience_type` varchar(100) DEFAULT 'Fine Dining',
  `chef_response` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chef_reviews`
--

INSERT INTO `chef_reviews` (`id`, `chef_id`, `user_id`, `author_name`, `rating`, `comment`, `created_at`, `status`, `experience_type`, `chef_response`) VALUES
(1, 1, NULL, 'Trần Quang Khải', 5, 'Chef không chỉ chế biến món ăn mà còn kể về nguồn gốc từng nguyên liệu. Nghệ thuật cân bằng hương vị thực sự xuất sắc. Đây là một trong những trải nghiệm ẩm thực đáng nhớ nhất của gia đình tôi.', '2026-06-29 02:38:52', 'approved', 'Chef\'s Table', 'Cảm ơn anh Khải đã lựa chọn Restaurantly và khu vực Chef\'s Table. Thấy thực khách tận hưởng trọn vẹn câu chuyện đằng sau món ăn chính là niềm hạnh phúc lớn nhất của chúng tôi.'),
(2, 1, NULL, 'Nguyễn Minh Nguyệt', 5, 'Một bữa tối kỷ niệm hoàn hảo. Đầu bếp đã chuẩn bị một thực đơn riêng dựa trên sở thích của hai vợ chồng. Nước sốt đậm đà, thịt bò Wagyu tan chảy trong miệng.', '2026-06-29 02:38:52', 'approved', 'Anniversary Dinner', 'Chúc mừng kỷ niệm của anh chị! Được góp phần làm nên một buổi tối đáng nhớ cho anh chị là niềm vinh hạnh của đội ngũ bếp.'),
(3, 2, NULL, 'Nguyễn Minh Nguyệt', 5, 'Một bữa tối kỷ niệm hoàn hảo. Đầu bếp đã chuẩn bị một thực đơn riêng dựa trên sở thích của hai vợ chồng. Nước sốt đậm đà, thịt bò Wagyu tan chảy trong miệng.', '2026-06-29 02:38:52', 'approved', 'Anniversary Dinner', 'Chúc mừng kỷ niệm của anh chị! Được góp phần làm nên một buổi tối đáng nhớ cho anh chị là niềm vinh hạnh của đội ngũ bếp.'),
(4, 2, NULL, 'Phạm Hoàng Yến', 4, 'Kỹ thuật Dry-aged thực sự làm món cá nổi bật, hương vị umami rất cô đặc. Không gian nhà hàng và cách Chef giao lưu với thực khách vô cùng chuyên nghiệp.', '2026-06-29 02:38:52', 'approved', 'Private Dining', 'Cảm ơn chị Yến. Chúng tôi đang tiếp tục thử nghiệm những mẻ cá Dry-aged mới và rất mong chị sẽ quay lại để thưởng thức.'),
(5, 3, NULL, 'Trần Quang Khải', 5, 'Chef không chỉ chế biến món ăn mà còn kể về nguồn gốc từng nguyên liệu. Nghệ thuật cân bằng hương vị thực sự xuất sắc. Đây là một trong những trải nghiệm ẩm thực đáng nhớ nhất của gia đình tôi.', '2026-06-29 02:38:52', 'approved', 'Chef\'s Table', 'Cảm ơn anh Khải đã lựa chọn Restaurantly và khu vực Chef\'s Table. Thấy thực khách tận hưởng trọn vẹn câu chuyện đằng sau món ăn chính là niềm hạnh phúc lớn nhất của chúng tôi.'),
(6, 3, NULL, 'Nguyễn Minh Nguyệt', 5, 'Một bữa tối kỷ niệm hoàn hảo. Đầu bếp đã chuẩn bị một thực đơn riêng dựa trên sở thích của hai vợ chồng. Nước sốt đậm đà, thịt bò Wagyu tan chảy trong miệng.', '2026-06-29 02:38:52', 'approved', 'Anniversary Dinner', 'Chúc mừng kỷ niệm của anh chị! Được góp phần làm nên một buổi tối đáng nhớ cho anh chị là niềm vinh hạnh của đội ngũ bếp.'),
(7, 10, NULL, 'Trần Quang Khải', 5, 'Chef không chỉ chế biến món ăn mà còn kể về nguồn gốc từng nguyên liệu. Nghệ thuật cân bằng hương vị thực sự xuất sắc. Đây là một trong những trải nghiệm ẩm thực đáng nhớ nhất của gia đình tôi.', '2026-06-29 02:38:52', 'approved', 'Chef\'s Table', 'Cảm ơn anh Khải đã lựa chọn Restaurantly và khu vực Chef\'s Table. Thấy thực khách tận hưởng trọn vẹn câu chuyện đằng sau món ăn chính là niềm hạnh phúc lớn nhất của chúng tôi.'),
(8, 10, NULL, 'Nguyễn Minh Nguyệt', 5, 'Một bữa tối kỷ niệm hoàn hảo. Đầu bếp đã chuẩn bị một thực đơn riêng dựa trên sở thích của hai vợ chồng. Nước sốt đậm đà, thịt bò Wagyu tan chảy trong miệng.', '2026-06-29 02:38:52', 'approved', 'Anniversary Dinner', 'Chúc mừng kỷ niệm của anh chị! Được góp phần làm nên một buổi tối đáng nhớ cho anh chị là niềm vinh hạnh của đội ngũ bếp.'),
(9, 12, NULL, 'Trần Quang Khải', 5, 'Chef không chỉ chế biến món ăn mà còn kể về nguồn gốc từng nguyên liệu. Nghệ thuật cân bằng hương vị thực sự xuất sắc. Đây là một trong những trải nghiệm ẩm thực đáng nhớ nhất của gia đình tôi.', '2026-06-29 02:38:52', 'approved', 'Chef\'s Table', 'Cảm ơn anh Khải đã lựa chọn Restaurantly và khu vực Chef\'s Table. Thấy thực khách tận hưởng trọn vẹn câu chuyện đằng sau món ăn chính là niềm hạnh phúc lớn nhất của chúng tôi.'),
(10, 12, NULL, 'Nguyễn Minh Nguyệt', 5, 'Một bữa tối kỷ niệm hoàn hảo. Đầu bếp đã chuẩn bị một thực đơn riêng dựa trên sở thích của hai vợ chồng. Nước sốt đậm đà, thịt bò Wagyu tan chảy trong miệng.', '2026-06-29 02:38:52', 'approved', 'Anniversary Dinner', 'Chúc mừng kỷ niệm của anh chị! Được góp phần làm nên một buổi tối đáng nhớ cho anh chị là niềm vinh hạnh của đội ngũ bếp.'),
(11, 13, NULL, 'Nguyễn Minh Nguyệt', 5, 'Một bữa tối kỷ niệm hoàn hảo. Đầu bếp đã chuẩn bị một thực đơn riêng dựa trên sở thích của hai vợ chồng. Nước sốt đậm đà, thịt bò Wagyu tan chảy trong miệng.', '2026-06-29 02:38:52', 'approved', 'Anniversary Dinner', 'Chúc mừng kỷ niệm của anh chị! Được góp phần làm nên một buổi tối đáng nhớ cho anh chị là niềm vinh hạnh của đội ngũ bếp.'),
(12, 13, NULL, 'Phạm Hoàng Yến', 4, 'Kỹ thuật Dry-aged thực sự làm món cá nổi bật, hương vị umami rất cô đặc. Không gian nhà hàng và cách Chef giao lưu với thực khách vô cùng chuyên nghiệp.', '2026-06-29 02:38:52', 'approved', 'Private Dining', 'Cảm ơn chị Yến. Chúng tôi đang tiếp tục thử nghiệm những mẻ cá Dry-aged mới và rất mong chị sẽ quay lại để thưởng thức.'),
(13, 14, NULL, 'Trần Quang Khải', 5, 'Chef không chỉ chế biến món ăn mà còn kể về nguồn gốc từng nguyên liệu. Nghệ thuật cân bằng hương vị thực sự xuất sắc. Đây là một trong những trải nghiệm ẩm thực đáng nhớ nhất của gia đình tôi.', '2026-06-29 02:38:52', 'approved', 'Chef\'s Table', 'Cảm ơn anh Khải đã lựa chọn Restaurantly và khu vực Chef\'s Table. Thấy thực khách tận hưởng trọn vẹn câu chuyện đằng sau món ăn chính là niềm hạnh phúc lớn nhất của chúng tôi.'),
(14, 14, NULL, 'Nguyễn Minh Nguyệt', 5, 'Một bữa tối kỷ niệm hoàn hảo. Đầu bếp đã chuẩn bị một thực đơn riêng dựa trên sở thích của hai vợ chồng. Nước sốt đậm đà, thịt bò Wagyu tan chảy trong miệng.', '2026-06-29 02:38:52', 'approved', 'Anniversary Dinner', 'Chúc mừng kỷ niệm của anh chị! Được góp phần làm nên một buổi tối đáng nhớ cho anh chị là niềm vinh hạnh của đội ngũ bếp.'),
(15, 15, NULL, 'Trần Quang Khải', 5, 'Chef không chỉ chế biến món ăn mà còn kể về nguồn gốc từng nguyên liệu. Nghệ thuật cân bằng hương vị thực sự xuất sắc. Đây là một trong những trải nghiệm ẩm thực đáng nhớ nhất của gia đình tôi.', '2026-06-29 02:38:52', 'approved', 'Chef\'s Table', 'Cảm ơn anh Khải đã lựa chọn Restaurantly và khu vực Chef\'s Table. Thấy thực khách tận hưởng trọn vẹn câu chuyện đằng sau món ăn chính là niềm hạnh phúc lớn nhất của chúng tôi.'),
(16, 15, NULL, 'Lê Vũ Anh', 5, 'Sự tinh tế trong từng đường cắt Sashimi và cách bài trí mang đậm chất thiền. Mỗi món ăn không chỉ ngon miệng mà còn là một tác phẩm nghệ thuật. Rất ấn tượng với sự tỉ mỉ của Bếp trưởng.', '2026-06-29 02:38:52', 'approved', 'Bespoke Menu', 'Rất vui vì anh Vũ Anh đã cảm nhận được triết lý ẩm thực chúng tôi muốn truyền tải. Hẹn gặp lại anh trong một dịp không xa.');

-- --------------------------------------------------------

--
-- Table structure for table `combos`
--

DROP TABLE IF EXISTS `combos`;
CREATE TABLE `combos` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `combos`
--

INSERT INTO `combos` (`id`, `name`, `description`, `price`, `image`, `status`, `is_active`, `created_at`, `theme_id`) VALUES
(1, 'Romantic Evening', 'Bít tết + salad cho 2 người', 12000000.00, 'e86b1160c79e65f1cb494d5d.jpg', 1, 1, '2026-05-14 08:00:00', NULL),
(2, 'The Olive Experience', 'phần ăn tinh hoa', 1200000.00, '7449c8a6ce9351e40490a778.jpg', 1, 1, '2026-05-23 07:15:51', 1),
(3, 'The Vega Grand Tasting', 'Bữa thưởng thức cao cấp 5 món - khai vị, chính, tráng miệng. Hành trình ẩm thực Michelin đẳng cấp cho 2 người.', 2000000.00, 'vega_grand_tasting.jpg', 1, 1, '2026-06-09 22:39:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `combo_items`
--

DROP TABLE IF EXISTS `combo_items`;
CREATE TABLE `combo_items` (
  `id` int(11) NOT NULL,
  `combo_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `combo_items`
--

INSERT INTO `combo_items` (`id`, `combo_id`, `food_id`) VALUES
(40, 2, 12),
(41, 2, 16),
(45, 1, 1),
(46, 1, 13),
(47, 3, 12),
(48, 3, 16),
(49, 3, 13),
(50, 3, 15),
(51, 3, 17);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `is_starred` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_note` text DEFAULT NULL,
  `reply_content` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `decor_packages`
--

DROP TABLE IF EXISTS `decor_packages`;
CREATE TABLE `decor_packages` (
  `id` int(11) NOT NULL,
  `event_type_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `decor_packages`
--

INSERT INTO `decor_packages` (`id`, `event_type_id`, `name`, `description`, `price`, `image_url`, `status`, `created_at`) VALUES
(4, 1, 'Gói Sinh Nhật Cơ Bản', 'Bóng bay màu sắc cơ bản, chữ Happy Birthday, 1 nến pháo hoa, trang trí bàn đơn giản.', 300000.00, 'public/assets/img/decors/birthday_basic.jpg', 'active', '2026-07-01 12:13:46'),
(5, 1, 'Gói Sinh Nhật Lung Linh', 'Bóng bay nghệ thuật thả trần, chữ Happy Birthday đèn LED, hoa hồng để bàn, thiệp chúc mừng viết tay.', 750000.00, 'public/assets/img/decors/birthday_pro.jpg', 'active', '2026-07-01 12:13:46'),
(6, 1, 'Gói Sinh Nhật VIP', 'Trang trí toàn bộ không gian riêng với bóng bay mạ vàng, nến điện tử lung linh, bánh kem thiết kế riêng và thợ chụp ảnh 30 phút.', 2500000.00, 'public/assets/img/decors/birthday_vip.jpg', 'active', '2026-07-01 12:13:46'),
(7, 2, 'Gói Kỷ Niệm Ngọt Ngào', 'Hoa hồng rải bàn, 2 ly rượu vang khai vị, nến thơm lãng mạn.', 450000.00, 'public/assets/img/decors/anniversary_sweet.jpg', 'active', '2026-07-01 12:13:46'),
(8, 2, 'Gói Kỷ Niệm Nồng Nàn', 'Bó hoa hồng 99 đóa, bóng bay hình trái tim, thiệp kỷ niệm cao cấp, rượu vang đỏ sành điệu.', 1200000.00, 'public/assets/img/decors/anniversary_passionate.jpg', 'active', '2026-07-01 12:13:46'),
(9, 2, 'Gói Kỷ Niệm Tình Yêu Vĩnh Cửu', 'Bàn VIP riêng tư, nghệ sĩ vĩ cầm chơi nhạc tại bàn, trang trí toàn bộ bằng hoa hồng tươi nhập khẩu, chai Champagne cao cấp.', 4500000.00, 'public/assets/img/decors/anniversary_forever.jpg', 'active', '2026-07-01 12:13:46'),
(10, 3, 'Gói Tỏ Tình Chân Thành', 'Bóng bay chữ \"I Love You\", rải cánh hoa hồng xếp hình trái tim, nến lung linh trên bàn.', 600000.00, 'public/assets/img/decors/proposal_sincere.jpg', 'active', '2026-07-01 12:13:46'),
(11, 3, 'Gói Cầu Hôn Lãng Mạn', 'Bóng bay chữ \"Marry Me\", lối đi trải thảm hoa hồng, nến điện tử rực rỡ, pháo sáng mini khi trao nhẫn.', 1500000.00, 'public/assets/img/decors/proposal_romantic.jpg', 'active', '2026-07-01 12:13:46'),
(12, 3, 'Gói Cầu Hôn Trong Mơ', 'Bao trọn không gian VIP, trang trí hoa tươi nghệ thuật 100%, có quay phim chụp ảnh ghi lại khoảnh khắc, nhẫn được đặt trong hộp hoa đặc biệt.', 5000000.00, 'public/assets/img/decors/proposal_dream.jpg', 'active', '2026-07-01 12:13:46'),
(13, 4, 'Gói Gắn Kết Đội Ngũ', 'Bảng tên công ty chào đón, thiệp cảm ơn đặt tại mỗi vị trí ngồi, trang trí tone màu thanh lịch.', 400000.00, 'public/assets/img/decors/company_basic.jpg', 'active', '2026-07-01 12:13:46'),
(14, 4, 'Gói Tiệc Doanh Nghiệp', 'Hoa tươi cao cấp chạy dọc bàn tiệc dài, thẻ tên riêng cho từng vị khách, in logo công ty lên menu.', 1200000.00, 'public/assets/img/decors/company_pro.jpg', 'active', '2026-07-01 12:13:46'),
(15, 4, 'Gói Dạ Tiệc Thượng Lưu', 'Setup toàn bộ bàn tiệc chuẩn Fine Dining với ly pha lê, hoa tươi nhập khẩu thiết kế riêng, có máy chiếu và backdrop chụp ảnh mang logo công ty.', 3500000.00, 'public/assets/img/decors/company_vip.jpg', 'active', '2026-07-01 12:13:46'),
(16, 5, 'Gói Trang Trí Nhẹ Nhàng', 'Nến thơm và một lẵng hoa nhỏ tinh tế đặt giữa bàn, mang lại cảm giác ấm cúng, thư giãn.', 200000.00, 'public/assets/img/decors/other_light.jpg', 'active', '2026-07-01 12:13:46'),
(17, 5, 'Gói Bất Ngờ Thú Vị', 'Bóng bay giấu trong hộp quà, khi mở ra bóng bay lên mang theo lời chúc ý nghĩa, phù hợp để tạo bất ngờ.', 650000.00, 'public/assets/img/decors/other_surprise.jpg', 'active', '2026-07-01 12:13:46'),
(18, 5, 'Gói Thiết Kế Riêng (Bespoke)', 'Nhà hàng sẽ liên hệ để lên ý tưởng và thiết kế gói trang trí 100% theo đúng sở thích và yêu cầu độc bản của bạn.', 2000000.00, 'public/assets/img/decors/other_bespoke.jpg', 'active', '2026-07-01 12:13:46');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `identity_card` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `position` varchar(100) DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT 0.00,
  `status` enum('working','on_leave','resigned') DEFAULT 'working',
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_blob` longblob DEFAULT NULL,
  `avatar_mime` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `full_name`, `phone`, `email`, `identity_card`, `address`, `dob`, `gender`, `position`, `salary`, `status`, `avatar`, `avatar_blob`, `avatar_mime`, `created_at`) VALUES
(5, 'Huynh Duc Thong', '0901 234 567', 'thongd342@gmail.com', '102910382038141', 'biên hòa', '2003-02-03', 'male', 'Bếp trưởng', 500000.00, 'working', NULL, NULL, NULL, '2026-06-02 14:22:51'),
(7, 'Nguyễn Đăng Khoa', '0901 234 567', 'khoa.nguyen@email.com', '016728394506', 'biên hòa', '2025-07-29', 'male', 'Thu ngân', 400000.00, 'working', NULL, NULL, NULL, '2026-06-23 03:25:55'),
(8, 'Chef Minh', NULL, '', NULL, NULL, NULL, 'other', 'Bếp trưởng', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(9, 'Chef long', NULL, '', NULL, NULL, NULL, 'other', 'Bếp phó', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(10, 'Phát', NULL, '', NULL, NULL, NULL, 'other', 'Bếp chính', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(11, 'Nguyễn Văn A', NULL, '', NULL, NULL, NULL, 'other', 'Bếp chính', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(12, 'Trần Văn B', NULL, NULL, NULL, NULL, NULL, 'other', 'Phụ bếp', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(13, 'Lê Văn C', NULL, NULL, NULL, NULL, NULL, 'other', 'Phụ bếp', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(14, 'Phạm Văn D', NULL, NULL, NULL, NULL, NULL, 'other', 'Phụ bếp', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(15, 'Hoàng Văn E', NULL, NULL, NULL, NULL, NULL, 'other', 'Phụ bếp', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56'),
(16, 'Đỗ Văn F', NULL, NULL, NULL, NULL, NULL, 'other', 'Phụ bếp', 0.00, 'working', NULL, NULL, NULL, '2026-06-23 03:27:56');

-- --------------------------------------------------------

--
-- Table structure for table `event_types`
--

DROP TABLE IF EXISTS `event_types`;
CREATE TABLE `event_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_types`
--

INSERT INTO `event_types` (`id`, `name`, `description`, `image_url`, `status`, `created_at`) VALUES
(1, 'Sinh nhật', 'Tiệc sinh nhật ấm cúng', 'public/assets/images/events/event_birthday.jpg', 'active', '2026-06-12 03:35:53'),
(2, 'Kỷ niệm ngày cưới', 'Kỷ niệm ngày cưới lãng mạn', 'public/assets/images/events/event_anniversary.jpg', 'active', '2026-06-12 03:35:53'),
(3, 'Tỏ tình / Cầu hôn', 'Không gian tỏ tình siêu lãng mạn', 'public/assets/images/events/event_proposal.jpg', 'active', '2026-06-12 03:35:53'),
(4, 'Họp mặt / Công ty', 'Không gian riêng tư cho công ty', 'public/assets/images/events/event_gathering.jpg', 'active', '2026-06-12 03:35:53'),
(5, 'Khác', 'Các loại hình kỷ niệm khác', 'public/assets/images/events/event_other.jpg', 'active', '2026-06-12 03:35:53');

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

DROP TABLE IF EXISTS `foods`;
CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `max_toppings` int(11) DEFAULT 4,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_chef_recommended` tinyint(1) DEFAULT 0,
  `allergens` text DEFAULT NULL,
  `wine_pairing_id` int(11) DEFAULT NULL,
  `chef_note` text DEFAULT NULL,
  `food_journey` text DEFAULT NULL,
  `cooking_technique` text DEFAULT NULL,
  `theme_id` int(11) DEFAULT NULL,
  `cooking_status` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `category_id`, `name`, `price`, `image`, `description`, `status`, `max_toppings`, `is_active`, `is_chef_recommended`, `allergens`, `wine_pairing_id`, `chef_note`, `food_journey`, `cooking_technique`, `theme_id`, `cooking_status`) VALUES
(1, 2, 'Beefsteak', 400000.00, '7d76786780be41b26cea039d.jpg', 'Thăn nội bò nướng than hoa mềm ngọt, kèm sốt tiêu đen đặc biệt.', 1, 4, 1, 1, '', NULL, 'Tôi luôn chọn phần thăn ngoại (Striploin) với lớp mỡ viền vừa đủ để khi áp chảo, hương thơm béo ngậy quyện chặt vào từng thớ thịt đỏ au. Một chút muối biển Maldon là đủ để đánh thức mọi giác quan.', '{\"origin\":\"B\\u1eaft ngu\\u1ed3n t\\u1eeb nh\\u1eefng th\\u1ea3o nguy\\u00ean xanh m\\u01b0\\u1edbt, n\\u01a1i nu\\u00f4i d\\u01b0\\u1ee1ng d\\u00f2ng b\\u00f2 Wagyu tr\\u1ee9 danh.\",\"selection\":\"Ch\\u1ec9 nh\\u1eefng th\\u1edb th\\u1ecbt c\\u00f3 v\\u00e2n m\\u1ee1 c\\u1ea9m th\\u1ea1ch ho\\u00e0n h\\u1ea3o \\u0111\\u1ea1t chu\\u1ea9n A5 m\\u1edbi \\u0111\\u01b0\\u1ee3c ch\\u1ecdn l\\u1ecdc.\",\"storage\":\"Tr\\u1ea3i qua qu\\u00e1 tr\\u00ecnh l\\u00ean men kh\\u00f4 (Dry-aged) 21 ng\\u00e0y trong ph\\u00f2ng v\\u00f4 tr\\u00f9ng \\u0111\\u1ec3 h\\u01b0\\u01a1ng v\\u1ecb tr\\u1edf n\\u00ean c\\u00f4 \\u0111\\u1eb7c.\",\"prep\":\"C\\u1eaft g\\u1ecdt th\\u1ee7 c\\u00f4ng, \\u01b0\\u1edbp c\\u00f9ng mu\\u1ed1i bi\\u1ec3n Flaky v\\u00e0 ti\\u00eau \\u0111en nguy\\u00ean h\\u1ea1t xay v\\u1ee1.\",\"cooking_art\":\"N\\u01b0\\u1edbng tr\\u00ean l\\u1eeda than c\\u1ee7i Oga nhi\\u1ec7t \\u0111\\u1ed9 cao, \\u00e1p ch\\u1ea3o c\\u00f9ng b\\u01a1 t\\u1ecfi v\\u00e0 c\\u1ecf x\\u1ea1 h\\u01b0\\u01a1ng t\\u01b0\\u01a1i.\",\"presentation\":\"Th\\u1ecbt b\\u00f2 \\u0111\\u01b0\\u1ee3c th\\u00e1i l\\u00e1t ho\\u00e0n h\\u1ea3o, \\u0111\\u1ec3 l\\u1ed9 m\\u00e0u h\\u1ed3ng Ruby r\\u1ef1c r\\u1ee1, \\u0111i k\\u00e8m l\\u1edbp x\\u1ed1t Demi-Glace b\\u00f3ng b\\u1ea9y.\",\"origin_img\":\"wagyu_cows_meadow.png\",\"selection_img\":\"beef_selection_1782652185664.png\",\"storage_img\":\"beef_storage_1782652202575.png\",\"prep_img\":\"beef_prep_1782652218509.png\",\"cooking_art_img\":\"beef_cooking_1782652234858.png\",\"presentation_img\":\"beef_presentation_1782652249868.png\",\"certificate_img\":\"cert_mock_new.jpg\",\"cert_title\":\"Japanese Wagyu A5 Authenticity\",\"cert_country\":\"Miyazaki - Japan\",\"cert_provider\":\"Ozaki Farm\",\"cert_date\":\"10\\/11\\/2026\"}', '', NULL, ''),
(3, 2, 'Cá Hồi Áp Chảo', 180000.00, '1779508455_6a1124e77c5f4.jpg', 'Cá hồi Na Uy tươi áp chảo xém da, dùng kèm sốt chanh leo chua ngọt.', 1, 4, 1, 0, '', NULL, 'Bí quyết nằm ở chỗ chỉ áp chảo một mặt da cho đến khi giòn rụm, phần thịt còn lại được nấu chín bằng nhiệt độ tỏa lên để giữ trọn vẹn vị ngọt nguyên bản của vùng biển lạnh.', '{\"origin\":\"Từ những dòng hải lưu lạnh giá, tinh khiết của vùng biển Na Uy.\",\"selection\":\"Cá hồi được đánh bắt theo phương pháp bền vững, chọn phần phi lê lưng dày dặn và giàu Omega-3 nhất.\",\"storage\":\"Cấp đông siêu tốc ngay trên tàu để giữ trọn vẹn kết cấu săn chắc tự nhiên.\",\"prep\":\"Rút xương tỉ mỉ, khứa nhẹ lớp da để khi áp chảo không bị co rút.\",\"cooking_art\":\"Kỹ thuật áp chảo một mặt (Skin-on Sear) giúp lớp da giòn rụm hoàn hảo nhưng phần thịt vẫn mượt mà.\",\"presentation\":\"Đặt nhẹ nhàng trên thảm khoai tây nghiền mịn màng, điểm xuyết thêm bọt chanh dây (Foam) nghệ thuật.\",\"origin_img\":\"1783312838_6a4b31c61567c.jpg\",\"selection_img\":\"1783312838_6a4b31c615d26.jpg\",\"storage_img\":\"1783312838_6a4b31c61716c.png\",\"prep_img\":\"1783312838_6a4b31c617c63.jpg\",\"cooking_art_img\":\"1783312838_6a4b31c618aea.jpg\",\"presentation_img\":\"1783312838_6a4b31c61944c.jpg\",\"certificate_img\":\"1783312838_6a4b31c61a281.png\"}', '', NULL, ''),
(5, 2, 'Mì Ý Hải Sản', 150000.00, '1779508403_6a1124b3b9104.webp', 'Mì Spaghetti xào tôm, mực, vẹm xanh sốt cà chua cay nhẹ.', 1, 4, 1, 0, '', NULL, 'Sợi mì được luộc vừa chín tới (Al dente) để giữ độ dai giòn, sau đó xóc đều trên chảo cùng nước cốt hầm hải sản nguyên chất để từng sợi mì ngậm trọn tinh túy đại dương.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783312920_6a4b32182f4c5.jpg\",\"selection_img\":\"1783312920_6a4b321830d61.png\",\"storage_img\":\"1783312920_6a4b321831914.jpg\",\"prep_img\":\"1783312920_6a4b3218325f0.jpg\",\"cooking_art_img\":\"1783312920_6a4b321833617.jpg\",\"presentation_img\":\"1783312920_6a4b32183451e.png\",\"certificate_img\":\"1783312920_6a4b321835cf3.png\"}', '', NULL, ''),
(6, 5, 'Salad Cá Ngừ', 95000.00, '1779508380_6a11249cb4e61.png', 'Rau xanh tổng hợp mix cá ngừ đại dương, trứng cút và sốt dầu giấm.', 1, 4, 1, 0, '', NULL, 'Sự tươi mát của rau mầm trồng trong nhà kính kết hợp cùng sốt giấm balsamic ủ 10 năm sẽ đánh thức vị giác của bạn trước khi bước vào món chính.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783331253_6a4b79b52e685.webp\",\"selection_img\":\"1783331307_6a4b79ebec9dd.webp\",\"storage_img\":\"1783331333_6a4b7a0504f17.png\",\"prep_img\":\"1783331400_6a4b7a4834e8a.webp\",\"cooking_art_img\":\"1783331446_6a4b7a762856a.webp\",\"presentation_img\":\"1783331400_6a4b7a4835a21.webp\",\"certificate_img\":\"1783331307_6a4b79ebedc26.png\"}', '', NULL, ''),
(7, 3, 'Soup Kem Nấm', 65000.00, '1779508350_6a11247ee9fae.jpg', 'Soup nấm hương nấm mỡ xay mịn nấu cùng kem tươi béo ngậy.', 1, 4, 1, 0, '', NULL, 'Một món súp kinh điển đòi hỏi sự nhẫn nại. Nấm Truffle và nấm hương rừng được xào chậm với bơ Pháp trước khi xay nhuyễn cùng kem tươi hảo hạng.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783332172_6a4b7d4c58137.webp\",\"selection_img\":\"1783332187_6a4b7d5b8b504.webp\",\"storage_img\":\"1783332103_6a4b7d07f1800.png\",\"prep_img\":\"1783332094_6a4b7cfee9f29.webp\",\"cooking_art_img\":\"1783332048_6a4b7cd0a847a.webp\",\"presentation_img\":\"1783332024_6a4b7cb89da40.webp\"}', '', NULL, ''),
(11, 5, 'Bánh Mì Bơ Tỏi', 45000.00, '1779508232_6a112408d74a9.webp', 'Bánh mì baguette nướng giòn rụm phết bơ tỏi và lá thơm băm nhỏ.', 1, 4, 1, 0, '', NULL, 'Phần bơ tỏi được pha trộn theo tỷ lệ bí mật với ngò tây tươi, phết lên những lát bánh mì baguette nướng giòn rụm, tỏa hương thơm nức mũi.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783331032_6a4b78d830cf9.webp\",\"selection_img\":\"1783331047_6a4b78e7b6dd4.webp\",\"storage_img\":\"1783330927_6a4b786f0f90d.png\",\"prep_img\":\"1783331086_6a4b790e0efdf.webp\",\"cooking_art_img\":\"1783331194_6a4b797a112c1.webp\",\"presentation_img\":\"1783331140_6a4b7944a96a2.webp\",\"certificate_img\":\"1783330927_6a4b786f1005c.png\"}', '', 1, ''),
(12, 2, 'Beef Wellington', 850000.00, '1780713599_6a23887fcefe9.jpg', 'Thăn bò hảo hạng cuộn trong lớp nấm truffles và vỏ bánh ngàn lớp nướng vàng rụm.', 1, 4, 1, 0, '', NULL, 'Đây là món ăn thử thách mọi kỹ năng của đầu bếp: Lõi thăn bò hảo hạng, lớp pate nấm Truffle đen ngậy béo, và lớp vỏ bánh ngàn lớp vàng ươm phải hoàn hảo đến từng milimet.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783313632_6a4b34e0bd0f6.jpg\",\"selection_img\":\"1783313632_6a4b34e0bd758.jpg\",\"storage_img\":\"1783313632_6a4b34e0bdc0e.jpg\",\"prep_img\":\"1783313258_6a4b336ad4666.png\",\"cooking_art_img\":\"1783313632_6a4b34e0bee01.png\",\"presentation_img\":\"1783313293_6a4b338d8ae77.png\",\"certificate_img\":\"1783313632_6a4b34e0bf6a7.png\"}', '', 1, ''),
(13, 2, 'Duck Breast with Cherry Reduction', 650000.00, '1780713649_6a2388b12a6a1.jpg', 'Ức vịt áp chảo mềm mọng dùng kèm sốt cherry đỏ cô đặc chua ngọt tinh tế.', 1, 4, 1, 0, '', NULL, 'Ức vịt áp chảo khéo léo để phần da giòn rụm tan mỡ nhưng thịt vẫn giữ màu hồng đào (medium rare). Sốt Cherry Reduction với chút vang đỏ là điểm nhấn chua ngọt cân bằng.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783314503_6a4b3847b8a9e.png\",\"selection_img\":\"1783314371_6a4b37c315d6f.webp\",\"storage_img\":\"1783314302_6a4b377e7cc15.webp\",\"prep_img\":\"1783314470_6a4b38260e225.webp\",\"cooking_art_img\":\"1783314422_6a4b37f61d6bd.webp\",\"presentation_img\":\"1783314159_6a4b36ef2e4a2.webp\",\"certificate_img\":\"1783314334_6a4b379ee990e.png\"}', '', NULL, ''),
(14, 2, 'Herb-Crusted Lamb Rack', 750000.00, '1780713689_6a2388d90ba6f.jpg', 'Sườn cừu Pháp nướng phủ lớp vụn bánh mì và thảo mộc thơm lừng.', 1, 4, 1, 0, '', NULL, 'Sườn cừu được bọc trong một lớp vỏ thảo mộc tươi (hương thảo, ngò tây, vụn bánh mì), nướng vừa tới để giữ độ mọng nước mà không hề có mùi gắt đặc trưng.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783314697_6a4b390923888.jpg\",\"selection_img\":\"1783314697_6a4b39092432a.jpg\",\"storage_img\":\"1783314963_6a4b3a13457aa.webp\",\"prep_img\":\"1783315082_6a4b3a8a0d79f.webp\",\"cooking_art_img\":\"1783315122_6a4b3ab2dabe9.webp\",\"presentation_img\":\"1783315139_6a4b3ac3ea922.webp\",\"certificate_img\":\"1783315167_6a4b3adf00249.png\"}', '', NULL, ''),
(15, 1, 'Seared Hokkaido Scallops', 950000.00, '1780713718_6a2388f69f231.jpg', 'Cồi sò điệp Hokkaido áp chảo dùng kèm sốt bơ chanh vàng béo ngậy.', 1, 4, 1, 0, '', NULL, 'Còi sò điệp Hokkaido to bản chỉ cần áp chảo thật nhanh trên lửa lớn để xém vàng hai mặt. Vị ngọt lịm tự nhiên của hải sản vùng nước lạnh không cần quá nhiều gia vị phô trương.', '{\"origin\":\"Cồi sò điệp Hokkaido được đánh bắt tại vùng biển lạnh giá phía Bắc Nhật Bản, nơi dòng hải lưu Oyashio đi qua, mang lại độ ngọt thịt tự nhiên và chất lượng hảo hạng bậc nhất.\",\"selection\":\"Tuyển chọn khắt khe những cá thể sò điệp trưởng thành đạt chuẩn loại 1 (Jumbo), đánh bắt tự nhiên và cấp đông sâu ngay trên tàu để giữ vẹn nguyên sự tươi mới tinh khiết.\",\"storage\":\"Bảo quản ở nhiệt độ -40 độ C chuẩn sashimi, rã đông chậm tự nhiên trong môi trường lạnh nhằm bảo vệ tuyệt đối kết cấu mô thịt và giữ trọn vị ngọt của biển cả.\",\"prep\":\"Sơ chế tỉ mỉ, làm sạch nhẹ nhàng qua nước muối loãng, sau đó thấm khô hoàn toàn bề mặt bằng giấy chuyên dụng để đảm bảo hiệu ứng caramel hóa khi áp chảo.\",\"cooking_art\":\"Nghệ thuật áp chảo nhanh (Pan-Searing) đỉnh cao trên chảo gang nhiệt độ cao cùng bơ lạt Pháp, tạo lớp vỏ vàng nâu xém cạnh giòn tan ôm trọn phần lõi mọng nước, mềm mịn như bơ.\",\"presentation\":\"Được tôn vinh như một tác phẩm nghệ thuật, điểm xuyết cùng nấm truffle đen, trứng cá tầm Caviar và một lớp xốt bơ chanh vàng óng, kích thích trọn vẹn mọi giác quan.\",\"origin_img\":\"1783316005_6a4b3e254763e.webp\",\"selection_img\":\"1783317272_6a4b4318afd39.webp\",\"storage_img\":\"1783318648_6a4b48785b22d.png\",\"prep_img\":\"scallop_prep_new.png\",\"cooking_art_img\":\"1783320195_6a4b4e83a670f.png\",\"presentation_img\":\"scallop_presentation_new.png\",\"certificate_img\":\"1783317249_6a4b43016f9e9.png\"}', '', NULL, ''),
(16, 1, 'Burrata & Heirloom Tomato', 350000.00, '1780713742_6a23890e80b50.jpg', 'Phô mai Burrata tươi béo ngậy ăn cùng cà chua Heirloom và sốt dầu giấm balsamic.', 1, 4, 1, 0, '', NULL, 'Tôm sú tươi sống bật nhảy được xóc nhanh qua lửa lớn cùng muối hồng Himalaya và ớt sừng non, lớp vỏ ngoài giòn rụm nhưng thịt bên trong vẫn ngọt lịm.', '{\"origin\":\"Mang trong mình linh hồn của nền ẩm thực đồng quê giao thoa với sự tinh tế của ẩm thực đương đại.\",\"selection\":\"Sử dụng 100% nguyên liệu hữu cơ (Organic) và hải sản\\/thịt cao cấp nhập khẩu trực tiếp.\",\"storage\":\"Kiểm soát nhiệt độ nghiêm ngặt, áp dụng công nghệ Dry-age hoặc ướp đá sinh học để giữ trọn độ mọng nước.\",\"prep\":\"Sơ chế tỉ mỉ, loại bỏ hoàn toàn các phần viền thừa để giữ lại những lát cắt hoàn hảo nhất.\",\"cooking_art\":\"Áp dụng phương pháp nấu chậm (Sous-vide) hoặc áp chảo lửa lớn (Searing) để khóa chặt hương vị cốt lõi.\",\"presentation\":\"Sắp xếp như một tác phẩm nghệ thuật trên đĩa sứ thủ công, rưới thêm một lớp xốt sóng sánh đầy mê hoặc.\",\"origin_img\":\"1783315835_6a4b3d7b79000.webp\",\"selection_img\":\"1783315866_6a4b3d9a8195a.webp\",\"storage_img\":\"1783315599_6a4b3c8f8d075.png\",\"prep_img\":\"1783315892_6a4b3db4c6369.webp\",\"cooking_art_img\":\"1783315599_6a4b3c8f8dcdf.webp\",\"presentation_img\":\"1783315551_6a4b3c5fcf1fb.webp\",\"certificate_img\":\"1783315928_6a4b3dd84ddb9.png\"}', '', NULL, ''),
(17, 1, 'Tuna Tartare', 400000.00, '1780713767_6a238927e7dd3.jpg', 'Cá ngừ đại dương xắt lựu tẩm ướp tinh tế, dùng kèm quả bơ và bánh quy giòn.', 1, 4, 1, 0, '', NULL, 'Cá ngừ đại dương tươi rói được thái hạt lựu, ướp cùng dầu mè, tương tương và một chút chanh vàng để tôn lên độ thanh mát, tan ngay trong miệng.', '{\"origin\":\"Bắt nguồn từ những mẻ cá ngừ vây xanh (Bluefin Tuna) khổng lồ được đánh bắt tự nhiên ngoài khơi đại dương, mang theo hương vị thuần khiết và tươi mới nhất của biển cả.\",\"selection\":\"Chỉ những phần thăn cá ngừ (Loin) mang sắc đỏ ngọc ruby rực rỡ với tỷ lệ nạc mỡ hoàn hảo mới được các bậc thầy tuyển chọn để đảm bảo chất lượng tuyệt đối.\",\"storage\":\"Lưu trữ nghiêm ngặt trong hệ thống cấp đông sâu -60 độ C chuẩn quốc tế, giúp khóa chặt độ tươi ngon và cấu trúc protein của cá ngay sau khi đánh bắt.\",\"prep\":\"Thực hiện bởi bàn tay tài hoa của bếp trưởng, dùng dao Yanagiba sắc lẹm thái hạt lựu từng thớ cá một cách dứt khoát, giữ nguyên kết cấu đàn hồi nguyên bản.\",\"cooking_art\":\"Pha trộn tinh tế với dầu mè thơm lừng, nước tương hảo hạng và hẹ xắt nhỏ, phối hợp nhịp nhàng để các gia vị quyện chặt vào từng miếng cá mà không làm mất đi vị ngọt tự nhiên.\",\"presentation\":\"Trình bày theo phong cách đương đại: Tartare cá ngừ tươi nguyên bản, dùng kèm xốt kem béo ngậy điểm xuyết trứng cá, ăn cùng các loại vi rau mầm, vụn bánh giòn tan và bánh mì nướng than hoa trên nền đĩa đá phiến mộc mạc.\",\"origin_img\":\"tuna_origin.png\",\"selection_img\":\"tuna_selection.png\",\"storage_img\":\"tuna_storage.png\",\"prep_img\":\"tuna_prep.png\",\"cooking_art_img\":\"tuna_cooking_art.png\",\"presentation_img\":\"tuna_presentation.png\",\"certificate_img\":\"1783315457_6a4b3c01b3987.png\"}', '', NULL, ''),
(18, 4, 'Signature Truffle Martini', 400000.00, '1781149007_6a2a2d4f3f01a.jpg', 'Sự kết hợp hoàn hảo giữa Gin thượng hạng, dầu Nấm Truffle trắng và một chút Vermouth ủ mộc. Sang trọng, đậm đà và vương giả.', 1, 4, 1, 0, '', NULL, 'Không chỉ là một ly cocktail, đó là một trải nghiệm thị giác và khứu giác. Dầu nấm Truffle đen được nhỏ vài giọt lên bề mặt, mang lại hương vị ngai ngái đầy bí ẩn.', '{\"origin\":\"Lấy cảm hứng từ những khu vườn trái cây nhiệt đới tươi mát và nghệ thuật pha chế thủ công lâu đời.\",\"selection\":\"Tuyển chọn những loại nguyên liệu tươi ngon nhất trong ngày, kết hợp cùng các loại rượu vang\\/spirit thượng hạng.\",\"storage\":\"Bảo quản ở nhiệt độ tiêu chuẩn để đảm bảo sự cân bằng hoàn hảo về cấu trúc và hương vị.\",\"prep\":\"Thực hiện kỹ thuật chiết xuất chậm để lấy trọn vẹn tinh chất tự nhiên của nguyên liệu.\",\"cooking_art\":\"Sử dụng kỹ thuật pha chế hiện đại (Mixology) giúp hương vị hòa quyện đa tầng mượt mà.\",\"presentation\":\"Phục vụ trong ly pha lê sang trọng, điểm xuyết bằng một nhánh thảo mộc tươi để đánh thức khứu giác.\",\"origin_img\":\"1783331946_6a4b7c6a0e199.webp\",\"cooking_art_img\":\"1783331969_6a4b7c81cf299.webp\",\"presentation_img\":\"1783331985_6a4b7c9184186.webp\"}', '', NULL, ''),
(19, 6, 'Smoked Rosemary Old Fashioned', 380000.00, '1781148879_6a2a2ccfe97e9.jpg', 'Rượu Bourbon Whiskey ủ lâu năm hòa quyện cùng mật ong nguyên chất rừng sâu, khói hương thảo nướng cháy mang lại hậu vị sâu lắng, rất phù hợp cho những ngày lễ và tiết trời se lạnh mùa này.', 1, 4, 1, 0, '', NULL, 'Khói gỗ hương thảo đốt cháy chậm sẽ quẩn quanh trong ly pha lê, đánh thức hương vị caramel của rượu Bourbon lâu năm. Một ly rượu dành cho những tâm hồn sâu sắc.', '{\"origin\":\"Lấy cảm hứng từ những khu vườn trái cây nhiệt đới tươi mát và nghệ thuật pha chế thủ công lâu đời.\",\"selection\":\"Tuyển chọn những loại nguyên liệu tươi ngon nhất trong ngày, kết hợp cùng các loại rượu vang\\/spirit thượng hạng.\",\"storage\":\"Bảo quản ở nhiệt độ tiêu chuẩn để đảm bảo sự cân bằng hoàn hảo về cấu trúc và hương vị.\",\"prep\":\"Thực hiện kỹ thuật chiết xuất chậm để lấy trọn vẹn tinh chất tự nhiên của nguyên liệu.\",\"cooking_art\":\"Sử dụng kỹ thuật pha chế hiện đại (Mixology) giúp hương vị hòa quyện đa tầng mượt mà.\",\"presentation\":\"Phục vụ trong ly pha lê sang trọng, điểm xuyết bằng một nhánh thảo mộc tươi để đánh thức khứu giác.\",\"origin_img\":\"1783331897_6a4b7c39b1e2b.webp\",\"prep_img\":\"1783331834_6a4b7bfae3f96.webp\",\"cooking_art_img\":\"1783331855_6a4b7c0f33e27.webp\",\"presentation_img\":\"1783331800_6a4b7bd82fda8.webp\"}', '', NULL, ''),
(20, 4, 'Margarita hoa hồng lựu', 250000.00, '1781148793_6a2a2c79ba047.webp', 'Margarita lựu hoa hồng là một thức uống thơm ngon và đầy không khí lễ hội, không thể thiếu trong bất kỳ bữa tiệc nào. Vị chua thanh của lựu kết hợp tuyệt vời với vị ngọt dịu từ siro hoa hồng. Viền ly bằng một lớp muối hoa hồng và bạn sẽ thấy mình muốn nhâm nhi thức uống này suốt mùa đông.', 1, 4, 1, 0, '', NULL, 'Sự lãng mạn được rót vào ly với cánh hoa hồng tươi xay nhuyễn và nước lựu ép lạnh. Vành ly viền muối biển sẽ trung hòa độ chua ngọt một cách hoàn hảo.', '{\"origin\":\"Lấy cảm hứng từ những khu vườn trái cây nhiệt đới tươi mát và nghệ thuật pha chế thủ công lâu đời.\",\"selection\":\"Tuyển chọn những loại nguyên liệu tươi ngon nhất trong ngày, kết hợp cùng các loại rượu vang\\/spirit thượng hạng.\",\"storage\":\"Bảo quản ở nhiệt độ tiêu chuẩn để đảm bảo sự cân bằng hoàn hảo về cấu trúc và hương vị.\",\"prep\":\"Thực hiện kỹ thuật chiết xuất chậm để lấy trọn vẹn tinh chất tự nhiên của nguyên liệu.\",\"cooking_art\":\"Sử dụng kỹ thuật pha chế hiện đại (Mixology) giúp hương vị hòa quyện đa tầng mượt mà.\",\"presentation\":\"Phục vụ trong ly pha lê sang trọng, điểm xuyết bằng một nhánh thảo mộc tươi để đánh thức khứu giác.\",\"origin_img\":\"1783331760_6a4b7bb0574eb.jpg\",\"cooking_art_img\":\"1783331716_6a4b7b846efe6.webp\",\"presentation_img\":\"1783331698_6a4b7b72ac486.webp\"}', '', NULL, ''),
(21, 6, 'cocktail Gold Rush', 450000.00, '1781148667_6a2a2bfb171d5.jpg', 'Vang đỏ Cabernet Sauvignon cao cấp mix cùng trái cây nhiệt đới, điểm xuyết những vảy vàng 24k ăn được. Phù hợp cho những dịp kỷ niệm.', 1, 4, 1, 1, '', NULL, 'Một ly Gold Rush chuẩn vị cần sự cân bằng tuyệt đối giữa vị chua thanh của chanh vàng nguyên bản và độ ngọt sâu của mật ong rừng. Lắc thật mạnh cùng đá viên lớn để ly cocktail đạt độ lạnh sâu mà không bị loãng.', '{\"origin\":\"Lấy cảm hứng từ những khu vườn trái cây nhiệt đới tươi mát và nghệ thuật pha chế thủ công lâu đời.\",\"selection\":\"Tuyển chọn những loại nguyên liệu tươi ngon nhất trong ngày, kết hợp cùng các loại rượu vang\\/spirit thượng hạng.\",\"storage\":\"Bảo quản ở nhiệt độ tiêu chuẩn để đảm bảo sự cân bằng hoàn hảo về cấu trúc và hương vị.\",\"prep\":\"Thực hiện kỹ thuật chiết xuất chậm để lấy trọn vẹn tinh chất tự nhiên của nguyên liệu.\",\"cooking_art\":\"Sử dụng kỹ thuật pha chế hiện đại (Mixology) giúp hương vị hòa quyện đa tầng mượt mà.\",\"presentation\":\"Phục vụ trong ly pha lê sang trọng, điểm xuyết bằng một nhánh thảo mộc tươi để đánh thức khứu giác.\",\"origin_img\":\"gold_rush_origin_1782720351886.png\",\"selection_img\":\"gold_rush_selection_1782720363687.png\",\"prep_img\":\"gold_rush_prep_1782720378446.png\",\"cooking_art_img\":\"gold_rush_mix_1782720414326.png\",\"presentation_img\":\"gold_rush_presentation_1782720428215.png\",\"certificate_img\":\"gold_rush_cert_1782720444757.png\"}', '', NULL, ''),
(22, 4, 'Zen Garden Elixir', 180000.00, '1781148509_6a2a2b5da1be3.png', 'Thức uống Zen Garden Elixir là sự kết hợp hài hòa, gói gọn tinh thần thanh bình của một khu vườn trà Nhật Bản. Loại cocktail này làm nổi bật hương vị đất, đậm đà của matcha kết hợp với vị ngọt dịu của vải và vị chua thanh của yuzu. Đây là lựa chọn sảng khoái dành cho những ai tìm kiếm trải nghiệm độc đáo và thư thái.', 1, 4, 1, 0, '', NULL, 'Một thức uống thanh lọc tâm hồn. Trà xanh sương mù kết hợp cùng chanh yuzu Nhật Bản và hương sả, mang lại cảm giác bình yên như đang dạo bước trong một khu vườn thiền.', '{\"origin\":\"Lấy cảm hứng từ những khu vườn trái cây nhiệt đới tươi mát và nghệ thuật pha chế thủ công lâu đời.\",\"selection\":\"Tuyển chọn những loại nguyên liệu tươi ngon nhất trong ngày, kết hợp cùng các loại rượu vang\\/spirit thượng hạng.\",\"storage\":\"Bảo quản ở nhiệt độ tiêu chuẩn để đảm bảo sự cân bằng hoàn hảo về cấu trúc và hương vị.\",\"prep\":\"Thực hiện kỹ thuật chiết xuất chậm để lấy trọn vẹn tinh chất tự nhiên của nguyên liệu.\",\"cooking_art\":\"Sử dụng kỹ thuật pha chế hiện đại (Mixology) giúp hương vị hòa quyện đa tầng mượt mà.\",\"presentation\":\"Phục vụ trong ly pha lê sang trọng, điểm xuyết bằng một nhánh thảo mộc tươi để đánh thức khứu giác.\",\"origin_img\":\"1783331642_6a4b7b3a67710.webp\",\"storage_img\":\"1783331574_6a4b7af61478b.jpg\",\"presentation_img\":\"1783331516_6a4b7abc32fcf.png\"}', '', NULL, ''),
(23, 1, 'Bánh Cua', 399000.00, '1782874308_6a4480c443bfb.jpg', '**Bánh Cua (Crab Cakes)** là món khai vị kinh điển của ẩm thực ven biển Bắc Mỹ, nổi bật với phần thịt cua xanh Đại Tây Dương được giữ nguyên từng thớ để tôn vinh vị ngọt tự nhiên của hải sản. Kết hợp cùng bột panko, thảo mộc tươi và các loại gia vị được cân bằng tinh tế, bánh được áp chảo đến khi lớp vỏ ngoài vàng giòn trong khi phần nhân bên trong vẫn mềm, mọng và đậm đà. Khi thưởng thức cùng sốt aioli hoặc tartar, rau mầm và một lát chanh vàng, Crab Cakes mang đến sự hài hòa giữa hương vị thanh lịch và kết cấu hấp dẫn, trở thành món khai vị được yêu thích trong nhiều nhà hàng Fine Dining.', 1, 0, 1, 0, '', NULL, '', '{\"origin\":\"Cua xanh Đại Tây Dương được tuyển chọn từ những vùng biển trù phú dọc bờ Đông Hoa Kỳ, nơi dòng hải lưu trong lành và hệ sinh thái ven biển tạo nên môi trường lý tưởng cho loài cua này phát triển. Sau khi được đánh bắt theo phương pháp bền vững, cua được vận chuyển trong điều kiện kiểm soát nghiêm ngặt nhằm giữ trọn độ tươi và vị ngọt tự nhiên. Phần thịt cua trắng mềm, thanh khiết mang hương vị đặc trưng của đại dương, là nguyên liệu được nhiều nhà hàng Fine Dining trên thế giới tin dùng.\",\"selection\":\"Mỗi mẻ cua đều trải qua quy trình tuyển chọn khắt khe để đảm bảo chất lượng đồng nhất. Chúng tôi chỉ sử dụng phần thịt trắng nguyên khối với kết cấu săn chắc, không lẫn vụn vỏ hay tạp chất. Từng thớ thịt được kiểm tra kỹ lưỡng về màu sắc, hương thơm và độ tươi nhằm đáp ứng những tiêu chuẩn cao nhất trước khi bước vào căn bếp.\",\"storage\":\"Ngay sau khi được tách khỏi vỏ, thịt cua được làm lạnh nhanh và bảo quản trong chuỗi lạnh liên tục nhằm duy trì kết cấu mềm mại cũng như hương vị nguyên bản. Mỗi lô nguyên liệu đều được kiểm soát nghiêm ngặt về thời gian lưu trữ và điều kiện bảo quản, giúp đảm bảo chất lượng tối ưu khi đến tay đội ngũ đầu bếp.\",\"prep\":\"Quá trình sơ chế được thực hiện hoàn toàn thủ công bởi đội ngũ đầu bếp giàu kinh nghiệm. Từng phần thịt cua được nhẹ nhàng tách và làm sạch để giữ nguyên những thớ thịt tự nhiên quý giá. Mỗi mẻ nguyên liệu đều được kiểm tra lần cuối nhằm loại bỏ hoàn toàn những mảnh vỏ nhỏ, mang đến sự tinh tế và an toàn tuyệt đối cho thực khách.\",\"cooking_art\":\"Đội ngũ đầu bếp kết hợp thịt cua với các nguyên liệu được chọn lọc kỹ lưỡng như bột panko, thảo mộc tươi và gia vị cao cấp nhằm tôn lên vị ngọt tự nhiên của hải sản thay vì che lấp nó. Những chiếc Crab Cakes được tạo hình bằng tay, áp chảo đến khi lớp vỏ bên ngoài vàng giòn, trong khi phần nhân vẫn mềm, mọng và giữ trọn hương vị đặc trưng của cua xanh Đại Tây Dương.\",\"presentation\":\"Mỗi phần Crab Cakes được trình bày như một tác phẩm nghệ thuật, nơi sắc vàng óng của bánh hòa quyện cùng màu xanh tươi của rau mầm và điểm nhấn từ các loại thảo mộc hoặc hoa ăn được. Sự kết hợp giữa kết cấu, màu sắc và khoảng trống trên đĩa tạo nên một tổng thể thanh lịch, mang đến trải nghiệm thị giác tinh tế trước khi thực khách thưởng thức hương vị.\",\"origin_img\":\"1782906758_6a44ff86e9391.jpg\",\"selection_img\":\"1782906758_6a44ff86e997a.jpg\",\"storage_img\":\"1782906758_6a44ff86e9cb0.jpg\",\"prep_img\":\"1782906758_6a44ff86e9e97.jpg\",\"cooking_art_img\":\"1782906758_6a44ff86ea0d6.jpg\",\"presentation_img\":\"1782906758_6a44ff86ea2a2.jpg\",\"certificate_img\":\"1782906758_6a44ff86ea470.jpg\"}', '', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `food_recipes`
--

DROP TABLE IF EXISTS `food_recipes`;
CREATE TABLE `food_recipes` (
  `id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(10,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_recipes`
--

INSERT INTO `food_recipes` (`id`, `food_id`, `ingredient_id`, `quantity_required`, `unit`) VALUES
(38, 3, 17, 250.000, 'gram'),
(39, 3, 12, 1.000, 'gram'),
(40, 11, 1, 1.000, 'cái'),
(41, 11, 2, 35.000, 'gram'),
(42, 11, 21, 1.000, 'gram'),
(43, 11, 12, 1.000, 'gram'),
(44, 6, 19, 200.000, 'gram'),
(45, 6, 15, 50.000, 'gram'),
(46, 6, 20, 30.000, 'gram'),
(53, 13, 29, 200.000, 'gram'),
(54, 13, 30, 0.400, 'lít'),
(55, 14, 31, 400.000, 'gram'),
(56, 14, 16, 200.000, 'gram'),
(57, 14, 33, 20.000, 'gram'),
(59, 11, 2, 35.000, 'gram'),
(60, 11, 21, 1.000, 'gram'),
(61, 11, 12, 1.000, 'gram'),
(62, 7, 38, 150.000, 'gram'),
(63, 7, 39, 0.200, 'c├íi'),
(64, 7, 40, 15.000, 'gram'),
(65, 7, 21, 2.000, 'gram'),
(66, 5, 41, 120.000, 'gram'),
(67, 5, 5, 80.000, 'gram'),
(68, 5, 42, 0.100, 'l├¡t'),
(69, 16, 35, 1.000, 'Vi├¬n'),
(70, 16, 23, 20.000, 'gram'),
(71, 16, 22, 20.000, 'gram'),
(72, 16, 15, 150.000, 'gram'),
(79, 16, 15, 150.000, 'gram'),
(80, 16, 35, 1.000, 'Viên'),
(81, 16, 23, 20.000, 'gram'),
(82, 16, 22, 20.000, 'gram'),
(83, 7, 40, 15.000, 'gram'),
(84, 7, 39, 0.200, 'cái'),
(85, 7, 21, 2.000, 'gram'),
(86, 7, 38, 150.000, 'gram'),
(130, 20, 55, 20.000, 'ml'),
(131, 20, 54, 45.000, 'ml'),
(132, 20, 53, 15.000, 'ml'),
(133, 20, 52, 45.000, 'ml'),
(134, 20, 56, 15.000, 'ml'),
(146, 12, 33, 10.000, 'gram'),
(147, 12, 14, 200.000, 'gram'),
(156, 18, 64, 2.000, 'ml'),
(157, 18, 39, 30.000, 'ml'),
(158, 18, 62, 20.000, 'ml'),
(159, 18, 61, 25.000, 'ml'),
(160, 18, 63, 15.000, 'ml'),
(167, 22, 87, 7.000, 'gram'),
(168, 22, 89, 25.000, 'ml'),
(169, 22, 88, 45.000, 'ml'),
(172, 17, 14, 350.000, 'gram'),
(173, 17, 34, 200.000, 'gram'),
(181, 1, 2, 15.000, 'gram'),
(182, 1, 14, 450.000, 'gram'),
(183, 1, 12, 3.000, 'gram'),
(184, 1, 3, 10.000, 'gram'),
(185, 15, 2, 10.000, 'gram'),
(186, 15, 36, 40.000, 'gram'),
(187, 15, 37, 7.000, 'gram'),
(192, 23, 2, 10.000, 'gram'),
(193, 23, 99, 0.500, 'ml'),
(194, 23, 98, 140.000, 'gram'),
(195, 23, 11, 3.000, 'gram'),
(196, 21, 85, 50.000, 'gram'),
(197, 21, 84, 150.000, 'ml'),
(198, 21, 86, 1.000, 'lá'),
(199, 19, 57, 40.000, 'ml'),
(200, 19, 59, 2.000, 'ml'),
(201, 19, 58, 10.000, 'ml');

-- --------------------------------------------------------

--
-- Table structure for table `food_toppings`
--

DROP TABLE IF EXISTS `food_toppings`;
CREATE TABLE `food_toppings` (
  `id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `topping_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_toppings`
--

INSERT INTO `food_toppings` (`id`, `food_id`, `topping_id`) VALUES
(117, 1, 19),
(118, 1, 20),
(119, 1, 21),
(120, 1, 13),
(121, 1, 10),
(122, 1, 12),
(123, 1, 11),
(124, 1, 5),
(125, 1, 4),
(126, 1, 3),
(127, 1, 1),
(128, 1, 2),
(130, 3, 19),
(131, 3, 20),
(132, 3, 21),
(133, 3, 13),
(134, 3, 10),
(135, 3, 12),
(136, 3, 11),
(137, 5, 14),
(138, 5, 16),
(139, 5, 17),
(140, 5, 18),
(141, 5, 15),
(166, 12, 19),
(167, 12, 20),
(168, 12, 21),
(169, 12, 13),
(170, 12, 10),
(171, 12, 12),
(172, 12, 11),
(173, 12, 5),
(174, 12, 4),
(175, 12, 3),
(176, 12, 1),
(177, 12, 2),
(208, 13, 5),
(209, 13, 4),
(210, 13, 3),
(211, 13, 1),
(212, 13, 2),
(248, 14, 5),
(249, 14, 4),
(250, 14, 3),
(251, 14, 1),
(252, 14, 2),
(259, 15, 18),
(268, 22, 8),
(269, 22, 7),
(270, 22, 6),
(271, 22, 9),
(280, 20, 8),
(281, 20, 7),
(282, 20, 6),
(283, 20, 9),
(308, 18, 8),
(309, 18, 7),
(310, 18, 6),
(311, 18, 9),
(317, 7, 19),
(318, 21, 8),
(319, 21, 7),
(320, 21, 6),
(321, 21, 9),
(322, 19, 8),
(323, 19, 7),
(324, 19, 6),
(325, 19, 9);

-- --------------------------------------------------------

--
-- Table structure for table `footer_links`
--

DROP TABLE IF EXISTS `footer_links`;
CREATE TABLE `footer_links` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `priority` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `footer_links`
--

INSERT INTO `footer_links` (`id`, `title`, `url`, `priority`) VALUES
(1, 'Trang chủ', '/', 1),
(2, 'Thực đơn', '/menu.php', 2),
(3, 'Đặt bàn', '/booking_service.php?type=table', 3),
(4, 'Liên hệ', '/contact.php', 4);

-- --------------------------------------------------------

--
-- Table structure for table `footer_settings`
--

DROP TABLE IF EXISTS `footer_settings`;
CREATE TABLE `footer_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `footer_settings`
--

INSERT INTO `footer_settings` (`setting_key`, `setting_value`) VALUES
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('email', 'contact@restaurantly.com'),
('facebook_url', '#'),
('footer_bg_color', '#1f6f65'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_logo', ''),
('footer_text_color', '#ffffff'),
('google_map_iframe', ''),
('instagram_url', '#'),
('opening_hours', '08:00 AM - 10:00 PM'),
('phone', '0901 234 567'),
('restaurant_name', 'Restaurantly'),
('show_map', '1'),
('show_newsletter', '0'),
('show_social', '0'),
('tiktok_url', '#'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', ''),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', 'https://www.google.com/maps/place//@10.9235938,106.8296376,15z?entry=ttu&g_ep=EgoyMDI2MDYwMS4wIKXMDSoASAFQAw%3D%3D'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('address', '123 ─É╞░ß╗¥ng ABC, Quß║¡n 1, TP. HCM'),
('copyright_text', '┬⌐ 2026 Restaurantly. All Rights Reserved.'),
('email', 'contact@restaurantly.com'),
('facebook_url', '#'),
('footer_bg_color', '#1f6f65'),
('footer_description', 'Trß║úi nghiß╗çm ß║⌐m thß╗▒c ─æß║│ng cß║Ñp giß╗»a l├▓ng th├ánh phß╗æ.'),
('footer_logo', ''),
('footer_text_color', '#ffffff'),
('google_map_iframe', ''),
('instagram_url', '#'),
('opening_hours', '08:00 AM - 10:00 PM'),
('phone', '0901 234 567'),
('restaurant_name', 'Restaurantly'),
('show_map', '1'),
('show_newsletter', '0'),
('show_social', '0'),
('tiktok_url', '#'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trß║úi nghiß╗çm ß║⌐m thß╗▒c ─æß║│ng cß║Ñp giß╗»a l├▓ng th├ánh phß╗æ.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 ─É╞░ß╗¥ng ABC, Quß║¡n 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '┬⌐ 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', ''),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trß║úi nghiß╗çm ß║⌐m thß╗▒c ─æß║│ng cß║Ñp giß╗»a l├▓ng th├ánh phß╗æ.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 ─É╞░ß╗¥ng ABC, Quß║¡n 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '┬⌐ 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', 'https://www.google.com/maps/place//@10.9235938,106.8296376,15z?entry=ttu&g_ep=EgoyMDI2MDYwMS4wIKXMDSoASAFQAw%3D%3D'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trß║úi nghiß╗çm ß║⌐m thß╗▒c ─æß║│ng cß║Ñp giß╗»a l├▓ng th├ánh phß╗æ.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 ─É╞░ß╗¥ng ABC, Quß║¡n 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '┬⌐ 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('email', 'contact@restaurantly.com'),
('facebook_url', '#'),
('footer_bg_color', '#1f6f65'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_logo', ''),
('footer_text_color', '#ffffff'),
('google_map_iframe', ''),
('instagram_url', '#'),
('opening_hours', '08:00 AM - 10:00 PM'),
('phone', '0901 234 567'),
('restaurant_name', 'Restaurantly'),
('show_map', '1'),
('show_newsletter', '0'),
('show_social', '0'),
('tiktok_url', '#'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', ''),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', 'https://www.google.com/maps/place//@10.9235938,106.8296376,15z?entry=ttu&g_ep=EgoyMDI2MDYwMS4wIKXMDSoASAFQAw%3D%3D'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#1f5f07'),
('footer_text_color', '#ffffff'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#262629'),
('footer_text_color', '#ffffff'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#ffffff'),
('footer_text_color', '#000000'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#fdfcfc'),
('footer_text_color', '#643a3a'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#fdfcfc'),
('footer_text_color', '#643a3a'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#b6aaaa'),
('footer_text_color', '#643a3a'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#fffafa'),
('footer_text_color', '#643a3a'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#e1e0e0'),
('footer_text_color', '#3f2727'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#e1e0e0'),
('footer_text_color', '#3f2727'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '09:00 AM - 11:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#e1e0e0'),
('footer_text_color', '#3f2727'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '09:00 AM - 11:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('footer_bg_image', 'fbg_59e6958e22df5fbe.webp'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#e1e0e0'),
('footer_text_color', '#3f2727'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '09:00 AM - 11:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('footer_bg_image', 'fbg_b07858cb5904cc48.webp'),
('restaurant_name', 'Restaurantly'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_bg_color', '#e1e0e0'),
('footer_text_color', '#3f2727'),
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '09:00 AM - 11:00 PM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('zalo_url', ''),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.95857849559!2d106.8142937871582!3d10.945261700000007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dfd88b179e41%3A0xc97a12c798cae2d3!2zQkJRIMOUbmcgTeG6rXAgQmnDqm4gSG_DoCAtIOuaseuztOynkSDruYTsl5TtmLjslYQ!5e0!3m2!1svi!2s!4v1780668084617!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
('footer_logo', 'flogo_4c664961d3d27433.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `galleries`
--

DROP TABLE IF EXISTS `galleries`;
CREATE TABLE `galleries` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galleries`
--

INSERT INTO `galleries` (`id`, `image_url`, `title`, `sort_order`, `is_active`, `created_at`) VALUES
(5, '1780712860_anh3.jpg', '', 0, 1, '2026-06-06 02:27:40'),
(6, '1780712868_anh4.jpg', '', 0, 1, '2026-06-06 02:27:48'),
(7, '1780712877_anh5.jpg', '', 0, 1, '2026-06-06 02:27:57'),
(8, '1780712889_anh2.jpg', '', 0, 1, '2026-06-06 02:28:09'),
(9, '1780712925_anh6.jpg', '', 0, 1, '2026-06-06 02:28:45'),
(10, '1780712950_anh6.jpg', '', 0, 1, '2026-06-06 02:29:10'),
(11, '1780712953_anh7.jpg', '', 0, 1, '2026-06-06 02:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit_name` varchar(50) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `revenue` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_stock` float DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `storage_zone` varchar(50) DEFAULT 'Kho khô',
  `storage_temperature` varchar(100) DEFAULT NULL,
  `allergens` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `category`, `unit_name`, `cost_price`, `supplier_id`, `entry_date`, `expiry_date`, `revenue`, `updated_at`, `min_stock`, `is_active`, `storage_zone`, `storage_temperature`, `allergens`) VALUES
(2, 'Bơ lạt', 'Thực phẩm', 'kg', 150000.00, NULL, '2026-06-03', '2026-09-01', 0.00, '2026-07-11 11:51:23', 2, 1, 'Kho Tổng', NULL, ''),
(3, 'Tỏi', 'Thực phẩm', 'kg', 0.00, NULL, '2026-06-03', NULL, 0.00, '2026-07-11 11:51:23', 1, 1, 'Kho Tổng', NULL, ''),
(5, 'Tôm sú', 'Hải sản có vỏ', 'kg', 250000.00, 3, '2026-06-03', '2026-11-30', 0.00, '2026-06-29 12:40:47', 5, 1, 'Kho Tổng', '', ''),
(7, 'Hành lá', 'Thực phẩm', 'kg', 30000.00, NULL, '2026-06-03', NULL, 0.00, '2026-07-11 11:51:23', 1, 1, 'Kho Tổng', NULL, ''),
(8, 'Dầu ăn', 'Thực phẩm', 'lít', 45000.00, NULL, '2026-06-03', '2027-06-03', 0.00, '2026-06-03 12:24:03', 5, 1, 'Kho Tổng', NULL, ''),
(9, 'Sườn heo', '', 'kg', 180000.00, NULL, '2026-06-03', '2026-11-30', 0.00, '2026-06-16 04:11:20', 10, 1, 'Kho Tổng', '', ''),
(10, 'Tiêu xanh', 'Thực phẩm', 'kg', 120000.00, NULL, '2026-06-03', NULL, 0.00, '2026-07-11 11:51:23', 0.5, 1, 'Kho Tổng', NULL, ''),
(11, 'Hành tím', 'Thực phẩm', 'kg', 45000.00, NULL, '2026-06-03', '2026-07-17', 0.00, '2026-07-01 03:20:53', 2, 1, 'Kho Tổng', NULL, ''),
(12, 'Tiêu đen', 'Thực phẩm', 'kg', 180000.00, NULL, '2026-06-03', '2027-06-03', 0.00, '2026-06-03 12:24:03', 0.5, 1, 'Kho Tổng', NULL, ''),
(13, 'Sốt BBQ', 'Thực phẩm', 'lít', 90000.00, NULL, '2026-06-03', '2026-11-30', 0.00, '2026-06-03 12:24:03', 2, 1, 'Kho Tổng', NULL, ''),
(14, 'Thăn bò Wagyu A5 (Ozaki)', 'Thực phẩm', 'kg', 350000.00, 2, '2026-06-03', '2026-09-15', 0.00, '2026-06-29 12:40:47', 10, 1, 'Kho Tổng', NULL, ''),
(16, 'Khoai tây', 'Thực phẩm', 'kg', 25000.00, NULL, '2026-06-03', '2026-07-17', 0.00, '2026-07-01 03:20:53', 5, 1, 'Kho Tổng', NULL, ''),
(17, 'Cá hồi phi lê Nhật Bản (Nissui)', 'Hải sản', 'kg', 450000.00, 3, '2026-06-03', '2026-07-20', 0.00, '2026-06-29 12:40:47', 5, 1, 'Kho Tổng', '', ''),
(18, 'Măng tây', 'Thực phẩm', 'kg', 120000.00, NULL, '2026-06-03', NULL, 0.00, '2026-07-11 11:51:23', 2, 1, 'Kho Tổng', NULL, ''),
(19, 'Phi lê cá ngừ', 'Hải sản', 'kg', 175000.00, 3, NULL, '2026-07-22', 0.00, '2026-06-29 12:40:47', 0, 1, 'Kho khô', NULL, ''),
(20, 'Bắp ngọt', 'Rau củ', 'kg', 10000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 0, 1, 'Kho khô', '', ''),
(21, 'Muối', 'Gia vị', 'kg', 7500.00, NULL, NULL, '2027-06-04', 0.00, '2026-06-04 02:46:28', 0, 1, 'Kho khô', NULL, ''),
(22, 'Xà lách xanh', 'Rau củ', 'kg', 20555.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 0, 1, 'Kho khô', NULL, ''),
(25, 'Mật ong', 'Gia vị', 'lít', 100000.00, NULL, NULL, '2027-06-01', 0.00, '2026-07-11 11:51:23', 0, 1, 'Kho khô', NULL, ''),
(26, 'Nước tương', 'Gia vị', 'lít', 25000.00, NULL, NULL, '2027-06-04', 0.00, '2026-06-04 02:46:28', 0, 1, 'Kho khô', NULL, ''),
(28, 'Đùi heo muối Iberico (Rougié)', 'Thực phẩm', 'kg', 250000.00, 4, '2026-06-06', '2026-12-03', 0.00, '2026-06-29 12:40:47', 5, 1, 'Kho Tổng', NULL, ''),
(29, 'Ức vịt Pháp / Gan ngỗng (Rougié)', 'Thực phẩm', 'kg', 180000.00, 4, '2026-06-06', NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho Tổng', NULL, ''),
(30, 'Sốt cherry cô đặc', 'Gia vị', 'lít', 150000.00, NULL, '2026-06-06', NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho Tổng', NULL, ''),
(31, 'Sườn cừu', 'Thực phẩm', 'kg', 450000.00, NULL, '2026-06-06', NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho Tổng', NULL, ''),
(33, 'Hương thảo', 'Rau củ', 'kg', 120000.00, NULL, '2026-06-06', '2026-07-16', 0.00, '2026-07-01 03:20:53', 5, 1, 'Kho Tổng', NULL, ''),
(34, 'Đậu phộng', 'hạt', 'kg', 20000.00, NULL, NULL, '2026-08-08', 0.00, '2026-07-01 03:34:19', 1, 1, 'Kho khô', '25', ''),
(35, 'Phô mai Burrata', 'Thực phẩm', 'viên', 50000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(36, 'Cồi sò điệp', 'Hải sản có vỏ', 'kg', 800000.00, 3, NULL, NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho đông', 'Đông (-18°C)', ''),
(37, 'Trứng cá hồi', 'Hải sản', 'kg', 1500000.00, 3, NULL, NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(38, 'Nấm mỡ tươi (Button Mushroom)', 'Rau củ', 'kg', 120000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho mát', 'Mát (4-8°C)', ''),
(39, 'Kem tươi', 'Thực phẩm', 'lít', 150000.00, NULL, NULL, '2026-07-22', 0.00, '2026-07-01 03:20:53', 5, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(40, 'Hành tây', 'Rau củ', 'kg', 25000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho mát', 'Mát (8-15°C)', ''),
(41, 'Mỳ Ý', 'Thực phẩm', 'kg', 80000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho khô', 'Khô (Nhiệt độ phòng)', ''),
(42, 'Sốt cà chua nền', 'Gia vị', 'lít', 100000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 5, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(44, 'Nấm Truffle đen', 'Thực phẩm', 'kg', 5000000.00, NULL, NULL, '2026-09-11', 0.00, '2026-06-13 12:56:10', 0.5, 1, 'Kho khô', NULL, ''),
(45, 'Sốt Phô mai cay', 'Gia vị', 'lít', 200000.00, NULL, NULL, '2026-09-11', 0.00, '2026-06-13 12:56:10', 2, 1, 'Kho khô', NULL, ''),
(46, 'Phô mai Mozzarella', 'Thực phẩm', 'kg', 250000.00, NULL, NULL, '2026-09-11', 0.00, '2026-06-13 12:56:10', 5, 1, 'Kho khô', NULL, ''),
(47, 'Xúc xích Đức', 'Thực phẩm', 'kg', 300000.00, NULL, NULL, '2026-09-11', 0.00, '2026-06-13 12:56:10', 3, 1, 'Kho khô', NULL, ''),
(48, 'Dăm bông Prosciutto', 'Thực phẩm', 'kg', 800000.00, NULL, NULL, '2026-09-11', 0.00, '2026-06-13 12:56:10', 1, 1, 'Kho khô', NULL, ''),
(49, 'Trứng cá hồi Ikura', 'Hải sản', 'kg', 2000000.00, 3, NULL, '2026-09-11', 0.00, '2026-06-29 12:40:47', 0.5, 1, 'Kho khô', NULL, ''),
(50, 'Trứng gà tươi', 'Thực phẩm', 'quả', 3500.00, NULL, NULL, '2026-08-01', 0.00, '2026-06-15 02:33:13', 50, 1, 'Kho khô', NULL, ''),
(51, 'Khoai tây nghiền', 'Thực phẩm', 'kg', 80000.00, NULL, NULL, '2026-09-11', 0.00, '2026-06-13 12:56:10', 5, 1, 'Kho khô', NULL, ''),
(52, 'Rượu Tequila', 'Đồ uống', 'chai', 500000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(53, 'Rượu mùi cam (Cointreau / Triple Sec)', 'Đồ uống', 'chai', 450000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(54, 'Nước ép lựu nguyên chất', 'Đồ uống', 'lít', 120000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 5, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(55, 'Nước cốt chanh tươi', 'Gia vị', 'lít', 50000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(56, 'Siro hoa hồng (Rose syrup)', 'Gia vị', 'chai', 150000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(57, 'Rượu Bourbon hoặc Rye Whiskey', 'Đồ uống', 'chai', 800000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(58, 'Siro đường nâu', 'Gia vị', 'chai', 100000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(59, 'Rượu đắng Angostura Bitters', 'Gia vị', 'chai', 650000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 1, 1, 'Kho khô', NULL, ''),
(60, 'Vỏ cam vàng', 'Rau củ', 'gram', 50000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 100, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(61, 'Rượu Vodka', 'Đồ uống', 'chai', 400000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(62, 'Rượu mùi cà phê (Kahlúa)', 'Đồ uống', 'chai', 450000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(63, 'Siro chocolate đậm đặc', 'Gia vị', 'chai', 120000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(64, 'Dầu nấm Truffle', 'Gia vị', 'chai', 1500000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 1, 1, 'Kho khô', NULL, ''),
(65, 'Lát cam/chanh sấy khô', 'Thực phẩm', 'lát', 5000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 50, 1, 'Kho khô', NULL, ''),
(66, 'Hạt lựu tươi', 'Rau củ', 'gram', 150000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 100, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(67, 'Cánh hoa hồng hữu cơ', 'Rau củ', 'gram', 200000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 50, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(68, 'Dưa leo cuộn dải mỏng', 'Rau củ', 'gram', 30000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 500, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(69, 'Quả cherry ngâm rượu', 'Thực phẩm', 'hộp', 350000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(70, 'Lá bạc hà tươi', 'Rau củ', 'gram', 80000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 100, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(71, 'Thanh quế khô', 'Gia vị', 'gram', 150000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 200, 1, 'Kho khô', NULL, ''),
(72, 'Hoa hồi', 'Gia vị', 'gram', 180000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 200, 1, 'Kho khô', NULL, ''),
(73, 'Viên Truffle Chocolate', 'Thực phẩm', 'viên', 30000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 20, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(74, 'Sốt Chocolate đậm đặc (Fudge)', 'Gia vị', 'lít', 180000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(75, 'Kẹo bông gòn', 'Thực phẩm', 'gói', 25000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 10, 1, 'Kho khô', NULL, ''),
(76, 'Cốm Chocolate / Cốm màu', 'Thực phẩm', 'gram', 120000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 200, 1, 'Kho khô', NULL, ''),
(77, 'Bột Ca cao', 'Gia vị', 'gram', 150000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 200, 1, 'Kho khô', NULL, ''),
(78, 'Bột quế', 'Gia vị', 'gram', 180000.00, NULL, NULL, NULL, 0.00, '2026-07-11 11:51:23', 200, 1, 'Kho khô', NULL, ''),
(79, 'Kẹo Marshmallow', 'Thực phẩm', 'gói', 65000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 5, 1, 'Kho khô', NULL, ''),
(80, 'Nhũ vàng thực phẩm', 'Gia vị', 'hộp', 450000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 1, 1, 'Kho khô', NULL, ''),
(81, 'Muối hồng Himalaya', 'Gia vị', 'kg', 85000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(82, 'Đường tinh thể màu hồng', 'Gia vị', 'kg', 120000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho khô', NULL, ''),
(83, 'Lớp bọt Foam kem mặn', 'Thực phẩm', 'lít', 95000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 2, 1, 'Kho mát', 'Mát (2-4°C)', ''),
(84, 'Vang đỏ Cabernet Sauvignon', 'Đồ uống', 'ml', 500.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 0, 1, 'Kho khô', NULL, ''),
(85, 'Trái cây nhiệt đới mix', 'Rau củ', 'gram', 100.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 0, 1, 'Kho khô', NULL, ''),
(86, 'Vảy vàng 24k', 'Gia vị', 'lá', 50000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 0, 1, 'Kho khô', NULL, ''),
(87, 'Bột Matcha', 'Đồ uống', 'gram', 1000.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 0, 1, 'Kho khô', NULL, ''),
(88, 'Nước ép vải', 'Đồ uống', 'ml', 200.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 0, 1, 'Kho khô', NULL, ''),
(89, 'Nước ép Yuzu', 'Đồ uống', 'ml', 300.00, NULL, NULL, '2027-06-18', 0.00, '2026-06-18 12:25:33', 0, 1, 'Kho khô', NULL, ''),
(90, 'Chén sứ trắng cao cấp', 'Vật tư', '', 25000.00, NULL, NULL, '2031-06-18', 0.00, '2026-06-18 12:25:33', 20, 1, 'Kho Vật Tư', '', ''),
(91, 'Đĩa sứ trắng viền vàng', 'Vật tư', 'Cái', 45000.00, NULL, NULL, '2031-06-18', 0.00, '2026-06-18 12:25:33', 100, 1, 'Kho Vật Tư', NULL, ''),
(92, 'Muỗng inox 304', 'Vật tư', 'Cái', 15000.00, NULL, NULL, '2031-06-18', 0.00, '2026-06-18 12:25:33', 200, 1, 'Kho Vật Tư', NULL, ''),
(93, 'Dao cắt bít tết inox', 'Vật tư', '', 35000.00, NULL, NULL, '2031-06-18', 0.00, '2026-06-18 12:25:33', 20, 1, 'Kho Vật Tư', '', ''),
(94, 'Nĩa inox 304', 'Vật tư', 'Cái', 15000.00, NULL, NULL, '2031-06-18', 0.00, '2026-06-18 12:25:33', 200, 1, 'Kho Vật Tư', NULL, ''),
(95, 'Ly rượu vang pha lê', 'Vật tư', 'Cái', 120000.00, NULL, NULL, '2031-06-18', 0.00, '2026-06-18 12:25:33', 50, 1, 'Kho Vật Tư', NULL, ''),
(96, 'Khăn ướt nhà hàng', 'Vật tư', 'Cái', 2000.00, NULL, NULL, '2031-06-18', 0.00, '2026-06-18 12:25:33', 1000, 1, 'Kho Vật Tư', NULL, ''),
(97, 'Tôm đỏ (Carabineros)', 'Hải sản có vỏ', 'kg', 2500000.00, 5, '2026-07-01', '2027-01-01', 0.00, '2026-07-01 02:35:29', 5, 1, 'Kho đông lạnh', '-18°C', 'Động vật giáp xác'),
(98, 'Cua xanh Đại Tây Dương', 'Hải sản có vỏ', 'kg', 1800000.00, 5, '2026-07-01', '2027-01-01', 0.00, '2026-07-01 02:35:29', 5, 1, 'Kho đông lạnh', '-18°C', 'Động vật giáp xác'),
(99, 'Chanh vàng (Eureka)', 'Quả', 'kg', 150000.00, 6, '2026-07-01', '2027-01-01', 0.00, '2026-07-01 02:35:29', 5, 1, 'Kho mát', '4°C', '');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_audits`
--

DROP TABLE IF EXISTS `inventory_audits`;
CREATE TABLE `inventory_audits` (
  `id` int(11) NOT NULL,
  `audit_date` datetime DEFAULT current_timestamp(),
  `performed_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_audits`
--

INSERT INTO `inventory_audits` (`id`, `audit_date`, `performed_by`, `notes`) VALUES
(1, '2026-04-29 20:12:52', 'Admin', ''),
(2, '2026-04-29 20:13:38', 'Admin', ''),
(3, '2026-05-05 11:37:52', 'Admin', ''),
(4, '2026-05-07 09:38:23', 'Admin', ''),
(5, '2026-05-07 09:50:36', 'Admin', '');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_audit_details`
--

DROP TABLE IF EXISTS `inventory_audit_details`;
CREATE TABLE `inventory_audit_details` (
  `id` int(11) NOT NULL,
  `audit_id` int(11) DEFAULT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `system_qty` float DEFAULT NULL,
  `physical_qty` float DEFAULT NULL,
  `variance` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_audit_details`
--

INSERT INTO `inventory_audit_details` (`id`, `audit_id`, `ingredient_id`, `system_qty`, `physical_qty`, `variance`) VALUES
(1, 1, 1, 200, 200, 0),
(2, 1, 2, 29, 29, 0),
(3, 2, 1, 192, 192, 0),
(4, 2, 2, 29, 29, 0),
(5, 3, 1, 187, 187, 0),
(6, 3, 3, 60, 60, 0),
(7, 4, 4, 1, 1, 0),
(8, 4, 1, 170, 170, 0),
(9, 4, 3, 70, 70, 0),
(10, 5, 4, 0, 0, 0),
(11, 5, 1, 10, 10, 0),
(12, 5, 3, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_batches`
--

DROP TABLE IF EXISTS `inventory_batches`;
CREATE TABLE `inventory_batches` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `batch_code` varchar(50) DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
  `expiry_date` date DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `receiving_temperature` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `supplier_batch_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_batches`
--

INSERT INTO `inventory_batches` (`id`, `ingredient_id`, `warehouse_id`, `batch_code`, `quantity`, `expiry_date`, `cost_price`, `receiving_temperature`, `created_at`, `supplier_batch_number`) VALUES
(19, 1, 1, 'BATCH-260603', 0.000, '2026-06-06', 5000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(20, 2, 1, 'BATCH-260603', 4.000, '2026-09-01', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(21, 3, 1, 'BATCH-260603', 0.000, '2026-07-17', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(22, 4, 1, 'BATCH-260603', 0.000, '2026-08-02', 200000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(23, 5, 1, 'BATCH-260603', 15.000, '2026-11-30', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(24, 6, 1, 'BATCH-260603', 0.000, '2026-06-17', 40000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(25, 7, 1, 'BATCH-260603', 0.000, '2026-06-10', 30000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(26, 8, 1, 'BATCH-260603', 0.000, '2027-06-03', 45000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(27, 9, 1, 'BATCH-260603', 20.000, '2026-11-30', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(28, 10, 1, 'BATCH-260603', 0.000, '2026-06-17', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(29, 11, 1, 'BATCH-260603', 0.000, '2026-07-17', 45000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(30, 12, 1, 'BATCH-260603', 0.000, '2027-06-03', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(31, 13, 1, 'BATCH-260603', 5.000, '2026-11-30', 90000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(32, 14, 1, 'BATCH-260603', 20.000, '2026-11-30', 350000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(33, 15, 1, 'BATCH-260603', 5.000, '2026-06-10', 60000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(34, 16, 1, 'BATCH-260603', 0.000, '2026-07-17', 25000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(35, 17, 1, 'BATCH-260603', 10.000, '2026-11-30', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(36, 18, 1, 'BATCH-260603', 0.000, '2026-06-10', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-03 12:22:47', 'BATCH-260603'),
(37, 19, 1, 'PO-20260604094628', 0.000, '2026-06-14', 350000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(38, 20, 1, 'PO-20260604094628', 0.000, '2026-06-19', 20000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(39, 21, 1, 'PO-20260604094628', 0.000, '2027-06-04', 15000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(40, 22, 1, 'PO-20260604094628', 0.000, '2026-06-11', 30000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(41, 23, 1, 'PO-20260604094628', 0.000, '2026-06-11', 40000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(42, 24, 1, 'PO-20260604094628', 0.000, '2026-06-09', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(43, 25, 1, 'PO-20260604094628', 5.000, '2028-06-03', 200000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(44, 26, 1, 'PO-20260604094628', 0.000, '2027-06-04', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-04 02:46:28', 'PO-20260604094628'),
(45, 27, 1, 'PO-20260606-VIP', 20.000, '2026-07-20', 350000.00, 'Nhiệt độ phòng (20°C)', '2026-06-06 02:58:13', 'PO-20260606-VIP'),
(46, 28, 1, 'PO-20260606-VIP', 10.000, '2026-12-03', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-06 02:58:13', 'PO-20260606-VIP'),
(47, 29, 1, 'PO-20260606-VIP', 0.000, '2026-07-20', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-06 02:58:13', 'PO-20260606-VIP'),
(48, 30, 1, 'PO-20260606-VIP', 0.000, '2027-06-06', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-06 02:58:13', 'PO-20260606-VIP'),
(49, 31, 1, 'PO-20260606-VIP', 0.000, '2026-07-20', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-06 02:58:13', 'PO-20260606-VIP'),
(50, 32, 1, 'PO-20260606-VIP', 4.000, '2026-09-04', 80000.00, 'Nhiệt độ phòng (20°C)', '2026-06-06 02:58:13', 'PO-20260606-VIP'),
(51, 33, 1, 'PO-20260606-VIP', 0.000, '2026-06-20', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-06 02:58:13', 'PO-20260606-VIP'),
(52, 30, 1, 'PO-20260608113916', 0.000, '2026-06-16', 150000.00, '16', '2026-06-08 04:39:30', 'PO-20260608113916'),
(53, 34, 1, 'PO-20260608133108', 0.000, '2026-06-30', 20000.00, '25', '2026-06-08 06:31:22', 'PO-20260608133108'),
(54, 35, 1, 'PO-20260608091200', 0.000, '2026-07-22', 50000.00, '2', '2026-06-08 07:12:00', 'PO-20260608091200'),
(55, 35, 1, 'PO-20260608091519', 0.000, '2026-07-22', 50000.00, '7', '2026-06-08 07:15:19', 'PO-20260608091519'),
(56, 36, 1, 'PO-20260608091519', 0.000, '2026-07-22', 800000.00, '10', '2026-06-08 07:15:19', 'PO-20260608091519'),
(57, 37, 1, 'PO-20260608091519', 0.000, '2026-07-22', 1500000.00, '5', '2026-06-08 07:15:19', 'PO-20260608091519'),
(58, 38, 1, 'PO-20260608091519', 0.000, '2026-07-22', 120000.00, '9', '2026-06-08 07:15:19', 'PO-20260608091519'),
(59, 39, 1, 'PO-20260608091519', 10.000, '2026-07-22', 150000.00, '9', '2026-06-08 07:15:19', 'PO-20260608091519'),
(60, 40, 1, 'PO-20260608091519', 0.000, '2026-07-22', 25000.00, '7', '2026-06-08 07:15:19', 'PO-20260608091519'),
(61, 41, 1, 'PO-20260608091519', 0.000, '2026-07-22', 80000.00, '2', '2026-06-08 07:15:19', 'PO-20260608091519'),
(62, 42, 1, 'PO-20260608091519', 0.000, '2026-07-22', 100000.00, '8', '2026-06-08 07:15:19', 'PO-20260608091519'),
(63, 25, 1, 'PO-20260610110751', 3.000, '2027-06-01', 100000.00, '18', '2026-06-10 04:09:20', 'PO-20260610110751'),
(64, 26, 1, 'PO-20260610110751', 0.000, '2026-09-16', 25000.00, '20', '2026-06-10 04:09:20', 'PO-20260610110751'),
(65, 33, 1, 'PO-20260610110751', 0.000, '2026-06-17', 120000.00, '25', '2026-06-10 04:09:20', 'PO-20260610110751'),
(66, 36, 1, 'PO-20260610110751', 0.000, '2026-06-24', 800000.00, '-5', '2026-06-10 04:09:20', 'PO-20260610110751'),
(67, 37, 1, 'PO-20260610110751', 0.000, '2026-06-14', 1500000.00, '5', '2026-06-10 04:09:20', 'PO-20260610110751'),
(68, 40, 1, 'PO-20260610110751', 0.000, '2026-06-17', 25000.00, '25', '2026-06-10 04:09:20', 'PO-20260610110751'),
(69, 42, 1, 'PO-20260610110751', 0.000, '2026-07-18', 100000.00, '25', '2026-06-10 04:09:20', 'PO-20260610110751'),
(70, 14, 1, 'PO-20260610111208', 5.000, '2027-06-10', 350000.00, '25', '2026-06-10 04:15:59', 'PO-20260610111208'),
(71, 26, 1, 'PO-20260610111832', 0.000, '2027-06-10', 25000.00, '25', '2026-06-10 04:19:56', 'PO-20260610111832'),
(72, 2, 1, 'PO-20260610112206', 0.000, '2026-07-10', 150000.00, '25', '2026-06-10 04:23:37', 'PO-20260610112206'),
(73, 15, 1, 'PO-20260612095443', 10.000, '2026-07-16', 60000.00, '25', '2026-06-12 02:54:55', 'PO-20260612095443'),
(74, 44, 1, 'PO-TOP-1781355370-667', 10.000, '2026-09-11', 5000000.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-667'),
(75, 45, 1, 'PO-TOP-1781355370-478', 0.000, '2026-09-11', 200000.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-478'),
(76, 46, 1, 'PO-TOP-1781355370-704', 0.000, '2026-09-11', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-704'),
(77, 47, 1, 'PO-TOP-1781355370-332', 10.000, '2026-09-11', 300000.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-332'),
(78, 48, 1, 'PO-TOP-1781355370-646', 10.000, '2026-09-11', 800000.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-646'),
(79, 49, 1, 'PO-TOP-1781355370-326', 10.000, '2026-09-11', 2000000.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-326'),
(80, 50, 1, 'PO-TOP-1781355370-975', 10.000, '2026-09-11', 3500.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-975'),
(81, 51, 1, 'PO-TOP-1781355370-255', 10.000, '2026-09-11', 80000.00, 'Nhiệt độ phòng (20°C)', '2026-06-13 12:56:10', 'PO-TOP-1781355370-255'),
(82, 33, 1, 'PO-20260615091614', 0.000, '2026-06-22', 120000.00, '25', '2026-06-15 02:17:12', 'PO-20260615091614'),
(83, 30, 1, 'PO-20260615091733', 0.000, '2026-06-23', 150000.00, '10', '2026-06-15 02:19:13', 'PO-20260615091733'),
(84, 7, 1, 'PO-20260615092148', 0.000, '2026-06-24', 30000.00, '25', '2026-06-15 02:27:49', 'PO-20260615092148'),
(85, 18, 1, 'PO-20260615092148', 0.000, '2026-06-25', 120000.00, '25', '2026-06-15 02:27:49', 'PO-20260615092148'),
(86, 37, 1, 'PO-20260615092148', 0.000, '2026-07-19', 1500000.00, 'Nhiệt độ phòng (20°C)', '2026-06-15 02:27:49', 'PO-20260615092148'),
(87, 17, 1, 'PO-20260615092148', 15.000, '2026-07-20', 450000.00, '20', '2026-06-15 02:27:49', 'PO-20260615092148'),
(88, 19, 1, 'PO-20260615092148', 15.000, '2026-07-22', 175000.00, '18', '2026-06-15 02:27:49', 'PO-20260615092148'),
(89, 22, 1, 'PO-20260615093006', 0.000, '2026-07-19', 25000.00, '25', '2026-06-15 02:30:27', 'PO-20260615093006'),
(90, 50, 1, 'PO-20260615093258', 65.000, '2026-08-01', 3500.00, '25', '2026-06-15 02:33:13', 'PO-20260615093258'),
(91, 90, 5, 'VT-20260616-574', 300.000, '2031-06-18', 25000.00, 'Nhiệt độ phòng (20°C)', '2026-06-16 04:00:43', 'VT-20260616-574'),
(92, 91, 5, 'VT-20260616-169', 500.000, '2031-06-18', 45000.00, 'Nhiệt độ phòng (20°C)', '2026-06-16 04:00:43', 'VT-20260616-169'),
(93, 92, 5, 'VT-20260616-539', 1000.000, '2031-06-18', 15000.00, 'Nhiệt độ phòng (20°C)', '2026-06-16 04:00:43', 'VT-20260616-539'),
(94, 93, 5, 'VT-20260616-732', 500.000, '2031-06-18', 35000.00, 'Nhiệt độ phòng (20°C)', '2026-06-16 04:00:43', 'VT-20260616-732'),
(95, 94, 5, 'VT-20260616-101', 800.000, '2031-06-18', 15000.00, 'Nhiệt độ phòng (20°C)', '2026-06-16 04:00:43', 'VT-20260616-101'),
(96, 95, 5, 'VT-20260616-357', 150.000, '2031-06-18', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-16 04:00:43', 'VT-20260616-357'),
(97, 96, 5, 'VT-20260616-175', 4000.000, '2031-06-18', 2000.00, 'Nhiệt độ phòng (20°C)', '2026-06-16 04:00:43', 'VT-20260616-175'),
(98, 40, 1, 'PO-20260618183134', 0.000, '2026-07-16', 25000.00, '25', '2026-06-18 11:31:57', 'PO-20260618183134'),
(99, 33, 1, 'PO-20260618183134', 5.500, '2026-07-16', 120000.00, '25', '2026-06-18 11:31:57', 'PO-20260618183134'),
(100, 14, 8, 'BATCH-AUTO-20260618-193014-612', 5.000, '2026-11-30', 350000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-612'),
(101, 9, 2, 'BATCH-AUTO-20260618-193014-622', 9.200, '2026-11-30', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-622'),
(102, 9, 8, 'BATCH-AUTO-20260618-193014-580', 6.000, '2026-11-30', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-580'),
(103, 14, 2, 'BATCH-AUTO-20260618-193014-455', 0.000, '2026-11-30', 350000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-455'),
(104, 2, 9, 'BATCH-AUTO-20260618-193014-586', 0.000, '2026-07-10', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-586'),
(105, 12, 9, 'BATCH-AUTO-20260618-193014-243', 1.000, '2027-06-03', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-243'),
(106, 21, 9, 'BATCH-AUTO-20260618-193014-253', 1.000, '2027-06-04', 7500.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-253'),
(107, 26, 9, 'BATCH-AUTO-20260618-193014-108', 2.000, '2027-06-04', 25000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-108'),
(108, 21, 2, 'BATCH-AUTO-20260618-193014-352', 8.998, '2027-06-04', 7500.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-352'),
(109, 2, 2, 'BATCH-AUTO-20260618-193014-912', 0.000, '2026-07-10', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-912'),
(110, 12, 2, 'BATCH-AUTO-20260618-193014-410', 0.982, '2027-06-03', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-410'),
(111, 3, 2, 'BATCH-AUTO-20260618-193014-398', 0.000, '2026-07-17', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-398'),
(112, 2, 6, 'BATCH-AUTO-20260618-193014-264', 0.310, '2026-07-10', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-264'),
(113, 14, 6, 'BATCH-AUTO-20260618-193014-511', 3.350, '2026-11-30', 350000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-511'),
(114, 3, 6, 'BATCH-AUTO-20260618-193014-863', 0.070, '2026-07-17', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-863'),
(115, 13, 2, 'BATCH-AUTO-20260618-193014-781', 2.980, '2026-11-30', 90000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-781'),
(116, 29, 2, 'BATCH-AUTO-20260618-193014-608', 0.000, '2026-07-20', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-608'),
(117, 30, 2, 'BATCH-AUTO-20260618-193014-499', 0.000, '2026-06-23', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-499'),
(118, 31, 2, 'BATCH-AUTO-20260618-193014-558', 0.000, '2026-07-20', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-558'),
(119, 28, 2, 'BATCH-AUTO-20260618-193014-802', 5.960, '2026-12-03', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-802'),
(120, 28, 6, 'BATCH-AUTO-20260618-193014-977', 0.020, '2026-12-03', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-977'),
(121, 9, 6, 'BATCH-AUTO-20260618-193014-318', 0.400, '2026-11-30', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-318'),
(122, 10, 6, 'BATCH-AUTO-20260618-193014-263', 0.010, '2026-06-17', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-263'),
(123, 13, 6, 'BATCH-AUTO-20260618-193014-980', 0.010, '2026-11-30', 90000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-980'),
(124, 17, 2, 'BATCH-AUTO-20260618-193014-816', 4.250, '2026-07-20', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-816'),
(125, 17, 6, 'BATCH-AUTO-20260618-193014-535', 0.500, '2026-07-20', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-535'),
(126, 20, 2, 'BATCH-AUTO-20260618-193014-858', 0.000, '2026-06-19', 10000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-858'),
(127, 34, 2, 'BATCH-AUTO-20260618-193014-194', 0.000, '2026-06-30', 20000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-194'),
(128, 34, 6, 'BATCH-AUTO-20260618-193014-259', 0.600, '2026-06-30', 20000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-259'),
(129, 39, 3, 'BATCH-AUTO-20260618-193014-500', 0.000, '2026-06-25', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-500'),
(130, 25, 2, 'BATCH-AUTO-20260618-193014-686', 2.000, '2028-06-03', 100000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-686'),
(131, 18, 2, 'BATCH-AUTO-20260618-193014-544', 0.000, '2026-06-25', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-544'),
(132, 16, 2, 'BATCH-AUTO-20260618-193014-399', 0.200, '2026-07-17', 25000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-399'),
(133, 11, 2, 'BATCH-AUTO-20260618-193014-381', 0.100, '2026-07-17', 45000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-381'),
(134, 7, 2, 'BATCH-AUTO-20260618-193014-505', 0.000, '2026-06-24', 30000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-505'),
(135, 36, 2, 'BATCH-AUTO-20260618-193014-690', 0.000, '2026-06-24', 800000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-690'),
(136, 8, 2, 'BATCH-AUTO-20260618-193014-143', 30.000, '2027-06-03', 45000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-143'),
(137, 19, 2, 'BATCH-AUTO-20260618-193014-353', 5.000, '2026-07-22', 175000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-353'),
(138, 35, 2, 'BATCH-AUTO-20260618-193014-446', 0.000, '2026-07-22', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-446'),
(139, 20, 9, 'BATCH-AUTO-20260618-193014-201', 0.000, '2026-06-19', 10000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-201'),
(140, 41, 2, 'BATCH-AUTO-20260618-193014-714', 0.000, '2026-07-22', 80000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-714'),
(141, 26, 2, 'BATCH-AUTO-20260618-193014-805', 5.000, '2027-06-04', 25000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-805'),
(142, 37, 2, 'BATCH-AUTO-20260618-193014-654', 0.000, '2026-07-19', 1500000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-654'),
(143, 5, 2, 'BATCH-AUTO-20260618-193014-603', 9.240, '2026-11-30', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-603'),
(144, 20, 6, 'BATCH-AUTO-20260618-193014-562', 0.200, '2026-06-19', 10000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-562'),
(145, 46, 2, 'BATCH-AUTO-20260618-193014-486', 10.000, '2026-09-11', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-486'),
(146, 42, 2, 'BATCH-AUTO-20260618-193014-315', 0.000, '2026-07-22', 100000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-315'),
(147, 45, 2, 'BATCH-AUTO-20260618-193014-658', 10.000, '2026-09-11', 200000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-658'),
(148, 51, 2, 'BATCH-AUTO-20260618-193014-401', 8.000, '2026-09-11', 80000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-401'),
(149, 48, 2, 'BATCH-AUTO-20260618-193014-941', 8.000, '2026-09-11', 800000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-941'),
(150, 22, 2, 'BATCH-AUTO-20260618-193014-581', 0.000, '2026-07-19', 20555.56, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-581'),
(151, 47, 2, 'BATCH-AUTO-20260618-193014-235', 8.000, '2026-09-11', 300000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-235'),
(152, 50, 9, 'BATCH-AUTO-20260618-193014-395', 10.000, '2026-08-01', 3500.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-395'),
(153, 50, 2, 'BATCH-AUTO-20260618-193014-746', 40.000, '2026-08-01', 3500.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-746'),
(154, 49, 2, 'BATCH-AUTO-20260618-193014-216', 7.980, '2026-09-11', 2000000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-216'),
(155, 84, 3, 'BATCH-AUTO-20260618-193014-113', 850.000, '2027-06-18', 500.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-113'),
(156, 87, 3, 'BATCH-AUTO-20260618-193014-360', 990.000, '2027-06-18', 1000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-360'),
(157, 88, 3, 'BATCH-AUTO-20260618-193014-627', 910.000, '2027-06-18', 200.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-627'),
(158, 89, 3, 'BATCH-AUTO-20260618-193014-935', 960.000, '2027-06-18', 300.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-935'),
(159, 87, 6, 'BATCH-AUTO-20260618-193014-156', 10.000, '2027-06-18', 1000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-156'),
(160, 88, 6, 'BATCH-AUTO-20260618-193014-738', 90.000, '2027-06-18', 200.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-738'),
(161, 89, 6, 'BATCH-AUTO-20260618-193014-828', 40.000, '2027-06-18', 300.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-828'),
(162, 36, 6, 'BATCH-AUTO-20260618-193014-965', 0.040, '2026-06-24', 800000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-965'),
(163, 37, 6, 'BATCH-AUTO-20260618-193014-146', 0.010, '2026-07-19', 1500000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-146'),
(164, 49, 6, 'BATCH-AUTO-20260618-193014-440', 0.020, '2026-09-11', 2000000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-440'),
(165, 85, 6, 'BATCH-AUTO-20260618-193014-540', 50.000, '2027-06-18', 100.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-540'),
(166, 86, 6, 'BATCH-AUTO-20260618-193014-904', 1.000, '2027-06-18', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-904'),
(167, 84, 6, 'BATCH-AUTO-20260618-193014-481', 150.000, '2027-06-18', 500.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-481'),
(168, 52, 3, 'BATCH-AUTO-20260618-193014-704', 7.000, '2027-06-18', 500000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-704'),
(169, 53, 3, 'BATCH-AUTO-20260618-193014-266', 37.000, '2027-06-18', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-266'),
(170, 54, 3, 'BATCH-AUTO-20260618-193014-475', 54.970, '2027-06-18', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-475'),
(171, 55, 3, 'BATCH-AUTO-20260618-193014-904', 39.980, '2027-06-18', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-904'),
(172, 56, 3, 'BATCH-AUTO-20260618-193014-470', 37.000, '2027-06-18', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-470'),
(173, 57, 3, 'BATCH-AUTO-20260618-193014-225', 52.000, '2027-06-18', 800000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-225'),
(174, 58, 3, 'BATCH-AUTO-20260618-193014-933', 52.000, '2027-06-18', 100000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-933'),
(175, 59, 3, 'BATCH-AUTO-20260618-193014-844', 51.000, '2027-06-18', 650000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-844'),
(176, 60, 3, 'BATCH-AUTO-20260618-193014-194', 150.000, '2027-06-18', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-194'),
(177, 61, 3, 'BATCH-AUTO-20260618-193014-652', 52.000, '2027-06-18', 400000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-652'),
(178, 62, 3, 'BATCH-AUTO-20260618-193014-558', 52.000, '2027-06-18', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-558'),
(179, 63, 3, 'BATCH-AUTO-20260618-193014-905', 52.000, '2027-06-18', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-905'),
(180, 64, 3, 'BATCH-AUTO-20260618-193014-413', 51.000, '2027-06-18', 1500000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-413'),
(181, 65, 1, 'BATCH-AUTO-20260618-193014-311', 100.000, '2027-06-18', 5000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-311'),
(182, 66, 1, 'BATCH-AUTO-20260618-193014-485', 0.000, '2027-06-18', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-485'),
(183, 67, 1, 'BATCH-AUTO-20260618-193014-416', 0.000, '2027-06-18', 200000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-416'),
(184, 68, 1, 'BATCH-AUTO-20260618-193014-361', 0.000, '2027-06-18', 30000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-361'),
(185, 69, 1, 'BATCH-AUTO-20260618-193014-309', 52.000, '2027-06-18', 350000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-309'),
(186, 70, 1, 'BATCH-AUTO-20260618-193014-622', 0.000, '2027-06-18', 80000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-622'),
(187, 71, 1, 'BATCH-AUTO-20260618-193014-634', 0.000, '2027-06-18', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-634'),
(188, 72, 1, 'BATCH-AUTO-20260618-193014-728', 0.000, '2027-06-18', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-728'),
(189, 73, 1, 'BATCH-AUTO-20260618-193014-762', 70.000, '2027-06-18', 30000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-762'),
(190, 74, 1, 'BATCH-AUTO-20260618-193014-333', 52.000, '2027-06-18', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-333'),
(191, 75, 1, 'BATCH-AUTO-20260618-193014-269', 60.000, '2027-06-18', 25000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-269'),
(192, 76, 1, 'BATCH-AUTO-20260618-193014-177', 0.000, '2027-06-18', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-177'),
(193, 77, 1, 'BATCH-AUTO-20260618-193014-302', 0.000, '2027-06-18', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-302'),
(194, 78, 1, 'BATCH-AUTO-20260618-193014-549', 0.000, '2027-06-18', 180000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-549'),
(195, 79, 1, 'BATCH-AUTO-20260618-193014-979', 55.000, '2027-06-18', 65000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-979'),
(196, 80, 1, 'BATCH-AUTO-20260618-193014-515', 51.000, '2027-06-18', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-515'),
(197, 81, 1, 'BATCH-AUTO-20260618-193014-244', 52.000, '2027-06-18', 85000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-244'),
(198, 82, 1, 'BATCH-AUTO-20260618-193014-552', 52.000, '2027-06-18', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-552'),
(199, 83, 1, 'BATCH-AUTO-20260618-193014-637', 52.000, '2027-06-18', 95000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-637'),
(200, 85, 3, 'BATCH-AUTO-20260618-193014-719', 950.000, '2027-06-18', 100.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-719'),
(201, 86, 3, 'BATCH-AUTO-20260618-193014-525', 999.000, '2027-06-18', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-525'),
(202, 22, 6, 'BATCH-AUTO-20260618-193014-556', 0.040, '2026-07-19', 20555.56, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-556'),
(203, 35, 6, 'BATCH-AUTO-20260618-193014-113', 2.000, '2026-07-22', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-113'),
(204, 55, 2, 'BATCH-AUTO-20260618-193014-885', 12.000, '2027-06-18', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-885'),
(205, 42, 6, 'BATCH-AUTO-20260618-193014-282', 0.100, '2026-07-22', 100000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-282'),
(206, 41, 6, 'BATCH-AUTO-20260618-193014-452', 0.120, '2026-07-22', 80000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-452'),
(207, 5, 6, 'BATCH-AUTO-20260618-193014-492', 0.080, '2026-11-30', 250000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-492'),
(208, 55, 6, 'BATCH-AUTO-20260618-193014-463', 0.020, '2027-06-18', 50000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-463'),
(209, 56, 6, 'BATCH-AUTO-20260618-193014-702', 15.000, '2027-06-18', 150000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-702'),
(210, 52, 6, 'BATCH-AUTO-20260618-193014-806', 45.000, '2027-06-18', 500000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-806'),
(211, 53, 6, 'BATCH-AUTO-20260618-193014-468', 15.000, '2027-06-18', 450000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-468'),
(212, 54, 6, 'BATCH-AUTO-20260618-193014-832', 0.050, '2027-06-18', 120000.00, 'Nhiệt độ phòng (20°C)', '2026-06-18 12:30:14', 'BATCH-AUTO-20260618-193014-832'),
(213, 10, 1, 'PO-20260623143530', 0.000, '2026-10-01', 120000.00, '25', '2026-06-23 07:36:11', 'PO-20260623143530'),
(214, 30, 1, 'PO-20260623143530', 0.000, '2026-09-10', 150000.00, '25', '2026-06-23 07:36:11', 'PO-20260623143530'),
(215, 33, 1, 'PO-20260623143530', 8.000, '2026-08-01', 120000.00, '25', '2026-06-23 07:36:11', 'PO-20260623143530'),
(216, 38, 1, 'PO-20260623143530', 0.000, '2026-08-17', 120000.00, '25', '2026-06-23 07:36:11', 'PO-20260623143530'),
(217, 10, 1, 'PO-20260623160814', 0.000, '2027-01-01', 120000.00, '25', '2026-06-23 09:08:35', 'PO-20260623160814'),
(218, 33, 1, 'PO-20260623160814', 8.000, '2026-09-01', 120000.00, '25', '2026-06-23 09:08:35', 'PO-20260623160814'),
(219, 10, 1, 'PO-20260623161327', 0.000, '2026-07-24', 120000.00, '25', '2026-06-23 09:13:37', 'PO-20260623161327'),
(220, 14, 1, 'PO-20260623163036', 10.000, '2026-09-15', 350000.00, '25', '2026-06-23 09:30:49', 'PO-20260623163036'),
(221, 7, 1, 'PO-20260626185159', 0.000, '2026-08-01', 30000.00, '25', '2026-06-26 11:52:34', 'PO-20260626185159'),
(222, 18, 1, 'PO-20260626185159', 0.000, '2026-07-11', 120000.00, '25', '2026-06-26 11:52:34', 'PO-20260626185159'),
(223, 20, 1, 'PO-20260626185159', 0.000, '2026-07-18', 10000.00, '25', '2026-06-26 11:52:34', 'PO-20260626185159'),
(224, 30, 1, 'PO-20260626185159', 0.000, '2026-09-01', 150000.00, '25', '2026-06-26 11:52:34', 'PO-20260626185159'),
(225, 36, 1, 'PO-20260626185159', 0.000, '2026-08-01', 800000.00, '25', '2026-06-26 11:52:34', 'PO-20260626185159'),
(226, 3, 1, 'PO-20260630094338', 0.000, '2026-08-25', 0.00, '25', '2026-06-30 02:46:32', '1'),
(227, 97, 1, 'BATCH-20260701-97', 20.000, '2027-01-01', 2500000.00, '-18°C', '2026-07-01 02:35:29', 'SUP-2125'),
(228, 98, 1, 'BATCH-20260701-98', 20.000, '2027-01-01', 1800000.00, '-18°C', '2026-07-01 02:35:29', 'SUP-1537'),
(229, 99, 1, 'BATCH-20260701-99', 20.000, '2027-01-01', 150000.00, '4°C', '2026-07-01 02:35:29', 'SUP-9618'),
(230, 2, 1, 'PO-20260701101221', 2.000, '2026-09-01', 150000.00, '25', '2026-07-01 03:15:30', '1'),
(231, 3, 1, 'PO-20260701101221', 0.000, '2026-09-14', 0.00, '25', '2026-07-01 03:15:30', '2'),
(232, 11, 1, 'PO-20260701101221', 0.000, '2026-08-08', 45000.00, '25', '2026-07-01 03:15:30', '3'),
(233, 16, 1, 'PO-20260701101221', 0.000, '2026-08-12', 25000.00, '25', '2026-07-01 03:15:30', '4'),
(234, 20, 1, 'PO-20260701101221', 0.000, '2026-08-20', 10000.00, '25', '2026-07-01 03:15:30', '5'),
(235, 22, 1, 'PO-20260701101221', 0.000, '2026-08-20', 20555.00, '25', '2026-07-01 03:15:30', '6'),
(236, 33, 1, 'PO-20260701101221', 8.000, '2026-09-03', 120000.00, '25', '2026-07-01 03:15:30', '7'),
(237, 34, 1, 'PO-20260701101221', 2.000, '2026-08-19', 20000.00, '25', '2026-07-01 03:15:30', '8'),
(238, 35, 1, 'PO-20260701101221', 0.000, '2026-09-03', 50000.00, '25', '2026-07-01 03:15:30', '9'),
(239, 38, 1, 'PO-20260701101221', 0.000, '2026-08-19', 120000.00, '25', '2026-07-01 03:15:30', '10'),
(240, 39, 1, 'PO-20260701101221', 8.000, '2026-08-25', 150000.00, '25', '2026-07-01 03:15:30', '11'),
(241, 40, 1, 'PO-20260701101221', 0.000, '2026-08-12', 25000.00, '25', '2026-07-01 03:15:30', '12'),
(242, 41, 1, 'PO-20260701101221', 0.000, '2026-08-15', 80000.00, '25', '2026-07-01 03:15:30', '13'),
(243, 42, 1, 'PO-20260701101221', 0.000, '2026-08-18', 100000.00, '25', '2026-07-01 03:15:30', '14'),
(244, 29, 1, 'PO-20260701101552', 0.000, '2026-07-25', 180000.00, '25', '2026-07-01 03:17:00', '20'),
(245, 31, 1, 'PO-20260701101552', 0.000, '2026-08-06', 450000.00, '25', '2026-07-01 03:17:00', '21'),
(246, 36, 1, 'PO-20260701101552', 0.000, '2026-07-25', 800000.00, '25', '2026-07-01 03:17:00', '22'),
(247, 37, 1, 'PO-20260701101552', 0.000, '2026-08-15', 1500000.00, '18', '2026-07-01 03:17:00', '23'),
(248, 34, 1, 'PO-20260701102556', 4.000, '2026-10-01', 20000.00, '25', '2026-07-01 03:26:24', '100'),
(249, 34, 1, 'PO-20260701103356', 2.000, '2026-08-08', 20000.00, '25', '2026-07-01 03:34:19', '20'),
(250, 2, 1, 'PO-20260711183910', 8.000, '2026-12-10', 150000.00, '25', '2026-07-11 11:39:59', '20'),
(251, 2, 1, 'PO-20260711184718', 8.000, '2027-09-23', 150000.00, '25', '2026-07-11 11:47:52', '20');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `default_warehouse_id` int(11) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`, `default_warehouse_id`) VALUES
(1, 'Thịt', 4),
(2, 'Rau củ', 4),
(3, 'Gia vị', 2),
(4, 'Đồ uống', 3),
(5, 'rau', 4),
(6, 'hạt', 2),
(7, 'Hải sản', 2),
(8, 'Vật tư', 5),
(9, 'Hải sản có vỏ', 2),
(10, 'Quả', 2);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

DROP TABLE IF EXISTS `inventory_history`;
CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `type` enum('import','export','loss','audit_adjust_up','audit_adjust_down') NOT NULL,
  `quantity` decimal(10,4) DEFAULT 0.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `performed_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_history`
--

INSERT INTO `inventory_history` (`id`, `ingredient_id`, `warehouse_id`, `type`, `quantity`, `created_at`, `performed_by`) VALUES
(110, 1, 1, 'import', 200.0000, '2026-06-03 12:22:47', 'admin'),
(111, 2, 1, 'import', 10.0000, '2026-06-03 12:22:47', 'admin'),
(112, 3, 1, 'import', 5.0000, '2026-06-03 12:22:47', 'admin'),
(113, 4, 1, 'import', 10.0000, '2026-06-03 12:22:47', 'admin'),
(114, 5, 1, 'import', 15.0000, '2026-06-03 12:22:47', 'admin'),
(115, 6, 1, 'import', 5.0000, '2026-06-03 12:22:47', 'admin'),
(116, 7, 1, 'import', 2.0000, '2026-06-03 12:22:47', 'admin'),
(117, 8, 1, 'import', 20.0000, '2026-06-03 12:22:47', 'admin'),
(118, 9, 1, 'import', 20.0000, '2026-06-03 12:22:47', 'admin'),
(119, 10, 1, 'import', 2.0000, '2026-06-03 12:22:47', 'admin'),
(120, 11, 1, 'import', 5.0000, '2026-06-03 12:22:47', 'admin'),
(121, 12, 1, 'import', 2.0000, '2026-06-03 12:22:47', 'admin'),
(122, 13, 1, 'import', 5.0000, '2026-06-03 12:22:47', 'admin'),
(123, 14, 1, 'import', 20.0000, '2026-06-03 12:22:47', 'admin'),
(124, 15, 1, 'import', 5.0000, '2026-06-03 12:22:47', 'admin'),
(125, 16, 1, 'import', 10.0000, '2026-06-03 12:22:47', 'admin'),
(126, 17, 1, 'import', 10.0000, '2026-06-03 12:22:47', 'admin'),
(127, 18, 1, 'import', 5.0000, '2026-06-03 12:22:47', 'admin'),
(128, 14, 1, 'export', 5.0000, '2026-06-04 02:12:38', 'Admin (Chuyển đi #33)'),
(129, 14, 8, 'import', 5.0000, '2026-06-04 02:12:38', 'Admin (Nhận từ #33)'),
(130, 9, 1, 'export', 10.0000, '2026-06-04 02:12:41', 'Admin (Chuyển đi #32)'),
(131, 9, 2, 'import', 10.0000, '2026-06-04 02:12:41', 'Admin (Nhận từ #32)'),
(132, 9, 1, 'export', 3.0000, '2026-06-04 02:14:04', 'Admin (Chuyển đi #36)'),
(133, 9, 8, 'import', 3.0000, '2026-06-04 02:14:04', 'Admin (Nhận từ #36)'),
(134, 9, 1, 'export', 3.0000, '2026-06-04 02:14:07', 'Admin (Chuyển đi #35)'),
(135, 9, 8, 'import', 3.0000, '2026-06-04 02:14:07', 'Admin (Nhận từ #35)'),
(136, 14, 1, 'export', 5.0000, '2026-06-04 02:14:11', 'Admin (Chuyển đi #34)'),
(137, 14, 2, 'import', 5.0000, '2026-06-04 02:14:11', 'Admin (Nhận từ #34)'),
(138, 2, 1, 'export', 10.0000, '2026-06-04 02:34:18', 'Admin (Chuyển đi #37)'),
(139, 2, 9, 'import', 10.0000, '2026-06-04 02:34:18', 'Admin (Nhận từ #37)'),
(140, 10, 1, 'export', 2.0000, '2026-06-04 02:34:19', 'Admin (Chuyển đi #40)'),
(141, 10, 9, 'import', 2.0000, '2026-06-04 02:34:19', 'Admin (Nhận từ #40)'),
(142, 4, 1, 'export', 10.0000, '2026-06-04 02:34:21', 'Admin (Chuyển đi #39)'),
(143, 4, 9, 'import', 10.0000, '2026-06-04 02:34:21', 'Admin (Nhận từ #39)'),
(144, 8, 1, 'export', 20.0000, '2026-06-04 02:34:28', 'Admin (Chuyển đi #38)'),
(145, 8, 9, 'import', 20.0000, '2026-06-04 02:34:28', 'Admin (Nhận từ #38)'),
(146, 12, 1, 'export', 2.0000, '2026-06-04 02:39:38', 'Admin (Chuyển đi #41)'),
(147, 12, 9, 'import', 2.0000, '2026-06-04 02:39:38', 'Admin (Nhận từ #41)'),
(148, 19, 1, 'import', 10.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(149, 20, 1, 'import', 10.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(150, 21, 1, 'import', 10.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(151, 22, 1, 'import', 8.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(152, 23, 1, 'import', 8.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(153, 24, 1, 'import', 15.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(154, 25, 1, 'import', 5.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(155, 26, 1, 'import', 5.0000, '2026-06-04 02:46:28', 'admin (Auto Script)'),
(156, 24, 1, 'export', 7.0000, '2026-06-04 02:48:21', 'Admin (Chuyển đi #42)'),
(157, 24, 3, 'import', 7.0000, '2026-06-04 02:48:21', 'Admin (Nhận từ #42)'),
(158, 24, 1, 'export', 8.0000, '2026-06-04 02:48:24', 'Admin (Chuyển đi #43)'),
(159, 24, 8, 'import', 8.0000, '2026-06-04 02:48:24', 'Admin (Nhận từ #43)'),
(160, 21, 1, 'export', 10.0000, '2026-06-04 02:49:14', 'Admin (Chuyển đi #44)'),
(161, 21, 9, 'import', 10.0000, '2026-06-04 02:49:14', 'Admin (Nhận từ #44)'),
(162, 26, 1, 'export', 5.0000, '2026-06-04 02:49:41', 'Admin (Chuyển đi #45)'),
(163, 26, 9, 'import', 5.0000, '2026-06-04 02:49:41', 'Admin (Nhận từ #45)'),
(164, 21, 9, 'export', 2.0000, '2026-06-04 03:01:20', 'Admin (Chuyển đi #46)'),
(165, 21, 2, 'import', 2.0000, '2026-06-04 03:01:20', 'Admin (Nhận từ #46)'),
(168, 1, 1, 'export', 200.0000, '2026-06-04 03:02:01', 'Admin (Chuyển đi #47)'),
(169, 1, 2, 'import', 200.0000, '2026-06-04 03:02:01', 'Admin (Nhận từ #47)'),
(172, 2, 9, 'export', 2.0000, '2026-06-04 03:02:27', 'Admin (Chuyển đi #48)'),
(173, 2, 2, 'import', 2.0000, '2026-06-04 03:02:27', 'Admin (Nhận từ #48)'),
(177, 12, 9, 'export', 1.0000, '2026-06-04 03:02:57', 'Admin (Chuyển đi #49)'),
(178, 12, 2, 'import', 1.0000, '2026-06-04 03:02:57', 'Admin (Nhận từ #49)'),
(186, 3, 1, 'export', 3.0000, '2026-06-04 03:03:51', 'Admin (Chuyển đi #50)'),
(187, 3, 2, 'import', 3.0000, '2026-06-04 03:03:51', 'Admin (Nhận từ #50)'),
(196, 15, 1, 'export', 3.0000, '2026-06-04 03:04:12', 'Admin (Chuyển đi #51)'),
(197, 15, 2, 'import', 3.0000, '2026-06-04 03:04:12', 'Admin (Nhận từ #51)'),
(198, 21, 2, 'export', 0.0000, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(199, 1, 2, 'export', 1.0000, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(200, 2, 2, 'export', 0.0400, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(201, 12, 2, 'export', 0.0000, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(202, 14, 2, 'export', 0.4500, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(203, 12, 2, 'export', 0.0000, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(204, 2, 2, 'export', 0.0200, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(205, 3, 2, 'export', 0.0100, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(206, 15, 2, 'export', 0.0300, '2026-06-04 03:04:17', 'POS (Xác nhận #19)'),
(207, 21, 2, 'export', 0.0000, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(208, 1, 2, 'export', 1.0000, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(209, 2, 2, 'export', 0.0400, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(210, 12, 2, 'export', 0.0000, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(211, 14, 2, 'export', 0.4500, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(212, 12, 2, 'export', 0.0000, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(213, 2, 2, 'export', 0.0200, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(214, 3, 2, 'export', 0.0100, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(215, 15, 2, 'export', 0.0300, '2026-06-04 03:07:36', 'POS (Xác nhận #20)'),
(216, 14, 2, 'export', 0.4500, '2026-06-04 03:10:27', 'POS (Xác nhận #21)'),
(217, 12, 2, 'export', 0.0000, '2026-06-04 03:10:27', 'POS (Xác nhận #21)'),
(218, 2, 2, 'export', 0.0200, '2026-06-04 03:10:27', 'POS (Xác nhận #21)'),
(219, 3, 2, 'export', 0.0100, '2026-06-04 03:10:27', 'POS (Xác nhận #21)'),
(220, 15, 2, 'export', 0.0300, '2026-06-04 03:10:27', 'POS (Xác nhận #21)'),
(221, 14, 2, 'export', 0.4500, '2026-06-04 03:10:55', 'POS (Xác nhận #22)'),
(222, 12, 2, 'export', 0.0000, '2026-06-04 03:10:55', 'POS (Xác nhận #22)'),
(223, 2, 2, 'export', 0.0200, '2026-06-04 03:10:55', 'POS (Xác nhận #22)'),
(224, 3, 2, 'export', 0.0100, '2026-06-04 03:10:55', 'POS (Xác nhận #22)'),
(225, 15, 2, 'export', 0.0300, '2026-06-04 03:10:55', 'POS (Xác nhận #22)'),
(226, 14, 2, 'export', 0.4500, '2026-06-05 01:21:54', 'POS (Xác nhận #23)'),
(227, 12, 2, 'export', 0.0000, '2026-06-05 01:21:54', 'POS (Xác nhận #23)'),
(228, 2, 2, 'export', 0.0200, '2026-06-05 01:21:54', 'POS (Xác nhận #23)'),
(229, 3, 2, 'export', 0.0100, '2026-06-05 01:21:54', 'POS (Xác nhận #23)'),
(230, 15, 2, 'export', 0.0300, '2026-06-05 01:21:54', 'POS (Xác nhận #23)'),
(231, 14, 2, 'export', 0.4500, '2026-06-05 01:34:31', 'POS (Xác nhận #24)'),
(232, 12, 2, 'export', 0.0000, '2026-06-05 01:34:31', 'POS (Xác nhận #24)'),
(233, 2, 2, 'export', 0.0200, '2026-06-05 01:34:31', 'POS (Xác nhận #24)'),
(234, 3, 2, 'export', 0.0100, '2026-06-05 01:34:31', 'POS (Xác nhận #24)'),
(235, 15, 2, 'export', 0.0300, '2026-06-05 01:34:31', 'POS (Xác nhận #24)'),
(236, 27, 1, 'import', 20.0000, '2026-06-06 02:58:13', 'admin'),
(237, 28, 1, 'import', 10.0000, '2026-06-06 02:58:13', 'admin'),
(238, 29, 1, 'import', 18.0000, '2026-06-06 02:58:13', 'admin'),
(239, 30, 1, 'import', 5.0000, '2026-06-06 02:58:13', 'admin'),
(240, 31, 1, 'import', 20.0000, '2026-06-06 02:58:13', 'admin'),
(241, 32, 1, 'import', 4.0000, '2026-06-06 02:58:13', 'admin'),
(242, 33, 3, 'import', 2.0000, '2026-06-06 02:58:13', 'admin'),
(243, 13, 1, 'export', 3.0000, '2026-06-06 03:05:27', 'Admin (Chuyển đi #52)'),
(244, 13, 2, 'import', 3.0000, '2026-06-06 03:05:27', 'Admin (Nhận từ #52)'),
(245, 29, 1, 'export', 8.0000, '2026-06-06 03:05:27', 'Admin (Chuyển đi #52)'),
(246, 29, 2, 'import', 8.0000, '2026-06-06 03:05:27', 'Admin (Nhận từ #52)'),
(247, 30, 1, 'export', 3.0000, '2026-06-06 03:05:27', 'Admin (Chuyển đi #52)'),
(248, 30, 2, 'import', 3.0000, '2026-06-06 03:05:27', 'Admin (Nhận từ #52)'),
(249, 31, 1, 'export', 10.0000, '2026-06-06 03:05:27', 'Admin (Chuyển đi #52)'),
(250, 31, 2, 'import', 10.0000, '2026-06-06 03:05:27', 'Admin (Nhận từ #52)'),
(251, 33, 3, 'export', 1.0000, '2026-06-06 03:05:27', 'Admin (Chuyển đi #52)'),
(252, 33, 3, 'import', 1.0000, '2026-06-06 03:05:27', 'Admin (Nhận từ #52)'),
(253, 28, 1, 'export', 6.0000, '2026-06-06 03:08:11', 'Admin (Chuyển đi #53)'),
(254, 28, 2, 'import', 6.0000, '2026-06-06 03:08:11', 'Admin (Nhận từ #53)'),
(255, 21, 2, 'export', 0.0000, '2026-06-06 07:48:07', 'POS (Xác nhận #47)'),
(256, 1, 2, 'export', 1.0000, '2026-06-06 07:48:07', 'POS (Xác nhận #47)'),
(257, 2, 2, 'export', 0.0400, '2026-06-06 07:48:07', 'POS (Xác nhận #47)'),
(258, 12, 2, 'export', 0.0000, '2026-06-06 07:48:07', 'POS (Xác nhận #47)'),
(259, 21, 2, 'export', 0.0000, '2026-06-06 07:54:29', 'POS (Xác nhận #48)'),
(260, 1, 2, 'export', 1.0000, '2026-06-06 07:54:29', 'POS (Xác nhận #48)'),
(261, 2, 2, 'export', 0.0400, '2026-06-06 07:54:29', 'POS (Xác nhận #48)'),
(262, 12, 2, 'export', 0.0000, '2026-06-06 07:54:29', 'POS (Xác nhận #48)'),
(263, 33, 3, 'export', 0.0100, '2026-06-06 07:54:29', 'POS (Vét kho dự phòng #48)'),
(264, 14, 2, 'export', 0.2000, '2026-06-06 07:54:29', 'POS (Xác nhận #48)'),
(265, 28, 2, 'export', 0.0200, '2026-06-06 07:54:29', 'POS (Xác nhận #48)'),
(267, 10, 9, 'export', 1.0000, '2026-06-06 08:00:22', 'Admin (Chuyển đi #54)'),
(268, 10, 2, 'import', 1.0000, '2026-06-06 08:00:22', 'Admin (Nhận từ #54)'),
(269, 9, 2, 'export', 0.4000, '2026-06-06 08:00:31', 'POS (Xác nhận #50)'),
(270, 10, 2, 'export', 0.0100, '2026-06-06 08:00:31', 'POS (Xác nhận #50)'),
(271, 13, 2, 'export', 0.0100, '2026-06-06 08:00:31', 'POS (Xác nhận #50)'),
(274, 14, 2, 'export', 0.4500, '2026-06-08 03:10:55', 'POS (Xác nhận #78)'),
(275, 12, 2, 'export', 0.0000, '2026-06-08 03:10:55', 'POS (Xác nhận #78)'),
(276, 2, 2, 'export', 0.0200, '2026-06-08 03:10:55', 'POS (Xác nhận #78)'),
(277, 3, 2, 'export', 0.0100, '2026-06-08 03:10:55', 'POS (Xác nhận #78)'),
(278, 15, 2, 'export', 0.0300, '2026-06-08 03:10:55', 'POS (Xác nhận #78)'),
(279, 14, 2, 'export', 0.4500, '2026-06-08 03:29:48', 'POS (Xác nhận #80)'),
(280, 12, 2, 'export', 0.0000, '2026-06-08 03:29:48', 'POS (Xác nhận #80)'),
(281, 2, 2, 'export', 0.0200, '2026-06-08 03:29:48', 'POS (Xác nhận #80)'),
(282, 3, 2, 'export', 0.0100, '2026-06-08 03:29:48', 'POS (Xác nhận #80)'),
(283, 15, 2, 'export', 0.0300, '2026-06-08 03:29:48', 'POS (Xác nhận #80)'),
(300, 17, 1, 'export', 5.0000, '2026-06-08 03:34:21', 'Admin (Chuyển đi #55)'),
(301, 17, 2, 'import', 5.0000, '2026-06-08 03:34:21', 'Admin (Nhận từ #55)'),
(302, 33, 3, 'export', 0.0100, '2026-06-08 03:34:24', 'POS (Vét kho dự phòng #81)'),
(303, 14, 2, 'export', 0.2000, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(304, 28, 2, 'export', 0.0200, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(305, 14, 2, 'export', 0.4500, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(306, 12, 2, 'export', 0.0000, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(307, 2, 2, 'export', 0.0200, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(308, 3, 2, 'export', 0.0100, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(309, 15, 2, 'export', 0.0300, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(310, 17, 2, 'export', 0.2500, '2026-06-08 03:34:24', 'POS (Xác nhận #81)'),
(311, 12, 2, 'export', 0.0000, '2026-06-08 03:34:25', 'POS (Xác nhận #81)'),
(312, 30, 1, 'import', 2.5000, '2026-06-08 04:39:30', 'Admin (Nhận hàng PO #3)'),
(313, 20, 1, 'export', 5.0000, '2026-06-08 04:39:55', 'Admin (Chuyển đi #56)'),
(314, 20, 2, 'import', 5.0000, '2026-06-08 04:39:55', 'Admin (Nhận từ #56)'),
(315, 34, 1, 'import', 3.0000, '2026-06-08 06:31:22', 'Admin (Nhận hàng PO #4)'),
(316, 34, 1, 'export', 2.0000, '2026-06-08 06:32:04', 'Admin (Chuyển đi #57)'),
(317, 34, 2, 'import', 2.0000, '2026-06-08 06:32:04', 'Admin (Nhận từ #57)'),
(318, 14, 2, 'export', 0.4500, '2026-06-08 06:42:15', 'POS (Xác nhận #82)'),
(319, 12, 2, 'export', 0.0000, '2026-06-08 06:42:15', 'POS (Xác nhận #82)'),
(320, 2, 2, 'export', 0.0200, '2026-06-08 06:42:15', 'POS (Xác nhận #82)'),
(321, 3, 2, 'export', 0.0100, '2026-06-08 06:42:15', 'POS (Xác nhận #82)'),
(322, 15, 2, 'export', 0.0300, '2026-06-08 06:42:15', 'POS (Xác nhận #82)'),
(323, 14, 2, 'import', 0.4500, '2026-06-08 06:42:55', 'Admin (Hoàn kho #82)'),
(324, 12, 2, 'import', 0.0000, '2026-06-08 06:42:55', 'Admin (Hoàn kho #82)'),
(325, 2, 2, 'import', 0.0200, '2026-06-08 06:42:55', 'Admin (Hoàn kho #82)'),
(326, 3, 2, 'import', 0.0100, '2026-06-08 06:42:55', 'Admin (Hoàn kho #82)'),
(327, 15, 2, 'import', 0.0300, '2026-06-08 06:42:55', 'Admin (Hoàn kho #82)'),
(328, 34, 2, 'export', 0.2000, '2026-06-08 06:43:51', 'POS (Xác nhận #83)'),
(329, 25, 1, 'export', 2.0000, '2026-06-08 07:51:56', 'Admin (Chuyển đi #64)'),
(330, 25, 2, 'import', 2.0000, '2026-06-08 07:51:56', 'Admin (Nhận từ #64)'),
(331, 18, 1, 'export', 3.0000, '2026-06-08 07:51:59', 'Admin (Chuyển đi #63)'),
(332, 18, 2, 'import', 3.0000, '2026-06-08 07:51:59', 'Admin (Nhận từ #63)'),
(333, 16, 1, 'export', 5.0000, '2026-06-08 07:52:01', 'Admin (Chuyển đi #62)'),
(334, 16, 2, 'import', 5.0000, '2026-06-08 07:52:01', 'Admin (Nhận từ #62)'),
(335, 39, 3, 'export', 10.0000, '2026-06-08 07:52:03', 'Admin (Chuyển đi #61)'),
(336, 39, 3, 'import', 10.0000, '2026-06-08 07:52:03', 'Admin (Nhận từ #61)'),
(337, 11, 1, 'export', 3.0000, '2026-06-08 07:52:05', 'Admin (Chuyển đi #60)'),
(338, 11, 2, 'import', 3.0000, '2026-06-08 07:52:05', 'Admin (Nhận từ #60)'),
(339, 40, 1, 'export', 2.0000, '2026-06-08 07:52:07', 'Admin (Chuyển đi #59)'),
(340, 40, 2, 'import', 2.0000, '2026-06-08 07:52:07', 'Admin (Nhận từ #59)'),
(341, 7, 1, 'export', 1.0000, '2026-06-08 07:52:09', 'Admin (Chuyển đi #58)'),
(342, 7, 2, 'import', 1.0000, '2026-06-08 07:52:09', 'Admin (Nhận từ #58)'),
(343, 36, 1, 'export', 4.0000, '2026-06-08 07:53:06', 'Admin (Chuyển đi #66)'),
(344, 36, 2, 'import', 4.0000, '2026-06-08 07:53:06', 'Admin (Nhận từ #66)'),
(345, 8, 9, 'export', 10.0000, '2026-06-08 07:53:08', 'Admin (Chuyển đi #65)'),
(346, 8, 2, 'import', 10.0000, '2026-06-08 07:53:08', 'Admin (Nhận từ #65)'),
(347, 19, 1, 'export', 5.0000, '2026-06-08 07:54:16', 'Admin (Chuyển đi #69)'),
(348, 19, 2, 'import', 5.0000, '2026-06-08 07:54:16', 'Admin (Nhận từ #69)'),
(349, 35, 1, 'export', 70.0000, '2026-06-08 07:54:18', 'Admin (Chuyển đi #70)'),
(350, 35, 2, 'import', 70.0000, '2026-06-08 07:54:18', 'Admin (Nhận từ #70)'),
(351, 20, 1, 'export', 3.0000, '2026-06-08 07:54:21', 'Admin (Chuyển đi #68)'),
(352, 20, 9, 'import', 3.0000, '2026-06-08 07:54:21', 'Admin (Nhận từ #68)'),
(353, 41, 1, 'export', 7.0000, '2026-06-08 07:54:23', 'Admin (Chuyển đi #67)'),
(354, 41, 2, 'import', 7.0000, '2026-06-08 07:54:23', 'Admin (Nhận từ #67)'),
(355, 26, 9, 'export', 2.0000, '2026-06-08 07:55:07', 'Admin (Chuyển đi #72)'),
(356, 26, 2, 'import', 2.0000, '2026-06-08 07:55:07', 'Admin (Nhận từ #72)'),
(357, 6, 1, 'export', 2.0000, '2026-06-08 07:55:09', 'Admin (Chuyển đi #71)'),
(358, 6, 9, 'import', 2.0000, '2026-06-08 07:55:09', 'Admin (Nhận từ #71)'),
(359, 37, 1, 'export', 0.8000, '2026-06-08 07:55:44', 'Admin (Chuyển đi #74)'),
(360, 37, 2, 'import', 0.8000, '2026-06-08 07:55:44', 'Admin (Nhận từ #74)'),
(361, 5, 1, 'export', 10.0000, '2026-06-08 07:55:47', 'Admin (Chuyển đi #73)'),
(362, 5, 2, 'import', 10.0000, '2026-06-08 07:55:47', 'Admin (Nhận từ #73)'),
(363, 21, 2, 'export', 0.0000, '2026-06-08 09:59:18', 'POS (Xác nhận #84)'),
(364, 2, 2, 'export', 0.0400, '2026-06-08 09:59:18', 'POS (Xác nhận #84)'),
(365, 12, 2, 'export', 0.0000, '2026-06-08 09:59:18', 'POS (Xác nhận #84)'),
(366, 25, 1, 'import', 3.0000, '2026-06-10 04:09:20', 'Admin (Nhận hàng PO #9)'),
(367, 26, 1, 'import', 3.0000, '2026-06-10 04:09:20', 'Admin (Nhận hàng PO #9)'),
(368, 33, 3, 'import', 6.0000, '2026-06-10 04:09:20', 'Admin (Nhận hàng PO #9)'),
(369, 36, 1, 'import', 3.0000, '2026-06-10 04:09:20', 'Admin (Nhận hàng PO #9)'),
(370, 37, 1, 'import', 7.0000, '2026-06-10 04:09:20', 'Admin (Nhận hàng PO #9)'),
(371, 40, 1, 'import', 4.0000, '2026-06-10 04:09:20', 'Admin (Nhận hàng PO #9)'),
(372, 42, 1, 'import', 4.0000, '2026-06-10 04:09:20', 'Admin (Nhận hàng PO #9)'),
(373, 14, 1, 'import', 5.0000, '2026-06-10 04:15:59', 'Admin (Nhận hàng PO #10)'),
(374, 26, 1, 'import', 4.0000, '2026-06-10 04:19:56', 'Admin (Nhận hàng PO #11)'),
(375, 2, 1, 'import', 2.0000, '2026-06-10 04:23:37', 'Admin (Nhận hàng PO #12)'),
(376, 17, 2, 'export', 0.2500, '2026-06-12 02:49:03', 'POS (Xác nhận #85)'),
(377, 12, 2, 'export', 0.0000, '2026-06-12 02:49:03', 'POS (Xác nhận #85)'),
(389, 15, 1, 'import', 10.0000, '2026-06-12 02:54:55', 'Admin (Nhận hàng PO #13)'),
(390, 14, 2, 'export', 0.4500, '2026-06-12 02:57:19', 'POS (Xác nhận #87)'),
(391, 2, 2, 'export', 0.0200, '2026-06-12 02:57:19', 'POS (Xác nhận #87)'),
(392, 12, 2, 'export', 0.0000, '2026-06-12 02:57:19', 'POS (Xác nhận #87)'),
(393, 3, 2, 'export', 0.0100, '2026-06-12 02:57:19', 'POS (Xác nhận #87)'),
(394, 1, 2, 'loss', 50.0000, '2026-06-13 02:12:22', 'Admin'),
(395, 1, 2, 'loss', 50.0000, '2026-06-13 02:13:29', 'Admin'),
(396, 23, 1, 'loss', 8.0000, '2026-06-13 02:13:51', 'Admin'),
(397, 34, 2, 'export', 0.2000, '2026-06-13 05:19:24', 'POS (Xác nhận #88)'),
(402, 34, 2, 'export', 0.2000, '2026-06-13 05:31:48', 'POS (Xác nhận Món & Topping #91)'),
(403, 20, 2, 'export', 0.2000, '2026-06-13 05:31:48', 'POS (Vét kho dự phòng #91)'),
(404, 33, 3, 'import', 5.5000, '2026-06-15 02:17:12', 'Admin (Nhận hàng PO #14)'),
(405, 30, 1, 'import', 2.5000, '2026-06-15 02:19:13', 'Admin (Nhận hàng PO #15)'),
(406, 7, 1, 'import', 10.0000, '2026-06-15 02:27:49', 'Admin (Nhận hàng PO #16)'),
(407, 18, 1, 'import', 10.0000, '2026-06-15 02:27:49', 'Admin (Nhận hàng PO #16)'),
(408, 37, 1, 'import', 5.0000, '2026-06-15 02:27:49', 'Admin (Nhận hàng PO #16)'),
(409, 17, 1, 'import', 15.0000, '2026-06-15 02:27:49', 'Admin (Nhận hàng PO #16)'),
(410, 19, 1, 'import', 15.0000, '2026-06-15 02:27:49', 'Admin (Nhận hàng PO #16)'),
(411, 18, 1, 'loss', 5.0000, '2026-06-15 02:28:46', 'Admin'),
(412, 7, 1, 'loss', 2.0000, '2026-06-15 02:29:00', 'Admin'),
(413, 19, 1, 'loss', 10.0000, '2026-06-15 02:29:12', 'Admin'),
(414, 37, 1, 'loss', 7.0000, '2026-06-15 02:29:23', 'Admin'),
(415, 22, 1, 'import', 10.0000, '2026-06-15 02:30:27', 'Admin (Nhận hàng PO #18)'),
(416, 22, 1, 'loss', 8.0000, '2026-06-15 02:30:43', 'Admin'),
(417, 50, 1, 'import', 65.0000, '2026-06-15 02:33:13', 'Admin (Nhận hàng PO #19)'),
(418, 46, 1, 'export', 10.0000, '2026-06-15 02:35:17', 'Admin (Chuyển đi #78)'),
(419, 46, 2, 'import', 10.0000, '2026-06-15 02:35:17', 'Admin (Nhận từ #78)'),
(420, 42, 1, 'export', 8.0000, '2026-06-15 02:35:20', 'Admin (Chuyển đi #77)'),
(421, 42, 2, 'import', 8.0000, '2026-06-15 02:35:20', 'Admin (Nhận từ #77)'),
(422, 45, 1, 'export', 10.0000, '2026-06-15 02:35:20', 'Admin (Chuyển đi #77)'),
(423, 45, 2, 'import', 10.0000, '2026-06-15 02:35:20', 'Admin (Nhận từ #77)'),
(424, 51, 1, 'export', 8.0000, '2026-06-15 02:35:22', 'Admin (Chuyển đi #76)'),
(425, 51, 2, 'import', 8.0000, '2026-06-15 02:35:22', 'Admin (Nhận từ #76)'),
(426, 48, 1, 'export', 8.0000, '2026-06-15 02:35:24', 'Admin (Chuyển đi #75)'),
(427, 48, 2, 'import', 8.0000, '2026-06-15 02:35:24', 'Admin (Nhận từ #75)'),
(428, 22, 1, 'export', 8.0000, '2026-06-15 02:37:48', 'Admin (Chuyển đi #81)'),
(429, 22, 2, 'import', 8.0000, '2026-06-15 02:37:48', 'Admin (Nhận từ #81)'),
(430, 47, 1, 'export', 8.0000, '2026-06-15 02:37:48', 'Admin (Chuyển đi #81)'),
(431, 47, 2, 'import', 8.0000, '2026-06-15 02:37:48', 'Admin (Nhận từ #81)'),
(432, 49, 1, 'export', 8.0000, '2026-06-15 02:37:50', 'Admin (Chuyển đi #82)'),
(433, 49, 9, 'import', 8.0000, '2026-06-15 02:37:50', 'Admin (Nhận từ #82)'),
(434, 50, 1, 'export', 50.0000, '2026-06-15 02:37:50', 'Admin (Chuyển đi #82)'),
(435, 50, 9, 'import', 50.0000, '2026-06-15 02:37:50', 'Admin (Nhận từ #82)'),
(436, 6, 1, 'export', 4.0000, '2026-06-15 02:37:51', 'Admin (Chuyển đi #79)'),
(437, 6, 2, 'import', 4.0000, '2026-06-15 02:37:51', 'Admin (Nhận từ #79)'),
(442, 38, 1, 'export', 6.0000, '2026-06-15 02:38:45', 'Admin (Chuyển đi #83)'),
(443, 38, 2, 'import', 6.0000, '2026-06-15 02:38:45', 'Admin (Nhận từ #83)'),
(444, 50, 9, 'export', 40.0000, '2026-06-15 02:47:56', 'Admin (Chuyển đi #84)'),
(445, 50, 2, 'import', 40.0000, '2026-06-15 02:47:56', 'Admin (Nhận từ #84)'),
(446, 2, 9, 'export', 5.0000, '2026-06-15 02:47:56', 'Admin (Chuyển đi #84)'),
(447, 2, 2, 'import', 5.0000, '2026-06-15 02:47:56', 'Admin (Nhận từ #84)'),
(448, 26, 9, 'export', 3.0000, '2026-06-15 02:47:56', 'Admin (Chuyển đi #84)'),
(449, 26, 2, 'import', 3.0000, '2026-06-15 02:47:56', 'Admin (Nhận từ #84)'),
(450, 49, 9, 'export', 8.0000, '2026-06-15 02:47:56', 'Admin (Chuyển đi #84)'),
(451, 49, 2, 'import', 8.0000, '2026-06-15 02:47:56', 'Admin (Nhận từ #84)'),
(452, 21, 9, 'export', 7.0000, '2026-06-15 02:47:56', 'Admin (Chuyển đi #84)'),
(453, 21, 2, 'import', 7.0000, '2026-06-15 02:47:56', 'Admin (Nhận từ #84)'),
(454, 8, 9, 'export', 20.0000, '2026-06-15 02:47:56', 'Admin (Chuyển đi #84)'),
(455, 8, 2, 'import', 20.0000, '2026-06-15 02:47:56', 'Admin (Nhận từ #84)'),
(456, 6, 9, 'export', 2.0000, '2026-06-15 02:47:56', 'Admin (Chuyển đi #84)'),
(457, 6, 2, 'import', 2.0000, '2026-06-15 02:47:56', 'Admin (Nhận từ #84)'),
(458, 87, 3, 'export', 5.0000, '2026-06-15 04:21:32', 'POS (Xác nhận Món & Topping #93)'),
(459, 88, 3, 'export', 45.0000, '2026-06-15 04:21:32', 'POS (Xác nhận Món & Topping #93)'),
(460, 89, 3, 'export', 20.0000, '2026-06-15 04:21:32', 'POS (Xác nhận Món & Topping #93)'),
(461, 36, 2, 'export', 0.0400, '2026-06-15 04:24:17', 'POS (Xác nhận Món & Topping #94)'),
(462, 37, 2, 'export', 0.0100, '2026-06-15 04:24:17', 'POS (Xác nhận Món & Topping #94)'),
(463, 2, 2, 'export', 0.0100, '2026-06-15 04:24:17', 'POS (Xác nhận Món & Topping #94)'),
(464, 49, 2, 'export', 0.0200, '2026-06-15 04:24:17', 'POS (Xác nhận Món & Topping #94)'),
(465, 85, 3, 'export', 50.0000, '2026-06-15 04:24:17', 'POS (Vét kho dự phòng #94)'),
(466, 86, 3, 'export', 1.0000, '2026-06-15 04:24:17', 'POS (Xác nhận Món & Topping #94)'),
(467, 84, 3, 'export', 150.0000, '2026-06-15 04:24:17', 'POS (Xác nhận Món & Topping #94)'),
(468, 33, 3, 'export', 0.0100, '2026-06-15 07:14:03', 'Hệ thống KDS (Báo xong món POS)'),
(469, 14, 2, 'export', 0.2000, '2026-06-15 07:14:03', 'Hệ thống KDS (Báo xong món POS)'),
(470, 28, 2, 'export', 0.0200, '2026-06-15 07:14:03', 'Hệ thống KDS (Báo xong món POS)'),
(471, 35, 2, 'export', 2.0000, '2026-06-15 07:14:03', 'Hệ thống KDS (Báo xong món POS)'),
(472, 22, 2, 'export', 0.0400, '2026-06-15 07:14:03', 'Hệ thống KDS (Báo xong món POS)'),
(473, 52, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(474, 53, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(475, 54, 3, 'import', 55.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(476, 55, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(477, 56, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(478, 57, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(479, 58, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(480, 59, 3, 'import', 51.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(481, 60, 3, 'import', 150.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(482, 61, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(483, 62, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(484, 63, 3, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(485, 64, 3, 'import', 51.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(486, 65, 1, 'import', 100.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(487, 66, 1, 'import', 150.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(488, 67, 1, 'import', 100.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(489, 68, 1, 'import', 550.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(490, 69, 1, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(491, 70, 1, 'import', 150.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(492, 71, 1, 'import', 250.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(493, 72, 1, 'import', 250.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(494, 73, 1, 'import', 70.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(495, 74, 1, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(496, 75, 1, 'import', 60.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(497, 76, 1, 'import', 250.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(498, 77, 1, 'import', 250.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(499, 78, 1, 'import', 250.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(500, 79, 1, 'import', 55.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(501, 80, 1, 'import', 51.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(502, 81, 1, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(503, 82, 1, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(504, 83, 1, 'import', 52.0000, '2026-06-15 07:30:41', 'Admin (Auto Fix)'),
(505, 22, 2, 'export', 0.0200, '2026-06-15 07:38:25', 'POS (Vét kho dự phòng #95)'),
(506, 22, 2, 'export', 0.0200, '2026-06-15 07:38:25', 'POS (Vét kho dự phòng #95)'),
(507, 35, 2, 'export', 1.0000, '2026-06-15 07:38:25', 'POS (Xác nhận Món & Topping #95)'),
(508, 35, 2, 'export', 1.0000, '2026-06-15 07:38:25', 'POS (Xác nhận Món & Topping #95)'),
(509, 87, 3, 'export', 5.0000, '2026-06-15 07:38:25', 'POS (Xác nhận Món & Topping #95)'),
(510, 88, 3, 'export', 45.0000, '2026-06-15 07:38:25', 'POS (Xác nhận Món & Topping #95)'),
(511, 89, 3, 'export', 20.0000, '2026-06-15 07:38:25', 'POS (Xác nhận Món & Topping #95)'),
(512, 7, 2, 'export', 0.0000, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(513, 6, 2, 'export', 0.5000, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(514, 11, 2, 'export', 0.0500, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(515, 7, 2, 'export', 0.0000, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(516, 11, 2, 'export', 0.0500, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(517, 6, 2, 'export', 0.5000, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(518, 30, 2, 'export', 0.4000, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(519, 29, 2, 'export', 0.2000, '2026-06-15 07:44:05', 'POS (Xác nhận Món & Topping #96)'),
(523, 55, 3, 'export', 12.0000, '2026-06-15 07:57:07', 'Admin (Chuyển đi #85)'),
(524, 55, 2, 'import', 12.0000, '2026-06-15 07:57:07', 'Admin (Nhận từ #85)'),
(529, 42, 2, 'export', 0.1000, '2026-06-15 08:01:46', 'POS (Xác nhận Món & Topping #98)'),
(530, 41, 2, 'export', 0.1200, '2026-06-15 08:01:46', 'POS (Xác nhận Món & Topping #98)'),
(531, 5, 2, 'export', 0.0800, '2026-06-15 08:01:46', 'POS (Xác nhận Món & Topping #98)'),
(532, 55, 3, 'export', 0.0200, '2026-06-15 08:01:46', 'POS (Xác nhận Món & Topping #98)'),
(533, 56, 3, 'export', 15.0000, '2026-06-15 08:01:46', 'POS (Xác nhận Món & Topping #98)'),
(534, 52, 3, 'export', 45.0000, '2026-06-15 08:01:47', 'POS (Xác nhận Món & Topping #98)'),
(535, 53, 3, 'export', 15.0000, '2026-06-15 08:01:47', 'POS (Xác nhận Món & Topping #98)'),
(536, 54, 3, 'export', 0.0500, '2026-06-15 08:01:47', 'POS (Xác nhận Món & Topping #98)'),
(569, 17, 2, 'export', 0.2500, '2026-06-15 09:42:24', 'POS (Xác nhận Món & Topping #100)'),
(570, 12, 2, 'export', 0.0000, '2026-06-15 09:42:24', 'POS (Xác nhận Món & Topping #100)'),
(571, 55, 3, 'export', 0.0200, '2026-06-15 09:42:24', 'POS (Xác nhận Món & Topping #100)'),
(572, 56, 3, 'export', 0.0200, '2026-06-15 09:42:24', 'POS (Xác nhận Món & Topping #100)'),
(573, 54, 3, 'export', 0.0500, '2026-06-15 09:42:24', 'POS (Xác nhận Món & Topping #100)'),
(574, 53, 3, 'export', 0.0200, '2026-06-15 09:42:24', 'POS (Xác nhận Món & Topping #100)'),
(575, 52, 3, 'export', 0.0600, '2026-06-15 09:42:24', 'POS (Xác nhận Món & Topping #100)'),
(576, 17, 2, 'import', 0.2500, '2026-06-15 09:46:01', 'Admin (Hoàn kho #100)'),
(577, 12, 2, 'import', 0.0000, '2026-06-15 09:46:01', 'Admin (Hoàn kho #100)'),
(578, 55, 3, 'import', 0.0200, '2026-06-15 09:46:01', 'Admin (Hoàn kho #100)'),
(579, 56, 3, 'import', 0.0200, '2026-06-15 09:46:01', 'Admin (Hoàn kho #100)'),
(580, 54, 3, 'import', 0.0500, '2026-06-15 09:46:01', 'Admin (Hoàn kho #100)'),
(581, 53, 3, 'import', 0.0200, '2026-06-15 09:46:01', 'Admin (Hoàn kho #100)'),
(582, 52, 3, 'import', 0.0600, '2026-06-15 09:46:01', 'Admin (Hoàn kho #100)'),
(583, 29, 2, 'export', 0.2000, '2026-06-15 09:46:12', 'Hệ thống KDS (Báo xong món POS)'),
(584, 30, 2, 'export', 0.4000, '2026-06-15 09:46:12', 'Hệ thống KDS (Báo xong món POS)'),
(585, 1, 7, '', -100.0000, '2026-06-16 03:56:45', 'Admin'),
(586, 7, 7, '', -2.0000, '2026-06-16 03:56:45', 'Admin'),
(587, 18, 7, '', -5.0000, '2026-06-16 03:56:45', 'Admin'),
(588, 19, 7, '', -10.0000, '2026-06-16 03:56:45', 'Admin'),
(589, 22, 7, '', -8.0000, '2026-06-16 03:56:45', 'Admin'),
(590, 23, 7, '', -8.0000, '2026-06-16 03:56:45', 'Admin'),
(591, 37, 7, '', -7.0000, '2026-06-16 03:56:45', 'Admin'),
(592, 38, 1, 'export', 4.0000, '2026-06-16 03:58:02', 'Admin (Chuyển đi #86)'),
(593, 38, 7, 'import', 4.0000, '2026-06-16 03:58:02', 'Admin (Nhận từ #86)'),
(594, 38, 7, '', -4.0000, '2026-06-16 03:58:19', 'Admin'),
(595, 7, 2, 'import', 0.0000, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(596, 6, 2, 'import', 0.5000, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(597, 11, 2, 'import', 0.0500, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(598, 7, 2, 'import', 0.0000, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(599, 11, 2, 'import', 0.0500, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(600, 6, 2, 'import', 0.5000, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(601, 30, 2, 'import', 0.4000, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(602, 29, 2, 'import', 0.2000, '2026-06-17 13:24:42', 'Admin (Hoàn kho #96)'),
(603, 40, 2, 'loss', 2.0000, '2026-06-18 04:24:32', 'Admin'),
(604, 40, 1, 'loss', 6.0000, '2026-06-18 04:24:45', 'Admin'),
(605, 33, 3, 'loss', 7.4800, '2026-06-18 04:24:59', 'Admin'),
(606, 33, 6, 'loss', 0.0100, '2026-06-18 04:25:05', 'Admin'),
(607, 33, 7, 'loss', 7.4900, '2026-06-18 04:25:20', 'Admin'),
(608, 33, 7, '', -7.4900, '2026-06-18 04:25:26', 'Admin'),
(609, 40, 7, '', -8.0000, '2026-06-18 04:25:26', 'Admin'),
(610, 40, 1, 'import', 7.5000, '2026-06-18 11:31:57', 'Admin (Nhận hàng PO #20)'),
(611, 33, 1, 'import', 8.0000, '2026-06-18 11:31:57', 'Admin (Nhận hàng PO #20)'),
(612, 6, 1, 'loss', 1.0000, '2026-06-18 11:58:36', 'Admin'),
(613, 6, 2, 'loss', 6.0000, '2026-06-18 11:58:50', 'Admin'),
(614, 6, 7, '', -7.0000, '2026-06-18 11:58:57', 'Admin'),
(615, 33, 1, 'loss', 8.0000, '2026-06-18 12:08:26', 'Admin'),
(616, 10, 2, 'loss', 0.9900, '2026-06-18 12:08:47', 'Admin'),
(617, 38, 2, 'loss', 6.0000, '2026-06-18 12:11:54', 'Admin'),
(618, 30, 1, 'loss', 4.5000, '2026-06-18 12:12:03', 'Admin'),
(619, 10, 9, 'loss', 1.0000, '2026-06-18 12:12:11', 'Admin'),
(620, 10, 7, 'loss', 1.9900, '2026-06-18 12:12:17', 'Admin'),
(621, 10, 7, 'loss', 1.9900, '2026-06-18 12:12:22', 'Admin'),
(622, 10, 7, 'loss', 1.9900, '2026-06-18 12:15:28', 'Admin'),
(623, 10, 7, '', -1.9900, '2026-06-18 12:18:29', 'Admin'),
(624, 30, 7, '', -4.5000, '2026-06-18 12:18:29', 'Admin'),
(625, 33, 7, '', -8.0000, '2026-06-18 12:18:29', 'Admin'),
(626, 38, 7, '', -6.0000, '2026-06-18 12:18:29', 'Admin'),
(627, 5, 2, 'export', 0.3000, '2026-06-23 06:53:24', 'POS (Xác nhận Món & Topping #1101)'),
(628, 3, 2, 'export', 0.0100, '2026-06-23 06:53:24', 'POS (Xác nhận Món & Topping #1101)'),
(629, 10, 1, 'import', 1.0000, '2026-06-23 07:36:11', 'Admin (Nhận hàng PO #21)'),
(630, 30, 1, 'import', 5.0000, '2026-06-23 07:36:11', 'Admin (Nhận hàng PO #21)'),
(631, 33, 1, 'import', 8.0000, '2026-06-23 07:36:11', 'Admin (Nhận hàng PO #21)'),
(632, 38, 1, 'import', 10.0000, '2026-06-23 07:36:11', 'Admin (Nhận hàng PO #21)'),
(633, 14, 1, 'export', 7.0000, '2026-06-23 07:36:51', 'Admin (Chuyển đi #87)'),
(634, 14, 2, 'import', 7.0000, '2026-06-23 07:36:51', 'Admin (Nhận từ #87)'),
(639, 38, 1, 'export', 8.0000, '2026-06-23 07:40:10', 'Admin (Chuyển đi #88)'),
(640, 38, 2, 'import', 8.0000, '2026-06-23 07:40:10', 'Admin (Nhận từ #88)'),
(641, 44, 1, 'export', 8.0000, '2026-06-23 07:40:10', 'Admin (Chuyển đi #88)'),
(642, 44, 2, 'import', 8.0000, '2026-06-23 07:40:10', 'Admin (Nhận từ #88)'),
(643, 7, 2, 'export', 0.0000, '2026-06-23 07:40:16', 'POS (Xác nhận Món & Topping #1102)'),
(644, 11, 2, 'export', 0.0500, '2026-06-23 07:40:16', 'POS (Xác nhận Món & Topping #1102)'),
(645, 7, 2, 'export', 0.0000, '2026-06-23 07:40:16', 'POS (Xác nhận Món & Topping #1102)'),
(646, 11, 2, 'export', 0.0500, '2026-06-23 07:40:16', 'POS (Xác nhận Món & Topping #1102)'),
(647, 38, 2, 'export', 0.0500, '2026-06-23 07:40:16', 'POS (Vét kho dự phòng #1102)'),
(648, 77, 1, 'export', 250.0000, '2026-06-23 09:06:42', 'Admin (Chuyển đi #89)'),
(649, 77, 9, 'import', 250.0000, '2026-06-23 09:06:42', 'Admin (Nhận từ #89)'),
(650, 78, 1, 'export', 250.0000, '2026-06-23 09:06:42', 'Admin (Chuyển đi #89)'),
(651, 78, 9, 'import', 250.0000, '2026-06-23 09:06:42', 'Admin (Nhận từ #89)'),
(652, 67, 1, 'export', 100.0000, '2026-06-23 09:06:42', 'Admin (Chuyển đi #89)'),
(653, 67, 9, 'import', 100.0000, '2026-06-23 09:06:42', 'Admin (Nhận từ #89)'),
(654, 76, 1, 'export', 250.0000, '2026-06-23 09:07:23', 'Admin (Chuyển đi #90)'),
(655, 76, 9, 'import', 250.0000, '2026-06-23 09:07:23', 'Admin (Nhận từ #90)'),
(656, 68, 1, 'export', 550.0000, '2026-06-23 09:07:23', 'Admin (Chuyển đi #90)'),
(657, 68, 9, 'import', 550.0000, '2026-06-23 09:07:23', 'Admin (Nhận từ #90)'),
(658, 40, 1, 'export', 7.0000, '2026-06-23 09:07:23', 'Admin (Chuyển đi #90)'),
(659, 40, 9, 'import', 7.0000, '2026-06-23 09:07:23', 'Admin (Nhận từ #90)'),
(660, 20, 1, 'loss', 10.0000, '2026-06-23 09:07:49', 'Admin'),
(661, 33, 1, 'loss', 8.0000, '2026-06-23 09:07:55', 'Admin'),
(662, 10, 1, 'loss', 1.0000, '2026-06-23 09:07:59', 'Admin'),
(663, 10, 7, '', -1.0000, '2026-06-23 09:08:06', 'Admin'),
(664, 20, 7, '', -10.0000, '2026-06-23 09:08:06', 'Admin'),
(665, 33, 7, '', -8.0000, '2026-06-23 09:08:06', 'Admin'),
(666, 10, 1, 'import', 1.0000, '2026-06-23 09:08:35', 'Admin (Nhận hàng PO #22)'),
(667, 33, 1, 'import', 8.0000, '2026-06-23 09:08:35', 'Admin (Nhận hàng PO #22)'),
(668, 66, 1, 'export', 150.0000, '2026-06-23 09:09:09', 'Admin (Chuyển đi #91)'),
(669, 66, 9, 'import', 150.0000, '2026-06-23 09:09:09', 'Admin (Nhận từ #91)'),
(670, 72, 1, 'export', 250.0000, '2026-06-23 09:09:09', 'Admin (Chuyển đi #91)'),
(671, 72, 9, 'import', 250.0000, '2026-06-23 09:09:09', 'Admin (Nhận từ #91)'),
(672, 33, 1, 'export', 8.0000, '2026-06-23 09:09:09', 'Admin (Chuyển đi #91)'),
(673, 33, 9, 'import', 8.0000, '2026-06-23 09:09:09', 'Admin (Nhận từ #91)'),
(678, 75, 1, 'export', 50.0000, '2026-06-23 09:10:08', 'Admin (Chuyển đi #93)'),
(679, 75, 2, 'import', 50.0000, '2026-06-23 09:10:08', 'Admin (Nhận từ #93)'),
(680, 79, 1, 'export', 50.0000, '2026-06-23 09:10:08', 'Admin (Chuyển đi #93)'),
(681, 79, 2, 'import', 50.0000, '2026-06-23 09:10:08', 'Admin (Nhận từ #93)'),
(682, 70, 1, 'export', 150.0000, '2026-06-23 09:10:47', 'Admin (Chuyển đi #94)'),
(683, 70, 2, 'import', 150.0000, '2026-06-23 09:10:47', 'Admin (Nhận từ #94)'),
(684, 65, 1, 'export', 80.0000, '2026-06-23 09:10:47', 'Admin (Chuyển đi #94)'),
(685, 65, 2, 'import', 80.0000, '2026-06-23 09:10:47', 'Admin (Nhận từ #94)'),
(686, 83, 1, 'export', 40.0000, '2026-06-23 09:10:47', 'Admin (Chuyển đi #94)'),
(687, 83, 2, 'import', 40.0000, '2026-06-23 09:10:47', 'Admin (Nhận từ #94)'),
(688, 71, 1, 'export', 250.0000, '2026-06-23 09:11:22', 'Admin (Chuyển đi #96)'),
(689, 71, 2, 'import', 250.0000, '2026-06-23 09:11:22', 'Admin (Nhận từ #96)'),
(690, 81, 1, 'export', 40.0000, '2026-06-23 09:11:24', 'Admin (Chuyển đi #95)'),
(691, 81, 2, 'import', 40.0000, '2026-06-23 09:11:24', 'Admin (Nhận từ #95)'),
(692, 67, 9, 'export', 100.0000, '2026-06-23 09:12:35', 'Admin (Chuyển đi #99)'),
(693, 67, 2, 'import', 100.0000, '2026-06-23 09:12:35', 'Admin (Nhận từ #99)'),
(694, 76, 9, 'export', 250.0000, '2026-06-23 09:12:37', 'Admin (Chuyển đi #98)'),
(695, 76, 2, 'import', 250.0000, '2026-06-23 09:12:37', 'Admin (Nhận từ #98)'),
(696, 74, 1, 'export', 40.0000, '2026-06-23 09:12:39', 'Admin (Chuyển đi #97)'),
(697, 74, 2, 'import', 40.0000, '2026-06-23 09:12:39', 'Admin (Nhận từ #97)'),
(698, 20, 2, 'loss', 4.8000, '2026-06-23 09:13:01', 'Admin'),
(699, 20, 9, 'loss', 3.0000, '2026-06-23 09:13:08', 'Admin'),
(700, 10, 1, 'loss', 1.0000, '2026-06-23 09:13:13', 'Admin'),
(701, 10, 7, '', -1.0000, '2026-06-23 09:13:17', 'Admin'),
(702, 20, 7, '', -7.8000, '2026-06-23 09:13:17', 'Admin'),
(703, 10, 1, 'import', 2.0000, '2026-06-23 09:13:37', 'Admin (Nhận hàng PO #23)'),
(704, 40, 9, 'export', 7.0000, '2026-06-23 09:14:26', 'Admin (Chuyển đi #100)'),
(705, 40, 2, 'import', 7.0000, '2026-06-23 09:14:26', 'Admin (Nhận từ #100)'),
(706, 78, 9, 'export', 250.0000, '2026-06-23 09:16:09', 'Admin (Chuyển đi #101)'),
(707, 78, 2, 'import', 250.0000, '2026-06-23 09:16:09', 'Admin (Nhận từ #101)'),
(708, 77, 9, 'export', 250.0000, '2026-06-23 09:16:09', 'Admin (Chuyển đi #101)'),
(709, 77, 2, 'import', 250.0000, '2026-06-23 09:16:09', 'Admin (Nhận từ #101)'),
(710, 2, 9, 'export', 2.0000, '2026-06-23 09:16:09', 'Admin (Chuyển đi #101)'),
(711, 2, 2, 'import', 2.0000, '2026-06-23 09:16:09', 'Admin (Nhận từ #101)'),
(712, 69, 1, 'export', 40.0000, '2026-06-23 09:17:45', 'Admin (Chuyển đi #103)'),
(713, 69, 2, 'import', 40.0000, '2026-06-23 09:17:45', 'Admin (Nhận từ #103)'),
(714, 82, 1, 'export', 40.0000, '2026-06-23 09:17:47', 'Admin (Chuyển đi #102)'),
(715, 82, 2, 'import', 40.0000, '2026-06-23 09:17:47', 'Admin (Nhận từ #102)'),
(716, 73, 1, 'export', 50.0000, '2026-06-23 09:19:03', 'Admin (Chuyển đi #105)'),
(717, 73, 2, 'import', 50.0000, '2026-06-23 09:19:03', 'Admin (Nhận từ #105)'),
(718, 80, 1, 'export', 40.0000, '2026-06-23 09:19:05', 'Admin (Chuyển đi #104)'),
(719, 80, 2, 'import', 40.0000, '2026-06-23 09:19:05', 'Admin (Nhận từ #104)'),
(720, 14, 1, 'import', 10.0000, '2026-06-23 09:30:49', 'Admin (Nhận hàng PO #24)'),
(721, 14, 2, 'export', 0.4500, '2026-06-25 12:12:03', 'POS (Xác nhận Món & Topping #1103)'),
(722, 2, 2, 'export', 0.0200, '2026-06-25 12:12:03', 'POS (Xác nhận Món & Topping #1103)'),
(723, 12, 2, 'export', 0.0000, '2026-06-25 12:12:03', 'POS (Xác nhận Món & Topping #1103)'),
(724, 3, 2, 'export', 0.0100, '2026-06-25 12:12:03', 'POS (Xác nhận Món & Topping #1103)'),
(735, 2, 2, 'export', 0.0150, '2026-06-25 12:34:25', 'Hệ thống KDS (Báo xong món POS)'),
(736, 14, 2, 'export', 0.4500, '2026-06-25 12:34:25', 'Hệ thống KDS (Báo xong món POS)'),
(737, 12, 2, 'export', 0.0030, '2026-06-25 12:34:25', 'Hệ thống KDS (Báo xong món POS)'),
(738, 3, 2, 'export', 0.0100, '2026-06-25 12:34:25', 'Hệ thống KDS (Báo xong món POS)'),
(739, 33, 9, 'export', 8.0000, '2026-06-25 13:07:51', 'Admin (Chuyển đi #106)'),
(740, 33, 2, 'import', 8.0000, '2026-06-25 13:07:51', 'Admin (Nhận từ #106)'),
(741, 33, 2, 'export', 0.0100, '2026-06-25 13:07:56', 'POS (Vét kho dự phòng #1104)'),
(742, 14, 2, 'export', 0.2000, '2026-06-25 13:07:56', 'POS (Xác nhận Món & Topping #1104)'),
(743, 33, 2, 'export', 0.0100, '2026-06-26 11:42:45', 'Hệ thống KDS (Báo xong món POS)'),
(744, 14, 2, 'export', 0.2000, '2026-06-26 11:42:45', 'Hệ thống KDS (Báo xong món POS)'),
(746, 10, 1, 'export', 2.0000, '2026-06-26 11:46:03', 'Admin (Chuyển đi #107)'),
(747, 10, 2, 'import', 2.0000, '2026-06-26 11:46:03', 'Admin (Nhận từ #107)'),
(748, 9, 2, 'export', 0.4000, '2026-06-26 11:46:08', 'POS (Xác nhận Món & Topping #1105)'),
(749, 10, 2, 'export', 0.0100, '2026-06-26 11:46:08', 'POS (Xác nhận Món & Topping #1105)'),
(750, 13, 2, 'export', 0.0100, '2026-06-26 11:46:08', 'POS (Xác nhận Món & Topping #1105)'),
(751, 30, 1, 'loss', 5.0000, '2026-06-26 11:49:36', 'Admin'),
(752, 30, 2, 'loss', 2.6000, '2026-06-26 11:49:45', 'Admin'),
(753, 18, 1, 'loss', 10.0000, '2026-06-26 11:49:58', 'Admin'),
(754, 18, 2, 'loss', 3.0000, '2026-06-26 11:50:04', 'Admin'),
(755, 36, 1, 'loss', 4.0000, '2026-06-26 11:50:11', 'Admin'),
(756, 36, 2, 'loss', 3.9600, '2026-06-26 11:50:15', 'Admin'),
(757, 7, 1, 'loss', 10.0000, '2026-06-26 11:50:20', 'Admin'),
(758, 7, 2, 'loss', 1.0000, '2026-06-26 11:50:24', 'Admin'),
(759, 7, 7, '', -11.0000, '2026-06-26 11:50:37', 'Admin'),
(760, 18, 7, '', -13.0000, '2026-06-26 11:50:37', 'Admin'),
(761, 30, 7, '', -7.6000, '2026-06-26 11:50:37', 'Admin'),
(762, 36, 7, '', -7.9600, '2026-06-26 11:50:37', 'Admin'),
(763, 7, 1, 'import', 2.0000, '2026-06-26 11:52:34', 'Admin (Nhận hàng PO #25)'),
(764, 18, 1, 'import', 5.0000, '2026-06-26 11:52:34', 'Admin (Nhận hàng PO #25)'),
(765, 20, 1, 'import', 10.0000, '2026-06-26 11:52:34', 'Admin (Nhận hàng PO #25)'),
(766, 30, 1, 'import', 8.0000, '2026-06-26 11:52:34', 'Admin (Nhận hàng PO #25)'),
(767, 36, 1, 'import', 15.0000, '2026-06-26 11:52:34', 'Admin (Nhận hàng PO #25)'),
(768, 30, 1, 'export', 8.0000, '2026-06-26 11:59:46', 'Admin (Chuyển đi #108)'),
(769, 30, 2, 'import', 8.0000, '2026-06-26 11:59:46', 'Admin (Nhận từ #108)'),
(770, 30, 2, 'export', 0.4000, '2026-06-26 11:59:51', 'POS (Xác nhận Món & Topping #1106)'),
(771, 29, 2, 'export', 0.2000, '2026-06-26 11:59:51', 'POS (Xác nhận Món & Topping #1106)'),
(772, 42, 2, 'export', 0.1000, '2026-06-26 12:11:58', 'POS (Xác nhận Món & Topping #1107)'),
(773, 5, 2, 'export', 0.0800, '2026-06-26 12:11:58', 'POS (Xác nhận Món & Topping #1107)'),
(774, 41, 2, 'export', 0.1200, '2026-06-26 12:11:58', 'POS (Xác nhận Món & Topping #1107)'),
(775, 17, 2, 'export', 0.2500, '2026-06-26 12:19:14', 'POS (Xác nhận Món & Topping #1108)'),
(776, 12, 2, 'export', 0.0010, '2026-06-26 12:19:14', 'POS (Xác nhận Món & Topping #1108)'),
(777, 33, 2, 'export', 0.0200, '2026-06-26 12:30:54', 'POS (Vét kho dự phòng #1109)'),
(778, 31, 2, 'export', 0.4000, '2026-06-26 12:30:54', 'POS (Xác nhận Món & Topping #1109)'),
(779, 16, 2, 'export', 0.2000, '2026-06-26 12:30:54', 'POS (Xác nhận Món & Topping #1109)'),
(780, 31, 2, 'export', 0.4000, '2026-06-26 12:35:58', 'Hệ thống KDS (Báo xong món POS)'),
(781, 16, 2, 'export', 0.2000, '2026-06-26 12:35:58', 'Hệ thống KDS (Báo xong món POS)'),
(782, 33, 2, 'export', 0.0200, '2026-06-26 12:35:58', 'Hệ thống KDS (Báo xong món POS)'),
(783, 7, 1, 'export', 2.0000, '2026-06-26 12:38:18', 'Admin (Chuyển đi #109)'),
(784, 7, 2, 'import', 2.0000, '2026-06-26 12:38:18', 'Admin (Nhận từ #109)'),
(785, 7, 2, 'export', 0.0005, '2026-06-26 12:38:23', 'POS (Xác nhận Món & Topping #1110)'),
(786, 11, 2, 'export', 0.0500, '2026-06-26 12:38:23', 'POS (Xác nhận Món & Topping #1110)'),
(787, 7, 2, 'export', 0.0005, '2026-06-26 12:38:23', 'POS (Xác nhận Món & Topping #1110)'),
(788, 11, 2, 'export', 0.0500, '2026-06-26 12:38:23', 'POS (Xác nhận Món & Topping #1110)'),
(789, 7, 2, 'export', 0.0010, '2026-06-26 12:38:57', 'Hệ thống KDS (Báo xong món POS)'),
(790, 11, 2, 'export', 0.1000, '2026-06-26 12:38:57', 'Hệ thống KDS (Báo xong món POS)'),
(791, 14, 2, 'export', 0.3500, '2026-06-26 12:43:07', 'Hệ thống KDS (Báo xong món POS)'),
(792, 34, 2, 'export', 0.2000, '2026-06-26 12:43:07', 'Hệ thống KDS (Báo xong món POS)'),
(793, 5, 2, 'export', 0.3000, '2026-06-26 14:35:17', 'POS (Xác nhận Món & Topping #1111)'),
(794, 3, 2, 'export', 0.0100, '2026-06-26 14:35:17', 'POS (Xác nhận Món & Topping #1111)'),
(795, 5, 2, 'export', 0.3000, '2026-06-26 14:38:54', 'Hệ thống KDS (Báo xong món POS)'),
(796, 3, 2, 'export', 0.0100, '2026-06-26 14:38:54', 'Hệ thống KDS (Báo xong món POS)'),
(797, 3, 1, 'loss', 2.0000, '2026-06-29 13:31:20', 'Admin'),
(798, 3, 2, 'loss', 2.8800, '2026-06-29 13:31:27', 'Admin'),
(817, 3, 7, 'loss', -4.8800, '2026-06-30 02:43:20', 'Admin'),
(818, 3, 1, 'import', 4.0000, '2026-06-30 02:46:32', 'Admin (Nhận hàng PO #29)'),
(819, 3, 1, 'export', 3.0000, '2026-06-30 02:46:58', 'Admin (Chuyển đi #110)'),
(820, 3, 2, 'import', 3.0000, '2026-06-30 02:46:58', 'Admin (Nhận từ #110)'),
(821, 21, 2, 'export', 0.0010, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(822, 21, 2, 'export', 0.0010, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(823, 2, 2, 'export', 0.0350, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(824, 12, 2, 'export', 0.0010, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(825, 2, 2, 'export', 0.0350, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(826, 12, 2, 'export', 0.0010, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(827, 2, 2, 'export', 0.0150, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(828, 14, 2, 'export', 0.4500, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(829, 12, 2, 'export', 0.0030, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(830, 3, 2, 'export', 0.0100, '2026-06-30 02:47:05', 'POS (Xác nhận Món & Topping #1113)'),
(831, 30, 2, 'export', 0.4000, '2026-06-30 02:47:06', 'POS (Xác nhận Món & Topping #1113)'),
(832, 29, 2, 'export', 0.2000, '2026-06-30 02:47:06', 'POS (Xác nhận Món & Topping #1113)'),
(833, 30, 2, 'export', 0.4000, '2026-06-30 10:45:34', 'POS (Xác nhận Món & Topping #1114)'),
(834, 29, 2, 'export', 0.2000, '2026-06-30 10:45:34', 'POS (Xác nhận Món & Topping #1114)'),
(835, 30, 2, 'import', 0.4000, '2026-06-30 10:46:23', 'Admin (Hoàn kho #1114)'),
(836, 29, 2, 'import', 0.2000, '2026-06-30 10:46:23', 'Admin (Hoàn kho #1114)'),
(837, 30, 2, 'export', 0.4000, '2026-06-30 10:46:51', 'POS (Xác nhận Món & Topping #1115)'),
(838, 29, 2, 'export', 0.2000, '2026-06-30 10:46:51', 'POS (Xác nhận Món & Topping #1115)'),
(839, 29, 2, 'export', 0.2000, '2026-06-30 10:47:18', 'Hệ thống KDS (Báo xong món POS)'),
(840, 30, 2, 'export', 0.4000, '2026-06-30 10:47:18', 'Hệ thống KDS (Báo xong món POS)'),
(841, 97, 1, 'import', 20.0000, '2026-07-01 02:35:29', '1'),
(842, 98, 1, 'import', 20.0000, '2026-07-01 02:35:29', '1'),
(843, 99, 1, 'import', 20.0000, '2026-07-01 02:35:29', '1'),
(844, 34, 1, 'loss', 1.0000, '2026-07-01 02:36:41', 'Admin'),
(845, 34, 2, 'loss', 1.2000, '2026-07-01 02:36:46', 'Admin'),
(846, 36, 1, 'loss', 15.0000, '2026-07-01 02:36:53', 'Admin'),
(847, 20, 1, 'loss', 10.0000, '2026-07-01 02:36:57', 'Admin'),
(848, 20, 7, 'loss', -10.0000, '2026-07-01 03:04:29', 'Admin'),
(849, 34, 7, 'loss', -2.2000, '2026-07-01 03:04:29', 'Admin'),
(850, 36, 7, 'loss', -15.0000, '2026-07-01 03:04:29', 'Admin'),
(851, 40, 1, 'loss', 0.5000, '2026-07-01 03:05:24', 'Admin'),
(852, 40, 2, 'loss', 7.0000, '2026-07-01 03:05:30', 'Admin'),
(853, 11, 1, 'loss', 5.0000, '2026-07-01 03:05:39', 'Admin'),
(854, 11, 2, 'loss', 2.7000, '2026-07-01 03:05:43', 'Admin'),
(855, 33, 2, 'loss', 7.9400, '2026-07-01 03:05:47', 'Admin'),
(856, 16, 1, 'loss', 10.0000, '2026-07-01 03:06:06', 'Admin'),
(857, 16, 2, 'loss', 4.6000, '2026-07-01 03:06:10', 'Admin'),
(858, 41, 1, 'loss', 8.0000, '2026-07-01 03:06:14', 'Admin'),
(859, 41, 2, 'loss', 6.7600, '2026-07-01 03:06:18', 'Admin'),
(860, 38, 1, 'loss', 2.0000, '2026-07-01 03:06:22', 'Admin'),
(861, 38, 2, 'loss', 7.9500, '2026-07-01 03:06:26', 'Admin'),
(862, 35, 1, 'loss', 30.0000, '2026-07-01 03:06:31', 'Admin'),
(863, 35, 2, 'loss', 66.0000, '2026-07-01 03:06:34', 'Admin'),
(864, 42, 2, 'loss', 7.8000, '2026-07-01 03:06:44', 'Admin'),
(865, 31, 1, 'loss', 10.0000, '2026-07-01 03:06:47', 'Admin'),
(866, 31, 2, 'loss', 9.2000, '2026-07-01 03:06:51', 'Admin'),
(867, 3, 1, 'loss', 1.0000, '2026-07-01 03:06:55', 'Admin'),
(868, 3, 2, 'loss', 2.9900, '2026-07-01 03:07:00', 'Admin'),
(869, 2, 2, 'loss', 8.6900, '2026-07-01 03:07:04', 'Admin'),
(870, 37, 1, 'loss', 5.2000, '2026-07-01 03:07:08', 'Admin'),
(871, 37, 2, 'loss', 0.8000, '2026-07-01 03:07:12', 'Admin'),
(872, 29, 1, 'loss', 10.0000, '2026-07-01 03:11:28', 'Admin'),
(873, 29, 2, 'loss', 7.0000, '2026-07-01 03:11:32', 'Admin'),
(874, 22, 1, 'loss', 2.0000, '2026-07-01 03:11:36', 'Admin'),
(875, 22, 2, 'loss', 7.9200, '2026-07-01 03:11:40', 'Admin'),
(876, 2, 7, 'loss', -8.6900, '2026-07-01 03:11:43', 'Admin'),
(877, 3, 7, 'loss', -3.9900, '2026-07-01 03:11:43', 'Admin'),
(878, 11, 7, 'loss', -7.7000, '2026-07-01 03:11:43', 'Admin'),
(879, 16, 7, 'loss', -14.6000, '2026-07-01 03:11:43', 'Admin'),
(880, 22, 7, 'loss', -9.9200, '2026-07-01 03:11:43', 'Admin'),
(881, 29, 7, 'loss', -17.0000, '2026-07-01 03:11:43', 'Admin'),
(882, 31, 7, 'loss', -19.2000, '2026-07-01 03:11:43', 'Admin'),
(883, 33, 7, 'loss', -7.9400, '2026-07-01 03:11:43', 'Admin'),
(884, 35, 7, 'loss', -96.0000, '2026-07-01 03:11:43', 'Admin'),
(885, 37, 7, 'loss', -6.0000, '2026-07-01 03:11:43', 'Admin'),
(886, 38, 7, 'loss', -9.9500, '2026-07-01 03:11:43', 'Admin'),
(887, 40, 7, 'loss', -7.5000, '2026-07-01 03:11:43', 'Admin'),
(888, 41, 7, 'loss', -14.7600, '2026-07-01 03:11:43', 'Admin'),
(889, 42, 7, 'loss', -7.8000, '2026-07-01 03:11:43', 'Admin'),
(890, 39, 3, 'loss', 10.0000, '2026-07-01 03:11:49', 'Admin'),
(891, 39, 7, 'loss', -10.0000, '2026-07-01 03:11:52', 'Admin'),
(892, 2, 1, 'import', 2.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(893, 3, 1, 'import', 2.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(894, 11, 1, 'import', 3.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(895, 16, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(896, 20, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(897, 22, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(898, 33, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(899, 34, 1, 'import', 2.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(900, 35, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(901, 38, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(902, 39, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(903, 40, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(904, 41, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)'),
(905, 42, 1, 'import', 8.0000, '2026-07-01 03:15:30', 'Admin (Nhận hàng PO #30)');
INSERT INTO `inventory_history` (`id`, `ingredient_id`, `warehouse_id`, `type`, `quantity`, `created_at`, `performed_by`) VALUES
(906, 29, 1, 'import', 8.0000, '2026-07-01 03:17:00', 'Admin (Nhận hàng PO #31)'),
(907, 31, 1, 'import', 8.0000, '2026-07-01 03:17:00', 'Admin (Nhận hàng PO #31)'),
(908, 36, 1, 'import', 8.0000, '2026-07-01 03:17:00', 'Admin (Nhận hàng PO #31)'),
(909, 37, 1, 'import', 8.0000, '2026-07-01 03:17:00', 'Admin (Nhận hàng PO #31)'),
(910, 20, 1, 'export', 8.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(911, 20, 2, 'import', 8.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(912, 16, 1, 'export', 8.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(913, 16, 2, 'import', 8.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(914, 36, 1, 'export', 8.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(915, 36, 2, 'import', 8.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(916, 3, 1, 'export', 2.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(917, 3, 2, 'import', 2.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(918, 29, 1, 'export', 8.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(919, 29, 2, 'import', 8.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(920, 33, 1, 'export', 6.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(921, 33, 2, 'import', 6.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(922, 2, 1, 'export', 2.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(923, 2, 2, 'import', 2.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(924, 17, 1, 'export', 18.0000, '2026-07-01 03:18:41', 'Admin (Chuyển đi #111)'),
(925, 17, 2, 'import', 18.0000, '2026-07-01 03:18:41', 'Admin (Nhận từ #111)'),
(926, 99, 1, 'export', 10.0000, '2026-07-01 03:20:47', 'Admin (Chuyển đi #112)'),
(927, 99, 2, 'import', 10.0000, '2026-07-01 03:20:47', 'Admin (Nhận từ #112)'),
(928, 98, 1, 'export', 10.0000, '2026-07-01 03:20:50', 'Admin (Chuyển đi #113)'),
(929, 98, 2, 'import', 10.0000, '2026-07-01 03:20:50', 'Admin (Nhận từ #113)'),
(930, 72, 9, 'export', 250.0000, '2026-07-01 03:22:29', 'Admin (Chuyển đi #118)'),
(931, 72, 2, 'import', 250.0000, '2026-07-01 03:22:29', 'Admin (Nhận từ #118)'),
(932, 66, 9, 'export', 150.0000, '2026-07-01 03:22:32', 'Admin (Chuyển đi #117)'),
(933, 66, 2, 'import', 150.0000, '2026-07-01 03:22:32', 'Admin (Nhận từ #117)'),
(934, 11, 1, 'export', 3.0000, '2026-07-01 03:22:34', 'Admin (Chuyển đi #116)'),
(935, 11, 2, 'import', 3.0000, '2026-07-01 03:22:34', 'Admin (Nhận từ #116)'),
(936, 40, 1, 'export', 8.0000, '2026-07-01 03:22:37', 'Admin (Chuyển đi #115)'),
(937, 40, 2, 'import', 8.0000, '2026-07-01 03:22:37', 'Admin (Nhận từ #115)'),
(938, 39, 1, 'export', 6.0000, '2026-07-01 03:22:40', 'Admin (Chuyển đi #114)'),
(939, 39, 2, 'import', 6.0000, '2026-07-01 03:22:40', 'Admin (Nhận từ #114)'),
(940, 18, 1, 'export', 5.0000, '2026-07-01 03:23:06', 'Admin (Chuyển đi #119)'),
(941, 18, 2, 'import', 5.0000, '2026-07-01 03:23:06', 'Admin (Nhận từ #119)'),
(942, 41, 1, 'export', 8.0000, '2026-07-01 03:23:06', 'Admin (Chuyển đi #119)'),
(943, 41, 2, 'import', 8.0000, '2026-07-01 03:23:06', 'Admin (Nhận từ #119)'),
(944, 34, 1, 'loss', 2.0000, '2026-07-01 03:23:18', 'Admin'),
(945, 34, 7, 'loss', -2.0000, '2026-07-01 03:23:24', 'Admin'),
(946, 38, 1, 'export', 8.0000, '2026-07-01 03:25:45', 'Admin (Chuyển đi #120)'),
(947, 38, 2, 'import', 8.0000, '2026-07-01 03:25:45', 'Admin (Nhận từ #120)'),
(948, 34, 1, 'import', 4.0000, '2026-07-01 03:26:24', 'Admin (Nhận hàng PO #32)'),
(949, 31, 1, 'export', 8.0000, '2026-07-01 03:28:07', 'Admin (Chuyển đi #122)'),
(950, 31, 9, 'import', 8.0000, '2026-07-01 03:28:07', 'Admin (Nhận từ #122)'),
(951, 42, 1, 'export', 8.0000, '2026-07-01 03:28:07', 'Admin (Chuyển đi #122)'),
(952, 42, 9, 'import', 8.0000, '2026-07-01 03:28:07', 'Admin (Nhận từ #122)'),
(953, 97, 1, 'export', 15.0000, '2026-07-01 03:28:12', 'Admin (Chuyển đi #121)'),
(954, 97, 2, 'import', 15.0000, '2026-07-01 03:28:12', 'Admin (Nhận từ #121)'),
(955, 37, 1, 'export', 8.0000, '2026-07-01 03:28:12', 'Admin (Chuyển đi #121)'),
(956, 37, 2, 'import', 8.0000, '2026-07-01 03:28:12', 'Admin (Nhận từ #121)'),
(957, 34, 1, 'export', 4.0000, '2026-07-01 03:29:05', 'Admin (Chuyển đi #124)'),
(958, 34, 2, 'import', 4.0000, '2026-07-01 03:29:05', 'Admin (Nhận từ #124)'),
(959, 22, 1, 'export', 8.0000, '2026-07-01 03:29:08', 'Admin (Chuyển đi #123)'),
(960, 22, 2, 'import', 8.0000, '2026-07-01 03:29:08', 'Admin (Nhận từ #123)'),
(961, 35, 1, 'export', 8.0000, '2026-07-01 03:30:13', 'Admin (Chuyển đi #125)'),
(962, 35, 2, 'import', 8.0000, '2026-07-01 03:30:13', 'Admin (Nhận từ #125)'),
(963, 34, 2, 'loss', 4.0000, '2026-07-01 03:33:49', 'Admin'),
(964, 34, 1, 'import', 2.0000, '2026-07-01 03:34:19', 'Admin (Nhận hàng PO #33)'),
(965, 33, 2, 'export', 0.0100, '2026-07-01 14:35:10', 'POS (Vét kho dự phòng #1116)'),
(966, 14, 2, 'export', 0.2000, '2026-07-01 14:35:10', 'POS (Xác nhận Món & Topping #1116)'),
(967, 22, 2, 'export', 0.0200, '2026-07-01 14:35:10', 'POS (Vét kho dự phòng #1116)'),
(968, 22, 2, 'export', 0.0200, '2026-07-01 14:35:10', 'POS (Vét kho dự phòng #1116)'),
(969, 35, 2, 'export', 1.0000, '2026-07-01 14:35:10', 'POS (Xác nhận Món & Topping #1116)'),
(970, 35, 2, 'export', 1.0000, '2026-07-01 14:35:10', 'POS (Xác nhận Món & Topping #1116)'),
(971, 2, 2, 'export', 0.0150, '2026-07-02 01:43:31', 'POS (Xác nhận Món & Topping #1122)'),
(972, 14, 2, 'export', 0.4500, '2026-07-02 01:43:31', 'POS (Xác nhận Món & Topping #1122)'),
(973, 12, 2, 'export', 0.0030, '2026-07-02 01:43:31', 'POS (Xác nhận Món & Topping #1122)'),
(974, 3, 2, 'export', 0.0100, '2026-07-02 01:43:31', 'POS (Xác nhận Món & Topping #1122)'),
(975, 2, 2, 'export', 0.0150, '2026-07-02 01:44:18', 'Hệ thống KDS (Báo xong món POS)'),
(976, 14, 2, 'export', 0.4500, '2026-07-02 01:44:18', 'Hệ thống KDS (Báo xong món POS)'),
(977, 12, 2, 'export', 0.0030, '2026-07-02 01:44:18', 'Hệ thống KDS (Báo xong món POS)'),
(978, 3, 2, 'export', 0.0100, '2026-07-02 01:44:18', 'Hệ thống KDS (Báo xong món POS)'),
(979, 2, 2, 'export', 0.0150, '2026-07-02 01:46:20', 'POS (Xác nhận Món & Topping #1123)'),
(980, 14, 2, 'export', 0.4500, '2026-07-02 01:46:20', 'POS (Xác nhận Món & Topping #1123)'),
(981, 12, 2, 'export', 0.0030, '2026-07-02 01:46:20', 'POS (Xác nhận Món & Topping #1123)'),
(982, 3, 2, 'export', 0.0100, '2026-07-02 01:46:20', 'POS (Xác nhận Món & Topping #1123)'),
(983, 2, 2, 'export', 0.0150, '2026-07-02 01:47:47', 'POS (Xác nhận Món & Topping #1124)'),
(984, 14, 2, 'export', 0.4500, '2026-07-02 01:47:47', 'POS (Xác nhận Món & Topping #1124)'),
(985, 12, 2, 'export', 0.0030, '2026-07-02 01:47:47', 'POS (Xác nhận Món & Topping #1124)'),
(986, 3, 2, 'export', 0.0100, '2026-07-02 01:47:47', 'POS (Xác nhận Món & Topping #1124)'),
(987, 2, 2, 'loss', 1.9400, '2026-07-11 11:38:42', 'Admin'),
(988, 2, 9, 'loss', 1.0000, '2026-07-11 11:38:47', 'Admin'),
(989, 2, 1, 'import', 8.0000, '2026-07-11 11:39:59', 'Admin (Nhận hàng PO #34)'),
(990, 2, 1, 'loss', 8.0000, '2026-07-11 11:47:02', 'Admin'),
(991, 2, 1, 'import', 8.0000, '2026-07-11 11:47:52', 'Admin (Nhận hàng PO #35)');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_receipts`
--

DROP TABLE IF EXISTS `inventory_receipts`;
CREATE TABLE `inventory_receipts` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `import_price` decimal(15,2) NOT NULL,
  `entry_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_receipts`
--

INSERT INTO `inventory_receipts` (`id`, `ingredient_id`, `supplier_id`, `quantity`, `import_price`, `entry_date`, `expiry_date`, `note`) VALUES
(1, 27, NULL, 20.00, 350000.00, '2026-06-06', '2026-07-06', 'Phiếu nhập PO: PO-20260606-VIP'),
(2, 28, NULL, 10.00, 250000.00, '2026-06-06', '2026-12-03', 'Phiếu nhập PO: PO-20260606-VIP'),
(3, 29, NULL, 18.00, 180000.00, '2026-06-06', '2026-07-06', 'Phiếu nhập PO: PO-20260606-VIP'),
(4, 30, NULL, 5.00, 150000.00, '2026-06-06', '2027-06-06', 'Phiếu nhập PO: PO-20260606-VIP'),
(5, 31, NULL, 20.00, 450000.00, '2026-06-06', '2026-07-06', 'Phiếu nhập PO: PO-20260606-VIP'),
(6, 32, NULL, 4.00, 80000.00, '2026-06-06', '2026-09-04', 'Phiếu nhập PO: PO-20260606-VIP'),
(7, 33, NULL, 2.00, 120000.00, '2026-06-06', '2026-06-20', 'Phiếu nhập PO: PO-20260606-VIP');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stocks`
--

DROP TABLE IF EXISTS `inventory_stocks`;
CREATE TABLE `inventory_stocks` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(10,4) DEFAULT 0.0000,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_stocks`
--

INSERT INTO `inventory_stocks` (`id`, `warehouse_id`, `ingredient_id`, `quantity`, `last_updated`) VALUES
(21, 1, 1, 0.0000, '2026-06-04 10:02:01'),
(22, 1, 2, 8.0000, '2026-07-11 18:47:52'),
(23, 1, 3, 0.0000, '2026-07-01 10:18:41'),
(24, 1, 4, 0.0000, '2026-06-04 09:34:21'),
(25, 1, 5, 15.0000, '2026-06-03 19:22:47'),
(26, 1, 6, 0.0000, '2026-06-18 18:58:36'),
(27, 1, 7, 0.0000, '2026-06-26 19:38:18'),
(28, 1, 8, 0.0000, '2026-06-04 09:34:28'),
(29, 1, 9, 4.0000, '2026-06-04 09:14:07'),
(30, 1, 10, 0.0000, '2026-06-26 18:46:03'),
(31, 1, 11, 0.0000, '2026-07-01 10:22:34'),
(32, 1, 12, 0.0000, '2026-06-04 09:39:38'),
(33, 1, 13, 2.0000, '2026-06-06 10:05:27'),
(34, 1, 14, 13.0000, '2026-06-23 16:30:49'),
(35, 1, 15, 12.0000, '2026-06-12 09:54:55'),
(36, 1, 16, 0.0000, '2026-07-01 10:18:41'),
(37, 1, 17, 7.0000, '2026-07-01 10:18:41'),
(38, 1, 18, 0.0000, '2026-07-01 10:23:06'),
(39, 8, 14, 5.0000, '2026-06-04 09:12:38'),
(40, 2, 9, 9.2000, '2026-06-26 18:46:08'),
(41, 8, 9, 6.0000, '2026-06-04 09:14:07'),
(43, 2, 14, 4.3500, '2026-07-02 08:47:47'),
(44, 9, 2, 0.0000, '2026-07-11 18:38:47'),
(45, 9, 10, 0.0000, '2026-06-18 19:12:11'),
(46, 9, 4, 10.0000, '2026-06-04 09:34:21'),
(47, 9, 8, 0.0000, '2026-06-15 09:47:56'),
(48, 9, 12, 1.0000, '2026-06-04 10:02:57'),
(49, 1, 19, 15.0000, '2026-06-15 09:29:12'),
(50, 1, 20, 0.0000, '2026-07-01 10:18:41'),
(51, 1, 21, 0.0000, '2026-06-04 09:49:14'),
(52, 1, 22, 0.0000, '2026-07-01 10:29:08'),
(53, 1, 23, 0.0000, '2026-06-13 09:13:51'),
(54, 1, 24, 0.0000, '2026-06-04 09:48:24'),
(55, 1, 25, 5.0000, '2026-06-04 09:46:28'),
(56, 1, 26, 0.0000, '2026-06-04 09:49:41'),
(57, 3, 24, 7.0000, '2026-06-04 09:48:21'),
(58, 8, 24, 8.0000, '2026-06-04 09:48:24'),
(59, 9, 21, 1.0000, '2026-06-15 09:47:56'),
(60, 9, 26, 2.0000, '2026-06-15 09:47:56'),
(61, 2, 21, 8.9980, '2026-06-30 09:47:05'),
(64, 2, 1, 96.0000, '2026-06-13 09:13:29'),
(67, 2, 2, 0.0000, '2026-07-11 18:38:42'),
(71, 2, 12, 0.9790, '2026-07-02 08:47:47'),
(79, 2, 3, 1.9600, '2026-07-02 08:47:47'),
(88, 2, 15, 2.8200, '2026-06-05 08:34:31'),
(89, 6, 21, 0.0020, '2026-06-30 09:47:05'),
(90, 6, 1, 4.0000, '2026-06-06 14:54:29'),
(91, 6, 2, 0.4600, '2026-07-02 08:47:47'),
(92, 6, 12, 0.0150, '2026-07-02 08:47:47'),
(93, 6, 14, 6.0000, '2026-07-02 08:47:47'),
(96, 6, 3, 0.1400, '2026-07-02 08:47:47'),
(97, 6, 15, 0.1800, '2026-06-05 08:34:31'),
(127, 1, 27, 20.0000, '2026-06-06 09:58:13'),
(128, 1, 28, 4.0000, '2026-06-06 10:08:11'),
(129, 1, 29, 0.0000, '2026-07-01 10:18:41'),
(130, 1, 30, 0.0000, '2026-06-26 18:59:46'),
(131, 1, 31, 0.0000, '2026-07-01 10:28:07'),
(132, 1, 32, 4.0000, '2026-06-06 09:58:13'),
(133, 3, 33, 0.0000, '2026-06-18 11:24:59'),
(134, 2, 13, 2.9800, '2026-06-26 18:46:08'),
(135, 2, 29, 8.0000, '2026-07-01 10:18:41'),
(136, 2, 30, 6.4000, '2026-06-30 17:47:18'),
(137, 2, 31, 0.0000, '2026-07-01 10:06:51'),
(139, 2, 28, 5.9600, '2026-06-15 14:14:03'),
(148, 6, 33, 0.0400, '2026-07-01 21:35:10'),
(150, 6, 28, 0.0200, '2026-06-06 14:54:29'),
(152, 2, 10, 1.9900, '2026-06-26 18:46:08'),
(153, 6, 9, 0.8000, '2026-06-26 18:46:08'),
(154, 6, 10, 0.0200, '2026-06-26 18:46:08'),
(155, 6, 13, 0.0200, '2026-06-26 18:46:08'),
(184, 2, 17, 22.2500, '2026-07-01 10:18:41'),
(193, 6, 17, 0.7500, '2026-06-26 19:19:14'),
(196, 2, 20, 8.0000, '2026-07-01 10:18:41'),
(197, 1, 34, 2.0000, '2026-07-01 10:34:19'),
(198, 2, 34, 0.0000, '2026-07-01 10:33:49'),
(204, 6, 34, 0.6000, '2026-06-13 12:31:48'),
(205, 1, 35, 0.0000, '2026-07-01 10:30:13'),
(206, 1, 36, 0.0000, '2026-07-01 10:18:41'),
(207, 1, 37, 0.0000, '2026-07-01 10:28:12'),
(208, 1, 38, 0.0000, '2026-07-01 10:25:45'),
(209, 3, 39, 0.0000, '2026-07-01 10:11:49'),
(210, 1, 40, 0.0000, '2026-07-01 10:22:37'),
(211, 1, 41, 0.0000, '2026-07-01 10:23:06'),
(212, 1, 42, 0.0000, '2026-07-01 10:28:07'),
(213, 2, 25, 2.0000, '2026-06-08 14:51:56'),
(214, 2, 18, 5.0000, '2026-07-01 10:23:06'),
(215, 2, 16, 8.0000, '2026-07-01 10:18:41'),
(217, 2, 11, 3.0000, '2026-07-01 10:22:34'),
(218, 2, 40, 8.0000, '2026-07-01 10:22:37'),
(219, 2, 7, 1.9980, '2026-06-26 19:38:57'),
(220, 2, 36, 8.0000, '2026-07-01 10:18:41'),
(221, 2, 8, 30.0000, '2026-06-15 09:47:56'),
(222, 2, 19, 5.0000, '2026-06-08 14:54:16'),
(223, 2, 35, 6.0000, '2026-07-01 21:35:10'),
(224, 9, 20, 0.0000, '2026-06-23 16:13:08'),
(225, 2, 41, 8.0000, '2026-07-01 10:23:06'),
(226, 2, 26, 5.0000, '2026-06-15 09:47:56'),
(227, 9, 6, 0.0000, '2026-06-15 09:47:56'),
(228, 2, 37, 8.0000, '2026-07-01 10:28:12'),
(229, 2, 5, 8.9400, '2026-06-26 21:38:54'),
(261, 7, 1, 0.0000, '2026-06-16 10:56:45'),
(263, 7, 23, 0.0000, '2026-06-16 10:56:45'),
(270, 6, 20, 0.2000, '2026-06-13 12:31:48'),
(271, 1, 44, 2.0000, '2026-06-23 14:40:10'),
(272, 1, 45, 0.0000, '2026-06-15 09:35:20'),
(273, 1, 46, 0.0000, '2026-06-15 09:35:17'),
(274, 1, 47, 2.0000, '2026-06-15 09:37:48'),
(275, 1, 48, 2.0000, '2026-06-15 09:35:24'),
(276, 1, 49, 2.0000, '2026-06-15 09:37:50'),
(277, 1, 50, 25.0000, '2026-06-15 09:37:50'),
(278, 1, 51, 2.0000, '2026-06-15 09:35:22'),
(286, 7, 18, 0.0000, '2026-06-26 18:50:37'),
(287, 7, 7, 0.0000, '2026-06-26 18:50:37'),
(288, 7, 19, 0.0000, '2026-06-16 10:56:45'),
(289, 7, 37, 0.0000, '2026-07-01 10:11:43'),
(291, 7, 22, 0.0000, '2026-07-01 10:11:43'),
(293, 2, 46, 10.0000, '2026-06-15 09:35:17'),
(294, 2, 42, 0.0000, '2026-07-01 10:06:44'),
(295, 2, 45, 10.0000, '2026-06-15 09:35:20'),
(296, 2, 51, 8.0000, '2026-06-15 09:35:22'),
(297, 2, 48, 8.0000, '2026-06-15 09:35:24'),
(298, 2, 22, 7.9600, '2026-07-01 21:35:10'),
(299, 2, 47, 8.0000, '2026-06-15 09:37:48'),
(300, 9, 49, 0.0000, '2026-06-15 09:47:56'),
(301, 9, 50, 10.0000, '2026-06-15 09:47:56'),
(302, 2, 6, 0.0000, '2026-06-18 18:58:50'),
(305, 2, 38, 8.0000, '2026-07-01 10:25:45'),
(306, 2, 50, 40.0000, '2026-06-15 09:47:56'),
(309, 2, 49, 7.9800, '2026-06-15 11:24:17'),
(313, 3, 84, 850.0000, '2026-06-15 11:24:17'),
(316, 3, 87, 990.0000, '2026-06-15 14:38:25'),
(317, 3, 88, 910.0000, '2026-06-15 14:38:25'),
(318, 3, 89, 960.0000, '2026-06-15 14:38:25'),
(319, 6, 87, 10.0000, '2026-06-15 14:38:25'),
(320, 6, 88, 90.0000, '2026-06-15 14:38:25'),
(321, 6, 89, 40.0000, '2026-06-15 14:38:25'),
(322, 6, 36, 0.0400, '2026-06-15 11:24:17'),
(323, 6, 37, 0.0100, '2026-06-15 11:24:17'),
(325, 6, 49, 0.0200, '2026-06-15 11:24:17'),
(326, 6, 85, 50.0000, '2026-06-15 11:24:17'),
(327, 6, 86, 1.0000, '2026-06-15 11:24:17'),
(328, 6, 84, 150.0000, '2026-06-15 11:24:17'),
(329, 3, 52, 7.0000, '2026-06-15 16:46:01'),
(330, 3, 53, 37.0000, '2026-06-15 16:46:01'),
(331, 3, 54, 54.9700, '2026-06-15 16:46:01'),
(332, 3, 55, 39.9800, '2026-06-15 16:46:01'),
(333, 3, 56, 37.0000, '2026-06-15 16:46:01'),
(334, 3, 57, 52.0000, '2026-06-15 14:34:21'),
(335, 3, 58, 52.0000, '2026-06-15 14:34:21'),
(336, 3, 59, 51.0000, '2026-06-15 14:34:21'),
(337, 3, 60, 150.0000, '2026-06-15 14:34:21'),
(338, 3, 61, 52.0000, '2026-06-15 14:34:21'),
(339, 3, 62, 52.0000, '2026-06-15 14:34:21'),
(340, 3, 63, 52.0000, '2026-06-15 14:34:21'),
(341, 3, 64, 51.0000, '2026-06-15 14:34:21'),
(342, 1, 65, 20.0000, '2026-06-23 16:10:47'),
(343, 1, 66, 0.0000, '2026-06-23 16:09:09'),
(344, 1, 67, 0.0000, '2026-06-23 16:06:42'),
(345, 1, 68, 0.0000, '2026-06-23 16:07:23'),
(346, 1, 69, 12.0000, '2026-06-23 16:17:45'),
(347, 1, 70, 0.0000, '2026-06-23 16:10:47'),
(348, 1, 71, 0.0000, '2026-06-23 16:11:22'),
(349, 1, 72, 0.0000, '2026-06-23 16:09:09'),
(350, 1, 73, 20.0000, '2026-06-23 16:19:03'),
(351, 1, 74, 12.0000, '2026-06-23 16:12:39'),
(352, 1, 75, 10.0000, '2026-06-23 16:10:08'),
(353, 1, 76, 0.0000, '2026-06-23 16:07:23'),
(354, 1, 77, 0.0000, '2026-06-23 16:06:42'),
(355, 1, 78, 0.0000, '2026-06-23 16:06:42'),
(356, 1, 79, 5.0000, '2026-06-23 16:10:08'),
(357, 1, 80, 11.0000, '2026-06-23 16:19:05'),
(358, 1, 81, 12.0000, '2026-06-23 16:11:24'),
(359, 1, 82, 12.0000, '2026-06-23 16:17:47'),
(360, 1, 83, 12.0000, '2026-06-23 16:10:47'),
(361, 3, 85, 950.0000, '2026-06-15 14:35:08'),
(362, 3, 86, 999.0000, '2026-06-15 14:35:08'),
(363, 6, 22, 0.0800, '2026-07-01 21:35:10'),
(365, 6, 35, 4.0000, '2026-07-01 21:35:10'),
(370, 6, 7, 0.0010, '2026-06-26 19:38:23'),
(371, 6, 6, 0.0000, '2026-06-17 20:24:42'),
(372, 6, 11, 0.2000, '2026-06-26 19:38:23'),
(376, 6, 30, 1.2000, '2026-06-30 17:46:51'),
(377, 6, 29, 0.6000, '2026-06-30 17:46:51'),
(381, 2, 55, 12.0000, '2026-06-15 14:57:07'),
(386, 6, 42, 0.2000, '2026-06-26 19:11:58'),
(387, 6, 41, 0.2400, '2026-06-26 19:11:58'),
(388, 6, 5, 0.7600, '2026-06-26 21:35:17'),
(389, 6, 55, 0.0200, '2026-06-15 16:46:01'),
(390, 6, 56, 15.0000, '2026-06-15 16:46:01'),
(391, 6, 52, 45.0000, '2026-06-15 16:46:01'),
(392, 6, 53, 15.0000, '2026-06-15 16:46:01'),
(393, 6, 54, 0.0500, '2026-06-15 16:46:01'),
(433, 7, 38, 0.0000, '2026-07-01 10:11:43'),
(434, 5, 90, 300.0000, '2026-06-16 11:05:24'),
(435, 5, 91, 500.0000, '2026-06-16 11:05:24'),
(436, 5, 92, 1000.0000, '2026-06-16 11:05:24'),
(437, 5, 93, 500.0000, '2026-06-16 11:05:24'),
(438, 5, 94, 800.0000, '2026-06-16 11:05:24'),
(439, 5, 95, 150.0000, '2026-06-16 11:05:24'),
(440, 5, 96, 4000.0000, '2026-06-16 11:05:24'),
(441, 7, 40, 0.0000, '2026-07-01 10:11:43'),
(443, 7, 33, 0.0000, '2026-07-01 10:11:43'),
(447, 1, 33, 2.0000, '2026-07-01 10:18:41'),
(448, 7, 6, 0.0000, '2026-06-18 18:58:57'),
(451, 7, 10, 0.0000, '2026-06-23 16:13:17'),
(453, 7, 30, 0.0000, '2026-06-26 18:50:37'),
(470, 2, 44, 8.0000, '2026-06-23 14:40:10'),
(475, 6, 38, 0.0500, '2026-06-23 14:40:16'),
(476, 9, 77, 0.0000, '2026-06-23 16:16:09'),
(477, 9, 78, 0.0000, '2026-06-23 16:16:09'),
(478, 9, 67, 0.0000, '2026-06-23 16:12:35'),
(479, 9, 76, 0.0000, '2026-06-23 16:12:37'),
(480, 9, 68, 550.0000, '2026-06-23 16:07:23'),
(481, 9, 40, 0.0000, '2026-06-23 16:14:26'),
(482, 7, 20, 0.0000, '2026-07-01 10:04:29'),
(487, 9, 66, 0.0000, '2026-07-01 10:22:32'),
(488, 9, 72, 0.0000, '2026-07-01 10:22:29'),
(489, 9, 33, 0.0000, '2026-06-25 20:07:51'),
(492, 2, 75, 50.0000, '2026-06-23 16:10:08'),
(493, 2, 79, 50.0000, '2026-06-23 16:10:08'),
(494, 2, 70, 150.0000, '2026-06-23 16:10:47'),
(495, 2, 65, 80.0000, '2026-06-23 16:10:47'),
(496, 2, 83, 40.0000, '2026-06-23 16:10:47'),
(497, 2, 71, 250.0000, '2026-06-23 16:11:22'),
(498, 2, 81, 40.0000, '2026-06-23 16:11:24'),
(499, 2, 67, 100.0000, '2026-06-23 16:12:35'),
(500, 2, 76, 250.0000, '2026-06-23 16:12:37'),
(501, 2, 74, 40.0000, '2026-06-23 16:12:39'),
(507, 2, 78, 250.0000, '2026-06-23 16:16:09'),
(508, 2, 77, 250.0000, '2026-06-23 16:16:09'),
(510, 2, 69, 40.0000, '2026-06-23 16:17:45'),
(511, 2, 82, 40.0000, '2026-06-23 16:17:47'),
(512, 2, 73, 50.0000, '2026-06-23 16:19:03'),
(513, 2, 80, 40.0000, '2026-06-23 16:19:05'),
(519, 2, 33, 5.9900, '2026-07-01 21:35:10'),
(531, 7, 36, 0.0000, '2026-07-01 10:04:29'),
(549, 6, 31, 0.4000, '2026-06-26 19:30:54'),
(550, 6, 16, 0.2000, '2026-06-26 19:30:54'),
(558, 7, 3, 0.0000, '2026-07-01 10:11:43'),
(596, 1, 97, 5.0000, '2026-07-01 10:28:12'),
(597, 1, 98, 10.0000, '2026-07-01 10:20:50'),
(598, 1, 99, 10.0000, '2026-07-01 10:20:47'),
(599, 7, 34, 4.0000, '2026-07-01 10:33:49'),
(605, 7, 11, 0.0000, '2026-07-01 10:11:43'),
(608, 7, 16, 0.0000, '2026-07-01 10:11:43'),
(610, 7, 41, 0.0000, '2026-07-01 10:11:43'),
(614, 7, 35, 0.0000, '2026-07-01 10:11:43'),
(616, 7, 42, 0.0000, '2026-07-01 10:11:43'),
(617, 7, 31, 0.0000, '2026-07-01 10:11:43'),
(621, 7, 2, 10.9400, '2026-07-11 18:47:02'),
(624, 7, 29, 0.0000, '2026-07-01 10:11:43'),
(628, 7, 39, 0.0000, '2026-07-01 10:11:52'),
(639, 1, 39, 2.0000, '2026-07-01 10:22:40'),
(655, 2, 99, 10.0000, '2026-07-01 10:20:47'),
(656, 2, 98, 10.0000, '2026-07-01 10:20:50'),
(657, 2, 72, 250.0000, '2026-07-01 10:22:29'),
(658, 2, 66, 150.0000, '2026-07-01 10:22:32'),
(661, 2, 39, 6.0000, '2026-07-01 10:22:40'),
(667, 9, 31, 8.0000, '2026-07-01 10:28:07'),
(668, 9, 42, 8.0000, '2026-07-01 10:28:07'),
(669, 2, 97, 15.0000, '2026-07-01 10:28:12');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transfers`
--

DROP TABLE IF EXISTS `inventory_transfers`;
CREATE TABLE `inventory_transfers` (
  `id` int(11) NOT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `transfer_date` datetime DEFAULT current_timestamp(),
  `note` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_transfers`
--

INSERT INTO `inventory_transfers` (`id`, `from_warehouse_id`, `to_warehouse_id`, `performed_by`, `transfer_date`, `note`, `status`, `approved_by`, `approved_at`) VALUES
(1, 1, 3, 'Admin', '2026-05-06 14:17:26', 'Chuyển kho nội bộ', 'completed', 'Admin', '2026-05-07 09:55:23'),
(2, 1, 3, 'Admin', '2026-05-07 09:52:53', 'Yêu cầu chuyển kho nội bộ', 'completed', 'Admin', '2026-05-07 09:55:19'),
(3, 1, 2, 'Admin', '2026-05-07 14:39:15', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-05-07 14:39:21'),
(4, 1, 3, 'Admin', '2026-05-07 14:45:07', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-07 14:45:20'),
(5, 1, 3, 'Admin', '2026-05-09 10:51:42', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', 'Admin', '2026-05-09 11:22:07'),
(6, 1, 3, 'Admin', '2026-05-09 11:22:03', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', 'Admin', '2026-05-09 11:22:07'),
(7, 1, 3, 'Admin', '2026-05-09 13:41:38', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', NULL, NULL),
(8, 1, 2, 'Admin', '2026-05-09 13:42:09', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', NULL, NULL),
(9, 1, 3, 'Admin', '2026-05-09 13:52:16', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-09 13:52:19'),
(10, 1, 3, 'Admin', '2026-05-09 14:18:19', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-09 14:18:24'),
(11, 3, 2, 'Admin', '2026-05-09 14:18:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-09 14:18:56'),
(12, 1, 2, 'Admin', '2026-05-21 10:49:10', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-21 10:49:15'),
(13, 1, 2, 'Admin', '2026-05-21 11:00:12', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-21 11:00:34'),
(14, 2, 7, 'Admin', '2026-05-21 11:06:35', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-21 11:06:44'),
(15, 1, 2, 'Admin', '2026-05-23 10:57:38', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 10:57:42'),
(16, 1, 2, 'Admin', '2026-05-23 10:59:14', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 10:59:19'),
(17, 1, 2, 'Admin', '2026-05-23 11:00:27', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 11:00:59'),
(18, 1, 4, 'Admin', '2026-05-23 11:00:37', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 11:00:52'),
(19, 1, 2, 'Admin', '2026-05-23 13:51:10', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 13:51:18'),
(20, 1, 7, 'Admin', '2026-05-23 14:57:02', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 14:57:07'),
(21, 1, 7, 'Admin', '2026-05-23 14:59:26', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 15:07:12'),
(22, 1, 2, 'Admin', '2026-05-23 15:17:56', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-23 15:18:55'),
(23, 1, 2, 'Admin', '2026-05-24 19:41:01', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-24 19:42:09'),
(24, 1, 4, 'Admin', '2026-05-26 14:02:17', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-26 14:02:22'),
(25, 1, 4, 'Admin', '2026-05-26 16:24:29', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-26 16:24:37'),
(26, 1, 7, 'Admin', '2026-06-02 18:47:21', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-02 18:47:59'),
(27, 1, 7, 'Admin', '2026-06-02 18:47:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-02 18:47:57'),
(28, 1, 2, 'Admin', '2026-06-02 21:11:03', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-02 21:11:26'),
(29, 1, 4, 'Admin', '2026-06-02 21:11:18', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-02 21:11:29'),
(30, 4, 8, 'Admin', '2026-06-02 21:11:41', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-02 21:11:44'),
(31, 1, 8, 'Admin', '2026-06-02 21:12:15', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-02 21:12:32'),
(32, 1, 2, 'Admin', '2026-06-04 09:11:33', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:12:41'),
(33, 1, 8, 'Admin', '2026-06-04 09:12:05', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:12:38'),
(34, 1, 2, 'Admin', '2026-06-04 09:13:19', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:14:11'),
(35, 1, 8, 'Admin', '2026-06-04 09:13:42', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:14:07'),
(36, 1, 8, 'Admin', '2026-06-04 09:13:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:14:04'),
(37, 1, 9, 'Admin', '2026-06-04 09:32:50', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:34:18'),
(38, 1, 9, 'Admin', '2026-06-04 09:33:01', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:34:28'),
(39, 1, 9, 'Admin', '2026-06-04 09:34:00', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:34:21'),
(40, 1, 9, 'Admin', '2026-06-04 09:34:08', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:34:19'),
(41, 1, 9, 'Admin', '2026-06-04 09:39:18', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:39:38'),
(42, 1, 3, 'Admin', '2026-06-04 09:48:07', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:48:21'),
(43, 1, 8, 'Admin', '2026-06-04 09:48:17', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:48:24'),
(44, 1, 9, 'Admin', '2026-06-04 09:49:08', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:49:14'),
(45, 1, 9, 'Admin', '2026-06-04 09:49:32', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 09:49:41'),
(46, 9, 2, 'Admin', '2026-06-04 10:01:16', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 10:01:20'),
(47, 1, 2, 'Admin', '2026-06-04 10:01:58', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 10:02:01'),
(48, 9, 2, 'Admin', '2026-06-04 10:02:22', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 10:02:27'),
(49, 9, 2, 'Admin', '2026-06-04 10:02:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 10:02:57'),
(50, 1, 2, 'Admin', '2026-06-04 10:03:46', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 10:03:51'),
(51, 1, 2, 'Admin', '2026-06-04 10:04:08', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-04 10:04:12'),
(52, 1, 2, 'Admin', '2026-06-06 10:05:18', 'Yêu cầu chuyển kho nội bộ (5 mặt hàng)', 'completed', 'Admin', '2026-06-06 10:05:27'),
(53, 1, 2, 'Admin', '2026-06-06 10:08:07', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-06 10:08:11'),
(54, 9, 2, 'Admin', '2026-06-06 15:00:15', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-06 15:00:22'),
(55, 1, 2, 'Admin', '2026-06-08 10:34:16', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 10:34:21'),
(56, 1, 2, 'Admin', '2026-06-08 11:39:50', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 11:39:55'),
(57, 1, 2, 'Admin', '2026-06-08 13:32:00', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 13:32:04'),
(58, 1, 2, 'Admin', '2026-06-08 14:50:44', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:52:09'),
(59, 1, 2, 'Admin', '2026-06-08 14:50:53', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:52:07'),
(60, 1, 2, 'Admin', '2026-06-08 14:51:02', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:52:05'),
(61, 1, 2, 'Admin', '2026-06-08 14:51:11', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:52:03'),
(62, 1, 2, 'Admin', '2026-06-08 14:51:34', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:52:01'),
(63, 1, 2, 'Admin', '2026-06-08 14:51:41', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:51:59'),
(64, 1, 2, 'Admin', '2026-06-08 14:51:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:51:56'),
(65, 9, 2, 'Admin', '2026-06-08 14:52:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:53:08'),
(66, 1, 2, 'Admin', '2026-06-08 14:53:02', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:53:06'),
(67, 1, 2, 'Admin', '2026-06-08 14:53:22', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:54:23'),
(68, 1, 9, 'Admin', '2026-06-08 14:53:36', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:54:21'),
(69, 1, 2, 'Admin', '2026-06-08 14:53:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:54:16'),
(70, 1, 2, 'Admin', '2026-06-08 14:54:09', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:54:18'),
(71, 1, 9, 'Admin', '2026-06-08 14:54:44', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:55:09'),
(72, 9, 2, 'Admin', '2026-06-08 14:55:03', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:55:07'),
(73, 1, 2, 'Admin', '2026-06-08 14:55:23', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:55:47'),
(74, 1, 2, 'Admin', '2026-06-08 14:55:38', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-08 14:55:44'),
(75, 1, 2, 'Admin', '2026-06-15 09:33:42', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:35:24'),
(76, 1, 2, 'Admin', '2026-06-15 09:33:58', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:35:22'),
(77, 1, 2, 'Admin', '2026-06-15 09:34:23', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:35:20'),
(78, 1, 2, 'Admin', '2026-06-15 09:35:11', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:35:17'),
(79, 1, 2, 'Admin', '2026-06-15 09:35:52', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:37:51'),
(80, 1, 2, 'Admin', '2026-06-15 09:36:17', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'cancelled', NULL, NULL),
(81, 1, 2, 'Admin', '2026-06-15 09:37:03', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:37:48'),
(82, 1, 9, 'Admin', '2026-06-15 09:37:40', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:37:50'),
(83, 1, 2, 'Admin', '2026-06-15 09:38:41', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:38:45'),
(84, 9, 2, 'Admin', '2026-06-15 09:47:52', 'Yêu cầu chuyển kho nội bộ (7 mặt hàng)', 'completed', 'Admin', '2026-06-15 09:47:56'),
(85, 3, 2, 'Admin', '2026-06-15 14:57:04', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-15 14:57:07'),
(86, 1, 7, 'Admin', '2026-06-16 10:57:59', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-16 10:58:02'),
(87, 1, 2, 'Admin', '2026-06-23 14:36:47', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 14:36:51'),
(88, 1, 2, 'Admin', '2026-06-23 14:40:07', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-06-23 14:40:10'),
(89, 1, 9, 'Admin', '2026-06-23 16:06:39', 'Yêu cầu chuyển kho nội bộ (3 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:06:42'),
(90, 1, 9, 'Admin', '2026-06-23 16:07:18', 'Yêu cầu chuyển kho nội bộ (3 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:07:23'),
(91, 1, 9, 'Admin', '2026-06-23 16:09:05', 'Yêu cầu chuyển kho nội bộ (3 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:09:09'),
(92, 1, 9, 'Admin', '2026-06-23 16:09:29', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'cancelled', NULL, NULL),
(93, 1, 2, 'Admin', '2026-06-23 16:10:03', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:10:08'),
(94, 1, 2, 'Admin', '2026-06-23 16:10:42', 'Yêu cầu chuyển kho nội bộ (3 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:10:47'),
(95, 1, 2, 'Admin', '2026-06-23 16:11:00', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:11:24'),
(96, 1, 2, 'Admin', '2026-06-23 16:11:18', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:11:22'),
(97, 1, 2, 'Admin', '2026-06-23 16:11:42', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:12:39'),
(98, 9, 2, 'Admin', '2026-06-23 16:12:11', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:12:37'),
(99, 9, 2, 'Admin', '2026-06-23 16:12:29', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:12:35'),
(100, 9, 2, 'Admin', '2026-06-23 16:14:21', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:14:26'),
(101, 9, 2, 'Admin', '2026-06-23 16:16:06', 'Yêu cầu chuyển kho nội bộ (3 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:16:09'),
(102, 1, 2, 'Admin', '2026-06-23 16:17:09', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:17:47'),
(103, 1, 2, 'Admin', '2026-06-23 16:17:39', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:17:45'),
(104, 1, 2, 'Admin', '2026-06-23 16:18:17', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:19:05'),
(105, 1, 2, 'Admin', '2026-06-23 16:18:57', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-23 16:19:03'),
(106, 9, 2, 'Admin', '2026-06-25 20:07:48', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-25 20:07:51'),
(107, 1, 2, 'Admin', '2026-06-26 18:46:01', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-26 18:46:03'),
(108, 1, 2, 'Admin', '2026-06-26 18:59:41', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-26 18:59:46'),
(109, 1, 2, 'Admin', '2026-06-26 19:38:14', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-26 19:38:18'),
(110, 1, 2, 'Admin', '2026-06-30 09:46:54', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-06-30 09:46:58'),
(111, 1, 2, 'Admin', '2026-07-01 10:18:37', 'Yêu cầu chuyển kho nội bộ (8 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:18:41'),
(112, 1, 2, 'Admin', '2026-07-01 10:20:26', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:20:47'),
(113, 1, 2, 'Admin', '2026-07-01 10:20:40', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:20:50'),
(114, 1, 2, 'Admin', '2026-07-01 10:21:11', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:22:40'),
(115, 1, 2, 'Admin', '2026-07-01 10:21:39', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:22:37'),
(116, 1, 2, 'Admin', '2026-07-01 10:21:47', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:22:34'),
(117, 9, 2, 'Admin', '2026-07-01 10:22:10', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:22:32'),
(118, 9, 2, 'Admin', '2026-07-01 10:22:23', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:22:29'),
(119, 1, 2, 'Admin', '2026-07-01 10:23:02', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:23:06'),
(120, 1, 2, 'Admin', '2026-07-01 10:24:19', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:25:45'),
(121, 1, 2, 'Admin', '2026-07-01 10:27:38', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:28:12'),
(122, 1, 9, 'Admin', '2026-07-01 10:28:03', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:28:07'),
(123, 1, 2, 'Admin', '2026-07-01 10:28:47', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:29:08'),
(124, 1, 2, 'Admin', '2026-07-01 10:29:00', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:29:05'),
(125, 1, 2, 'Admin', '2026-07-01 10:30:04', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-07-01 10:30:13');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_units`
--

DROP TABLE IF EXISTS `inventory_units`;
CREATE TABLE `inventory_units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_units`
--

INSERT INTO `inventory_units` (`id`, `name`) VALUES
(1, 'kg'),
(2, 'gram'),
(3, 'lít'),
(5, 'cái'),
(6, 'chai'),
(7, 'con'),
(8, 'Viên'),
(9, 'ml'),
(10, 'lá');

-- --------------------------------------------------------

--
-- Table structure for table `milestones`
--

DROP TABLE IF EXISTS `milestones`;
CREATE TABLE `milestones` (
  `id` int(11) NOT NULL,
  `type` enum('visit','spend') NOT NULL,
  `threshold` int(11) NOT NULL,
  `reward_title` varchar(255) NOT NULL,
  `reward_desc` text DEFAULT NULL,
  `discount_percent` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `milestones`
--

INSERT INTO `milestones` (`id`, `type`, `threshold`, `reward_title`, `reward_desc`, `discount_percent`, `created_at`) VALUES
(1, 'visit', 3, 'Ly Champagne Chào Mừng', 'Tặng 1 ly Champagne cao cấp khi khách vừa ngồi vào bàn, kèm lời chúc.', 0, '2026-06-29 18:04:01'),
(2, 'visit', 5, 'Món Tráng Miệng Signature', 'Bếp trưởng trực tiếp ra bàn gửi lời chào và tặng một phần tráng miệng đặc biệt không có trong Menu.', 0, '2026-06-29 18:04:01'),
(3, 'visit', 10, 'Private Tasting', 'Tặng thư mời tham gia buổi thử nếm Private Tasting menu mùa mới cùng Bếp Trưởng.', 0, '2026-06-29 18:04:01'),
(4, 'spend', 10000000, 'Tăng mã giãm giá', 'ưu đãi giảm giá 10% khi khách hãng đạt được cột mốc', 10, '2026-06-30 09:48:43'),
(5, 'visit', 7, 'Đĩa Phô Mai Khai Vị', 'Tặng 1 đĩa phô mai tổng hợp nhập khẩu kèm mứt trái cây và bánh quy thủ công.', 0, '2026-07-01 19:18:27'),
(6, 'visit', 12, 'Đặc Quyền Chỗ Ngồi', 'Luôn được ưu tiên sắp xếp các vị trí bàn đẹp nhất, riêng tư nhất kể cả khi đặt sát giờ.', 0, '2026-07-01 19:18:27'),
(7, 'visit', 15, 'Tặng Rượu Vang', 'Tặng 1 chai vang đỏ hoặc vang trắng cao cấp (Sommelier Selection) trị giá 1.500.000 VND.', 0, '2026-07-01 19:18:27'),
(8, 'visit', 20, 'Chef\'s Table Upgrade', 'Được nâng cấp miễn phí lên trải nghiệm ăn trực tiếp tại quầy Chef\'s Table ngắm bếp trưởng nấu ăn.', 0, '2026-07-01 19:18:27'),
(9, 'visit', 25, 'Tráng Miệng Vàng 24K', 'Tặng món tráng miệng Signature được phủ vàng lá nguyên chất 24K vô cùng đẳng cấp.', 0, '2026-07-01 19:18:27'),
(10, 'visit', 30, 'Đưa Đón Limousine', 'Miễn phí dịch vụ đưa hoặc đón bằng xe Limousine hạng sang trong bán kính 10km.', 0, '2026-07-01 19:18:27'),
(11, 'visit', 40, 'Diamond Tier (Giảm 15%)', 'Chính thức thăng hạng Kim Cương, nhận ưu đãi giảm giá cố định 15% cho mọi hóa đơn.', 15, '2026-07-01 19:18:27'),
(12, 'visit', 50, 'Bữa Tối Tri Ân', 'Tặng 1 bữa tối set menu cao cấp hoàn toàn miễn phí dành cho 2 người vào dịp đặc biệt.', 0, '2026-07-01 19:18:27'),
(13, 'spend', 20000000, 'Set Quà Gia Vị Thượng Hạng', 'Tặng set quà gồm muối biển Truffle và dầu Olive nấm cục cao cấp mang về.', 0, '2026-07-01 19:18:27'),
(14, 'spend', 30000000, 'Signature Cocktails', 'Miễn phí 1 vòng Cocktail Signature do Mixologist pha chế tại bàn (Tối đa 4 ly).', 0, '2026-07-01 19:18:27'),
(15, 'spend', 50000000, 'Vé Masterclass Nấu Ăn', 'Tặng 1 vé tham dự lớp học nấu món Âu (Masterclass) độc quyền do chính Bếp trưởng giảng dạy.', 0, '2026-07-01 19:18:27'),
(16, 'spend', 80000000, 'Kỷ niệm thành lập', 'tăng 1 thẻ quà tặng giảm giá 20 khi đến nhà hàng', 20, '2026-07-01 19:18:27'),
(18, 'spend', 150000000, 'Voucher Nghỉ Dưỡng', 'Tặng 1 đêm nghỉ dưỡng hạng sang tại Resort 5 sao đối tác của nhà hàng.', 0, '2026-07-01 19:18:27'),
(19, 'spend', 200000000, 'Thẻ Tôn Vinh (Giảm 20%)', 'Thẻ thành viên tối thượng, ưu đãi giảm trực tiếp 20% vĩnh viễn trên mọi hóa đơn.', 20, '2026-07-01 19:18:27'),
(20, 'spend', 300000000, 'Tài Trợ Gói Tiệc', 'Nhà hàng tài trợ toàn bộ gói Trang Trí Bespoke trị giá 10.000.000 VND cho 1 sự kiện bất kỳ của bạn.', 0, '2026-07-01 19:18:27');

-- --------------------------------------------------------

--
-- Table structure for table `navigation_menu`
--

DROP TABLE IF EXISTS `navigation_menu`;
CREATE TABLE `navigation_menu` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `navigation_menu`
--

INSERT INTO `navigation_menu` (`id`, `title`, `url`, `position`) VALUES
(1, 'Trang chủ', '/', 1),
(2, 'Giới thiệu', '/about.php', 2),
(3, 'Thực đơn', '/menu.php', 3),
(4, 'Đặt bàn', '/booking_service.php', 4),
(5, 'Liên hệ', '/contact.php', 5);

-- --------------------------------------------------------

--
-- Table structure for table `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
CREATE TABLE `newsletters` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_item_toppings`
--

DROP TABLE IF EXISTS `order_item_toppings`;
CREATE TABLE `order_item_toppings` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `topping_id` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_item_toppings`
--

INSERT INTO `order_item_toppings` (`id`, `order_item_id`, `topping_id`, `price`) VALUES
(5, 51, 7, 0.00),
(6, 51, 8, 0.00),
(7, 52, 7, 0.00),
(8, 52, 8, 0.00),
(9, 53, 18, 80000.00),
(10, 54, 6, 0.00),
(11, 54, 9, 0.00),
(12, 56, 1, 0.00),
(13, 56, 19, 15000.00),
(14, 56, 11, 30000.00),
(1012, 56, 7, 0.00),
(1013, 1068, 17, 20000.00),
(1014, 1085, 5, 0.00),
(1015, 1086, 5, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `base_salary` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `position_name`, `base_salary`) VALUES
(9, 'Quản lý', 0),
(10, 'Bếp trưởng', 0),
(11, 'Đầu bếp', 0),
(12, 'Phụ bếp', 0),
(13, 'Phục vụ', 0),
(14, 'Thu ngân', 0),
(15, 'Tạp vụ', 0),
(16, 'Pha chế', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pos_orders`
--

DROP TABLE IF EXISTS `pos_orders`;
CREATE TABLE `pos_orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `status` enum('open','paid','cancelled') DEFAULT 'open',
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` enum('cash','transfer') DEFAULT 'cash',
  `booking_id` int(11) DEFAULT NULL,
  `deposit_amount` decimal(15,2) DEFAULT 0.00,
  `guests` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_orders`
--

INSERT INTO `pos_orders` (`id`, `table_id`, `status`, `total_amount`, `created_at`, `updated_at`, `payment_method`, `booking_id`, `deposit_amount`, `guests`) VALUES
(1, 1, 'cancelled', 0.00, '2026-06-11 13:24:13', '2026-06-11 13:29:25', 'cash', NULL, 0.00, 1),
(2, 1, 'paid', 850000.00, '2026-06-11 13:36:16', '2026-06-11 13:36:24', 'cash', NULL, 0.00, 1),
(3, 1, 'paid', 850000.00, '2026-06-11 13:36:37', '2026-06-11 13:36:41', 'cash', NULL, 0.00, 1),
(4, 3, 'paid', 65000.00, '2026-06-11 13:36:44', '2026-06-11 13:36:49', 'cash', NULL, 0.00, 1),
(5, 1, 'cancelled', 0.00, '2026-06-11 13:48:01', '2026-06-11 13:48:23', 'cash', NULL, 0.00, 1),
(6, 1, 'cancelled', 0.00, '2026-06-11 13:50:44', '2026-06-11 13:57:21', 'cash', NULL, 0.00, 1),
(7, 1, 'cancelled', 0.00, '2026-06-11 13:57:24', '2026-06-11 13:59:00', 'cash', NULL, 0.00, 1),
(8, 1, 'cancelled', 0.00, '2026-06-11 14:00:42', '2026-06-11 14:00:59', 'cash', NULL, 0.00, 1),
(9, 1, 'cancelled', 0.00, '2026-06-11 14:05:06', '2026-06-11 14:05:46', 'cash', NULL, 0.00, 1),
(10, 1, 'cancelled', 0.00, '2026-06-11 14:07:47', '2026-06-11 14:19:07', 'cash', NULL, 0.00, 1),
(11, 1, 'cancelled', 0.00, '2026-06-11 14:19:13', '2026-06-11 14:22:02', 'cash', NULL, 0.00, 1),
(12, 1, 'cancelled', 0.00, '2026-06-12 02:32:28', '2026-06-12 02:38:44', 'cash', NULL, 0.00, 1),
(13, 1, 'paid', 195000.00, '2026-06-12 02:38:51', '2026-06-12 02:45:02', 'cash', NULL, 0.00, 1),
(14, 1, 'cancelled', 0.00, '2026-06-12 02:46:45', '2026-06-12 02:52:04', 'cash', NULL, 0.00, 1),
(15, 1, 'cancelled', 0.00, '2026-06-12 02:57:50', '2026-06-12 02:57:54', 'cash', NULL, 0.00, 1),
(16, 1, 'paid', 400000.00, '2026-06-12 03:07:11', '2026-06-12 03:08:05', 'transfer', 87, 120000.00, 1),
(17, 1, 'paid', 220000.00, '2026-06-15 04:24:33', '2026-06-16 07:05:26', 'cash', NULL, 0.00, 1),
(18, 2, 'paid', 1200000.00, '2026-06-15 07:06:42', '2026-06-15 07:06:46', 'cash', NULL, 0.00, 1),
(19, 2, 'paid', 1200000.00, '2026-06-15 07:06:59', '2026-06-15 07:07:02', 'transfer', NULL, 0.00, 1),
(20, 2, 'paid', 1200000.00, '2026-06-15 07:07:21', '2026-06-15 07:47:01', 'cash', NULL, 0.00, 1),
(21, 4, 'cancelled', 0.00, '2026-06-15 07:13:54', '2026-06-15 07:50:26', 'cash', NULL, 0.00, 1),
(22, 1, 'paid', 795000.00, '2026-06-15 07:43:57', '2026-06-15 07:46:12', 'cash', 96, 238500.00, 1),
(23, 1, 'cancelled', 0.00, '2026-06-15 07:52:09', '2026-06-15 07:52:15', 'cash', NULL, 0.00, 1),
(24, 1, 'paid', 400000.00, '2026-06-15 07:56:00', '2026-06-15 08:04:00', 'cash', 98, 120000.00, 1),
(1017, 1, 'paid', 220000.00, '2026-06-15 04:24:33', '2026-06-15 07:38:54', 'transfer', NULL, 0.00, 1),
(1018, 1, 'paid', 2050000.00, '2026-06-21 15:22:38', '2026-06-21 15:23:01', 'cash', NULL, 0.00, 1),
(1019, 4, 'cancelled', 0.00, '2026-06-21 15:22:47', '2026-06-22 07:15:44', 'cash', NULL, 0.00, 1),
(1020, 4, 'cancelled', 0.00, '2026-06-22 07:33:30', '2026-06-22 07:33:39', 'cash', NULL, 0.00, 1),
(1021, 4, 'paid', 165000.00, '2026-06-23 07:41:32', '2026-06-23 07:41:40', 'cash', 1102, 44550.00, 1),
(1022, 14, 'paid', 400000.00, '2026-06-25 12:18:35', '2026-06-25 12:40:56', 'cash', 1103, 108000.00, 1),
(1023, 14, 'paid', 850000.00, '2026-06-25 13:06:54', '2026-06-26 11:43:09', 'cash', 1104, 229500.00, 1),
(1024, 14, 'paid', 220000.00, '2026-06-26 11:46:17', '2026-06-26 12:01:37', 'cash', 1105, 59400.00, 1),
(1025, 14, 'cancelled', 0.00, '2026-06-26 12:16:41', '2026-06-26 12:17:02', 'cash', 1107, 45000.00, 1),
(1026, 14, 'cancelled', 0.00, '2026-06-26 12:17:58', '2026-06-26 12:18:14', 'cash', NULL, 0.00, 1),
(1027, 14, 'cancelled', 0.00, '2026-06-26 12:19:37', '2026-06-26 12:24:56', 'cash', 1108, 54000.00, 1),
(1028, 14, 'paid', 750000.00, '2026-06-26 12:34:03', '2026-06-26 12:40:11', 'cash', 1109, 225000.00, 1),
(1029, 4, 'paid', 145000.00, '2026-06-26 12:38:48', '2026-06-26 12:39:58', 'cash', 1110, 43500.00, 1),
(1030, 4, 'paid', 400000.00, '2026-06-26 12:42:54', '2026-06-26 12:43:17', 'cash', NULL, 0.00, 1),
(1031, 4, 'cancelled', 0.00, '2026-06-26 12:44:02', '2026-06-26 12:44:10', 'cash', NULL, 0.00, 1),
(1032, 4, 'cancelled', 0.00, '2026-06-26 13:48:18', '2026-06-26 13:50:00', 'cash', NULL, 0.00, 1),
(1033, 14, 'paid', 195000.00, '2026-06-26 14:36:20', '2026-06-26 14:39:04', 'cash', 1111, 58500.00, 1),
(1034, 14, 'paid', 650000.00, '2026-06-30 10:47:05', '2026-06-30 10:47:28', 'transfer', 1115, 195000.00, 1),
(1035, 14, 'paid', 400000.00, '2026-07-02 01:44:11', '2026-07-02 01:44:42', 'transfer', 1122, 120000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pos_order_items`
--

DROP TABLE IF EXISTS `pos_order_items`;
CREATE TABLE `pos_order_items` (
  `id` int(11) NOT NULL,
  `pos_order_id` int(11) NOT NULL,
  `item_type` enum('food','combo') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending','preparing','cooking','ready','served') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_order_items`
--

INSERT INTO `pos_order_items` (`id`, `pos_order_id`, `item_type`, `item_id`, `quantity`, `price`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(4, 2, 'combo', 1, 1, 850000.00, NULL, 'ready', '2026-06-11 13:36:16', '2026-06-11 13:56:59'),
(5, 3, 'combo', 1, 1, 850000.00, NULL, 'ready', '2026-06-11 13:36:37', '2026-06-11 13:56:57'),
(6, 4, 'food', 7, 1, 65000.00, NULL, 'ready', '2026-06-11 13:36:44', '2026-06-11 13:56:56'),
(19, 13, 'food', 9, 1, 195000.00, NULL, 'ready', '2026-06-12 02:38:51', '2026-06-12 02:44:54'),
(23, 16, 'food', 1, 1, 400000.00, NULL, 'ready', '2026-06-12 03:07:11', '2026-06-12 03:12:20'),
(24, 17, 'food', 4, 1, 220000.00, '', 'draft', '2026-06-15 04:24:33', '2026-06-15 04:24:33'),
(25, 18, 'combo', 2, 1, 1200000.00, '', 'draft', '2026-06-15 07:06:42', '2026-06-15 07:06:42'),
(26, 19, 'combo', 2, 1, 1200000.00, '', 'draft', '2026-06-15 07:06:59', '2026-06-15 07:06:59'),
(27, 20, 'combo', 2, 1, 1200000.00, '', 'ready', '2026-06-15 07:07:21', '2026-06-15 07:07:55'),
(29, 22, 'food', 8, 1, 145000.00, NULL, 'served', '2026-06-15 07:43:57', '2026-06-15 09:50:03'),
(30, 22, 'food', 13, 1, 650000.00, NULL, 'ready', '2026-06-15 07:43:57', '2026-06-15 09:46:12'),
(32, 24, 'food', 5, 1, 150000.00, NULL, 'draft', '2026-06-15 07:56:00', '2026-06-15 07:56:00'),
(33, 24, 'food', 20, 1, 250000.00, NULL, 'draft', '2026-06-15 07:56:00', '2026-06-15 07:56:00'),
(34, 1018, 'combo', 1, 1, 850000.00, '', 'pending', '2026-06-21 15:22:38', '2026-06-21 15:22:44'),
(35, 1018, 'combo', 2, 1, 1200000.00, '', 'pending', '2026-06-21 15:22:40', '2026-06-21 15:22:44'),
(40, 1021, 'food', 8, 1, 165000.00, NULL, 'draft', '2026-06-23 07:41:32', '2026-06-23 07:41:32'),
(41, 1022, 'food', 1, 1, 400000.00, NULL, 'served', '2026-06-25 12:18:35', '2026-06-25 12:35:33'),
(42, 1023, 'food', 12, 1, 850000.00, NULL, 'served', '2026-06-25 13:06:54', '2026-06-26 11:43:06'),
(43, 1024, 'food', 4, 1, 220000.00, NULL, 'draft', '2026-06-26 11:46:17', '2026-06-26 11:46:17'),
(47, 1028, 'food', 14, 1, 750000.00, NULL, 'served', '2026-06-26 12:34:03', '2026-06-26 12:36:50'),
(48, 1029, 'food', 8, 1, 145000.00, NULL, 'served', '2026-06-26 12:38:48', '2026-06-26 12:39:47'),
(49, 1030, 'food', 17, 1, 400000.00, '', 'served', '2026-06-26 12:42:54', '2026-06-26 12:43:14'),
(52, 1033, 'food', 9, 1, 195000.00, NULL, 'served', '2026-06-26 14:36:20', '2026-06-26 14:38:59'),
(53, 1034, 'food', 13, 1, 650000.00, NULL, 'served', '2026-06-30 10:47:05', '2026-06-30 10:47:23'),
(54, 1035, 'food', 1, 1, 400000.00, NULL, 'served', '2026-07-02 01:44:11', '2026-07-02 01:44:30');

-- --------------------------------------------------------

--
-- Table structure for table `po_receipt_inspections`
--

DROP TABLE IF EXISTS `po_receipt_inspections`;
CREATE TABLE `po_receipt_inspections` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `check_packaging` tinyint(1) DEFAULT 0,
  `check_color` tinyint(1) DEFAULT 0,
  `check_odor` tinyint(1) DEFAULT 0,
  `check_freshness` tinyint(1) DEFAULT 0,
  `check_size` tinyint(1) DEFAULT 0,
  `check_weight` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `po_receipt_inspections`
--

INSERT INTO `po_receipt_inspections` (`id`, `po_id`, `ingredient_id`, `image_path`, `check_packaging`, `check_color`, `check_odor`, `check_freshness`, `check_size`, `check_weight`, `notes`, `created_at`) VALUES
(1, 29, 3, 'insp_29_3_1782787592.png', 1, 1, 1, 1, 1, 1, NULL, '2026-06-30 02:46:32'),
(2, 30, 2, 'insp_30_2_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(3, 30, 3, 'insp_30_3_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(4, 30, 11, 'insp_30_11_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(5, 30, 16, 'insp_30_16_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(6, 30, 20, 'insp_30_20_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(7, 30, 22, 'insp_30_22_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(8, 30, 33, 'insp_30_33_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(9, 30, 34, 'insp_30_34_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(10, 30, 35, 'insp_30_35_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(11, 30, 38, 'insp_30_38_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(12, 30, 39, 'insp_30_39_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(13, 30, 40, 'insp_30_40_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(14, 30, 41, 'insp_30_41_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(15, 30, 42, 'insp_30_42_1782875730.png', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:15:30'),
(16, 31, 29, 'insp_31_29_1782875820.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:17:00'),
(17, 31, 31, 'insp_31_31_1782875820.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:17:00'),
(18, 31, 36, 'insp_31_36_1782875820.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:17:00'),
(19, 31, 37, 'insp_31_37_1782875820.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:17:00'),
(20, 32, 34, 'insp_32_34_1782876384.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:26:24'),
(21, 33, 34, 'insp_33_34_1782876859.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-01 03:34:19'),
(22, 34, 2, 'insp_34_2_1783769999.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-11 11:39:59'),
(23, 35, 2, 'insp_35_2_1783770472.jpg', 1, 1, 1, 1, 1, 1, NULL, '2026-07-11 11:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_code` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `batch_cert_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_code`, `supplier_id`, `created_by`, `created_at`, `status`, `total_amount`, `notes`, `batch_cert_file`) VALUES
(1, 'PO-20260603-001', 1, 'admin', '2026-06-03 19:22:47', 'completed', 27185000.00, 'Nhập hàng đầu kỳ theo yêu cầu', 'generic_batch_cert_1782738470.png'),
(2, 'PO-20260604094628', 1, 'admin', '2026-06-04 09:46:28', 'completed', 7460000.00, NULL, 'generic_batch_cert_1782738470.png'),
(3, 'PO-20260608113916', 1, 'Admin', '2026-06-08 11:39:16', 'completed', 375000.00, NULL, 'generic_batch_cert_1782738470.png'),
(4, 'PO-20260608133108', 1, 'Admin', '2026-06-08 13:31:08', 'completed', 60000.00, NULL, 'generic_batch_cert_1782738470.png'),
(7, 'PO-20260608091200', 1, '1', '2026-06-08 14:12:00', 'completed', 5000000.00, NULL, 'generic_batch_cert_1782738470.png'),
(8, 'PO-20260608091519', 1, '1', '2026-06-08 14:15:19', 'completed', 14900000.00, NULL, 'generic_batch_cert_1782738470.png'),
(9, 'PO-20260610110751', 1, 'Admin', '2026-06-10 11:07:51', 'completed', 14495000.00, NULL, 'generic_batch_cert_1782738470.png'),
(10, 'PO-20260610111208', 1, 'Admin', '2026-06-10 11:12:08', 'completed', 1750000.00, NULL, 'generic_batch_cert_1782738470.png'),
(11, 'PO-20260610111832', 1, 'Admin', '2026-06-10 11:18:32', 'completed', 100000.00, NULL, 'generic_batch_cert_1782738470.png'),
(12, 'PO-20260610112206', 1, 'Admin', '2026-06-10 11:22:06', 'completed', 300000.00, NULL, 'generic_batch_cert_1782738470.png'),
(13, 'PO-20260612095443', 1, 'Admin', '2026-06-12 09:54:43', 'completed', 600000.00, NULL, 'generic_batch_cert_1782738470.png'),
(14, 'PO-20260615091614', 1, 'Admin', '2026-06-15 09:16:14', 'completed', 660000.00, NULL, 'generic_batch_cert_1782738470.png'),
(15, 'PO-20260615091733', 1, 'Admin', '2026-06-15 09:17:33', 'completed', 375000.00, NULL, 'generic_batch_cert_1782738470.png'),
(16, 'PO-20260615092148', 1, 'Admin', '2026-06-15 09:21:48', 'completed', 18375000.00, NULL, 'generic_batch_cert_1782738470.png'),
(17, 'PO-20260615092148', 1, 'Admin', '2026-06-15 09:21:48', 'cancelled', 18375000.00, NULL, 'generic_batch_cert_1782738470.png'),
(18, 'PO-20260615093006', 1, 'Admin', '2026-06-15 09:30:06', 'completed', 250000.00, NULL, 'generic_batch_cert_1782738470.png'),
(19, 'PO-20260615093258', 1, 'Admin', '2026-06-15 09:32:58', 'completed', 227500.00, NULL, 'generic_batch_cert_1782738470.png'),
(20, 'PO-20260618183134', 1, 'Admin', '2026-06-18 18:31:34', 'completed', 1147500.00, NULL, 'generic_batch_cert_1782738470.png'),
(21, 'PO-20260623143530', 1, 'Admin', '2026-06-23 14:35:30', 'completed', 3030000.00, NULL, 'generic_batch_cert_1782738470.png'),
(22, 'PO-20260623160814', 1, 'Admin', '2026-06-23 16:08:14', 'completed', 1080000.00, NULL, 'generic_batch_cert_1782738470.png'),
(23, 'PO-20260623161327', 1, 'Admin', '2026-06-23 16:13:27', 'completed', 240000.00, NULL, 'generic_batch_cert_1782738470.png'),
(24, 'PO-20260623163036', 1, 'Admin', '2026-06-23 16:30:36', 'completed', 3500000.00, NULL, 'generic_batch_cert_1782738470.png'),
(25, 'PO-20260626185159', 1, 'Admin', '2026-06-26 18:51:59', 'completed', 13960000.00, NULL, 'generic_batch_cert_1782738470.png'),
(26, 'PO-20260629150336-2', 2, '1', '2026-06-29 20:03:36', 'completed', 5000000.00, NULL, 'generic_batch_cert_1782738470.png'),
(27, 'PO-20260629150336-3', 3, '1', '2026-06-29 20:03:36', 'completed', 10000000.00, NULL, 'generic_batch_cert_1782738470.png'),
(28, 'PO-20260629150336-4', 4, '1', '2026-06-29 20:03:36', 'completed', 5000000.00, NULL, 'generic_batch_cert_1782738470.png'),
(29, 'PO-20260630094338', 1, 'Admin', '2026-06-30 09:43:38', 'completed', 200000.00, NULL, 'cert_po_29_1782787592.png'),
(30, 'PO-20260701101221', 1, 'Admin', '2026-07-01 10:12:21', 'completed', 6079440.00, NULL, 'cert_po_30_1782875730.jpg'),
(31, 'PO-20260701101552', 5, 'Admin', '2026-07-01 10:15:52', 'completed', 23440000.00, NULL, 'cert_po_31_1782875820.jpg'),
(32, 'PO-20260701102556', 1, 'Admin', '2026-07-01 10:25:56', 'completed', 80000.00, NULL, 'cert_po_32_1782876384.jpg'),
(33, 'PO-20260701103356', 1, 'Admin', '2026-07-01 10:33:56', 'completed', 40000.00, NULL, 'cert_po_33_1782876859.jpg'),
(34, 'PO-20260711183910', 1, 'Admin', '2026-07-11 18:39:10', 'completed', 1200000.00, NULL, 'cert_po_34_1783769999.jpg'),
(35, 'PO-20260711184718', 1, 'Admin', '2026-07-11 18:47:18', 'completed', 1200000.00, NULL, 'cert_po_35_1783770472.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_details`
--

DROP TABLE IF EXISTS `purchase_order_details`;
CREATE TABLE `purchase_order_details` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `expected_qty` decimal(10,2) NOT NULL,
  `expected_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_details`
--

INSERT INTO `purchase_order_details` (`id`, `po_id`, `ingredient_id`, `expected_qty`, `expected_price`) VALUES
(15, 1, 1, 200.00, 5000.00),
(16, 1, 2, 10.00, 150000.00),
(17, 1, 3, 5.00, 50000.00),
(18, 1, 4, 10.00, 200000.00),
(19, 1, 5, 15.00, 250000.00),
(20, 1, 6, 5.00, 40000.00),
(21, 1, 7, 2.00, 30000.00),
(22, 1, 8, 20.00, 45000.00),
(23, 1, 9, 20.00, 180000.00),
(24, 1, 10, 2.00, 120000.00),
(25, 1, 11, 5.00, 45000.00),
(26, 1, 12, 2.00, 180000.00),
(27, 1, 13, 5.00, 90000.00),
(28, 1, 14, 20.00, 350000.00),
(29, 1, 15, 5.00, 60000.00),
(30, 1, 16, 10.00, 25000.00),
(31, 1, 17, 10.00, 450000.00),
(32, 1, 18, 5.00, 120000.00),
(33, 2, 19, 10.00, 350000.00),
(34, 2, 20, 10.00, 20000.00),
(35, 2, 21, 10.00, 15000.00),
(36, 2, 22, 8.00, 30000.00),
(37, 2, 23, 8.00, 40000.00),
(38, 2, 24, 15.00, 120000.00),
(39, 2, 25, 5.00, 200000.00),
(40, 2, 26, 5.00, 50000.00),
(41, 3, 30, 2.50, 150000.00),
(42, 4, 34, 3.00, 20000.00),
(43, 7, 35, 100.00, 50000.00),
(44, 8, 35, 100.00, 50000.00),
(45, 8, 36, 5.00, 800000.00),
(46, 8, 37, 1.00, 1500000.00),
(47, 8, 38, 10.00, 120000.00),
(48, 8, 39, 10.00, 150000.00),
(49, 8, 40, 4.00, 25000.00),
(50, 8, 41, 15.00, 80000.00),
(51, 8, 42, 4.00, 100000.00),
(52, 9, 25, 3.00, 100000.00),
(53, 9, 26, 3.00, 25000.00),
(54, 9, 33, 6.00, 120000.00),
(55, 9, 36, 3.00, 800000.00),
(56, 9, 37, 7.00, 1500000.00),
(57, 9, 40, 4.00, 25000.00),
(58, 9, 42, 4.00, 100000.00),
(59, 10, 14, 5.00, 350000.00),
(60, 11, 26, 4.00, 25000.00),
(61, 12, 2, 2.00, 150000.00),
(62, 13, 15, 10.00, 60000.00),
(63, 14, 33, 5.50, 120000.00),
(64, 15, 30, 2.50, 150000.00),
(65, 16, 7, 10.00, 30000.00),
(66, 16, 18, 10.00, 120000.00),
(67, 16, 37, 5.00, 1500000.00),
(68, 16, 17, 15.00, 450000.00),
(69, 16, 19, 15.00, 175000.00),
(70, 17, 7, 10.00, 30000.00),
(71, 17, 18, 10.00, 120000.00),
(72, 17, 37, 5.00, 1500000.00),
(73, 17, 17, 15.00, 450000.00),
(74, 17, 19, 15.00, 175000.00),
(75, 18, 22, 10.00, 25000.00),
(76, 19, 50, 65.00, 3500.00),
(77, 20, 40, 7.50, 25000.00),
(78, 20, 33, 8.00, 120000.00),
(79, 21, 10, 1.00, 120000.00),
(80, 21, 30, 5.00, 150000.00),
(81, 21, 33, 8.00, 120000.00),
(82, 21, 38, 10.00, 120000.00),
(83, 22, 10, 1.00, 120000.00),
(84, 22, 33, 8.00, 120000.00),
(85, 23, 10, 2.00, 120000.00),
(86, 24, 14, 10.00, 350000.00),
(87, 25, 7, 2.00, 30000.00),
(88, 25, 18, 5.00, 120000.00),
(89, 25, 20, 10.00, 10000.00),
(90, 25, 30, 8.00, 150000.00),
(91, 25, 36, 15.00, 800000.00),
(92, 26, 14, 10.00, 500000.00),
(93, 27, 17, 10.00, 500000.00),
(94, 27, 36, 10.00, 500000.00),
(95, 28, 29, 10.00, 500000.00),
(96, 29, 3, 4.00, 50000.00),
(97, 30, 2, 2.00, 150000.00),
(98, 30, 3, 2.00, 0.00),
(99, 30, 11, 3.00, 45000.00),
(100, 30, 16, 8.00, 25000.00),
(101, 30, 20, 8.00, 10000.00),
(102, 30, 22, 8.00, 20555.00),
(103, 30, 33, 8.00, 120000.00),
(104, 30, 34, 2.00, 20000.00),
(105, 30, 35, 8.00, 50000.00),
(106, 30, 38, 8.00, 120000.00),
(107, 30, 39, 8.00, 150000.00),
(108, 30, 40, 8.00, 25000.00),
(109, 30, 41, 8.00, 80000.00),
(110, 30, 42, 8.00, 100000.00),
(111, 31, 29, 8.00, 180000.00),
(112, 31, 31, 8.00, 450000.00),
(113, 31, 36, 8.00, 800000.00),
(114, 31, 37, 8.00, 1500000.00),
(115, 32, 34, 4.00, 20000.00),
(116, 33, 34, 2.00, 20000.00),
(117, 34, 2, 8.00, 150000.00),
(118, 35, 2, 8.00, 150000.00);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_expenses`
--

DROP TABLE IF EXISTS `restaurant_expenses`;
CREATE TABLE `restaurant_expenses` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_expenses`
--

INSERT INTO `restaurant_expenses` (`id`, `category`, `amount`, `expense_date`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Điện nước', 12000000.00, '2026-06-23', 'tiên điện ', '2026-06-23 11:30:28', '2026-06-23 11:31:04'),
(2, 'Mặt bằng', 15000000.00, '2026-06-23', 'mặt bằng', '2026-06-23 16:20:26', '2026-06-23 16:20:26'),
(3, 'Marketing', 10000000.00, '2026-06-23', 'PR', '2026-06-23 16:20:52', '2026-06-23 16:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_tables`
--

DROP TABLE IF EXISTS `restaurant_tables`;
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL,
  `table_code` varchar(20) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `category` enum('open','room') DEFAULT 'open',
  `capacity` int(11) DEFAULT 16,
  `price` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'available',
  `is_available` tinyint(1) DEFAULT 1,
  `pos_x` int(11) DEFAULT 0,
  `pos_y` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `table_code`, `table_number`, `room_type`, `category`, `capacity`, `price`, `status`, `is_available`, `pos_x`, `pos_y`) VALUES
(4, 'R1', '4', 'Khu vực chung', 'open', 2, 0.00, 'available', 1, 320, 380),
(5, 'R2', '5', 'Khu vực chung', 'open', 2, 0.00, 'available', 1, 320, 560),
(6, 'R3', '6', 'Khu vực chung', 'open', 2, 0.00, 'available', 1, 720, 560),
(7, 'R4', '7', 'Khu vực chung', 'open', 2, 0.00, 'available', 1, 860, 470),
(8, 'R5', '8', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 860, 290),
(9, 'R6', '9', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 720, 200),
(10, 'W1', '10', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 500, 290),
(11, 'W2', '11', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 720, 380),
(12, 'W3', '12', 'Khu vực chung', 'open', 4, 0.00, 'available', 1, 500, 630),
(13, 'W4', '13', 'Khu vực chung', 'open', 6, 0.00, 'available', 1, 320, 200),
(14, 'W5', '14', 'Khu vực chung', 'open', 6, 0.00, 'available', 1, 130, 270),
(15, 'W6', '15', 'Khu vực chung', 'open', 6, 0.00, 'available', 1, 130, 460),
(17, 'V1', '101', 'Khu vực chung', 'open', 8, 0.00, 'available', 1, 125, 675),
(18, 'V2', '102', 'Khu vực chung', 'open', 8, 0.00, 'available', 1, 1000, 100),
(19, 'V3', '103', 'Phòng VIP', 'room', 16, 0.00, 'available', 1, 125, 90),
(20, 'V4', '104', 'Phòng VIP', 'room', 16, 0.00, 'available', 1, 1000, 675);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `price`) VALUES
(1, 'Đặt bàn thường', 0.00),
(2, 'Tiệc sinh nhật VIP', 1500000.00),
(3, 'Đầu bếp riêng tại bàn', 2500000.00);

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

DROP TABLE IF EXISTS `service_bookings`;
CREATE TABLE `service_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `booking_date` datetime NOT NULL,
  `service_type` enum('table','birthday','chef') DEFAULT 'table',
  `table_id` int(11) DEFAULT NULL,
  `chef_id` int(11) DEFAULT NULL,
  `combo_id` int(11) DEFAULT NULL,
  `guests` int(11) DEFAULT 1,
  `message` text DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `deposit_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('Pending','Confirmed','Completed','Cancelled','No-Show') DEFAULT 'Pending',
  `event_type` varchar(100) DEFAULT NULL,
  `decor_package` varchar(100) DEFAULT NULL,
  `has_cake` tinyint(1) DEFAULT 0,
  `has_flower` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `has_candle` tinyint(1) DEFAULT 0,
  `has_handwritten_card` tinyint(1) DEFAULT 0,
  `card_message` text DEFAULT NULL,
  `flower_preference` varchar(255) DEFAULT NULL,
  `music_playlist` varchar(255) DEFAULT NULL,
  `light_tone` varchar(50) DEFAULT NULL,
  `chef_requirements` text DEFAULT NULL,
  `is_reminded` tinyint(1) DEFAULT 0,
  `decor_id` int(11) DEFAULT NULL,
  `ai_suggested_menu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_bookings`
--

INSERT INTO `service_bookings` (`id`, `user_id`, `customer_name`, `customer_phone`, `booking_date`, `service_type`, `table_id`, `chef_id`, `combo_id`, `guests`, `message`, `total_amount`, `deposit_amount`, `status`, `event_type`, `decor_package`, `has_cake`, `has_flower`, `is_archived`, `created_at`, `has_candle`, `has_handwritten_card`, `card_message`, `flower_preference`, `music_playlist`, `light_tone`, `chef_requirements`, `is_reminded`, `decor_id`, `ai_suggested_menu`) VALUES
(1, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-16 15:17:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-05-13 08:17:11', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(2, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-23 15:18:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-05-13 08:18:22', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(3, 2, 'Khách demo', '0909123456', '2026-05-20 18:00:00', 'table', 2, NULL, 1, 4, 'Đặt combo gia đình', 850000.00, 255000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-05-14 10:00:00', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(4, NULL, 'Ẩn danh', '0911222333', '2026-05-25 12:00:00', 'birthday', 19, NULL, 0, 8, 'Tiệc sinh nhật', 2000000.00, 600000.00, 'Completed', 'birthday', 'premium', 1, 1, 0, '2026-05-14 10:05:00', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(5, 5, 'Huỳnh Dương', '0122222222', '2026-05-22 09:30:00', 'chef', 0, NULL, 1, 20, '', 1370000.00, 411000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-05-14 15:07:47', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(6, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-24 13:58:00', 'table', 1, NULL, 0, 2, '', 145000.00, 43500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-05-23 06:58:54', 0, 0, NULL, NULL, NULL, NULL, 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: ', 0, NULL, NULL),
(7, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-26 19:37:00', 'table', 1, NULL, 0, 2, '', 445000.00, 133500.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-05-24 12:37:30', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: ', 0, NULL, NULL),
(8, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-25 19:38:00', 'table', 2, NULL, 0, 2, '', 170000.00, 51000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-05-24 12:38:32', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: ', 0, NULL, NULL),
(9, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-25 19:42:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-05-24 12:42:37', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: ', 0, NULL, NULL),
(10, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-26 20:11:00', 'table', 2, NULL, -1, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-05-24 13:11:20', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Dưới 1.500.000 đ / khách\nPhong cách: Ẩm thực Việt Nam Đương Đại (Contemporary Vietnamese)\nChi tiết: rgreh\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò Wagyu, Nấm Truffle\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(11, 2, 'Huỳnh Đức Thông', '1234567890', '2026-05-31 10:30:00', 'table', 0, NULL, -1, 2, '', 280000.00, 84000.00, 'Completed', NULL, NULL, 0, 1, 0, '2026-05-30 03:30:35', 1, 1, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: 1.500.000 đ - 3.000.000 đ / khách\nPhong cách: Ẩm thực Việt Nam Đương Đại (Contemporary Vietnamese)\nChi tiết: cdevwesb\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò Wagyu, Nấm Truffle\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(12, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-04 11:26:00', 'table', 1, NULL, 0, 2, '', 95000.00, 28500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-01 04:26:57', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò Wagyu, Nấm Truffle\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(13, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-02 11:30:00', 'table', 1, NULL, 0, 2, '', 0.00, 0.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-01 04:31:27', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò Wagyu, Nấm Truffle\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(14, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-02 11:36:00', 'table', 1, NULL, 0, 2, '', 45000.00, 13500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-01 04:36:48', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò Wagyu, Nấm Truffle\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(15, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-02 16:02:00', 'table', 2, NULL, 0, 2, '', 45000.00, 13500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-01 09:02:55', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò Wagyu, Nấm Truffle\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(16, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-02 16:04:00', 'table', 1, NULL, 0, 2, '', 45000.00, 13500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-01 09:04:01', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò Wagyu, Nấm Truffle\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(17, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-04 19:55:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-03 12:56:04', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(18, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-04 19:57:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-03 12:57:41', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(19, 2, 'Quản trị viên', '1234567890', '2026-06-05 09:57:00', 'table', 1, NULL, 0, 2, '', 445000.00, 133500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-04 02:57:44', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(20, 2, 'Quản trị viên', '1234567890', '2026-06-04 10:06:00', 'table', 1, NULL, 0, 2, '', 445000.00, 133500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-04 03:06:56', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(21, 2, 'Quản trị viên', '1234567890', '2026-06-05 10:10:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-04 03:10:16', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(22, 2, 'Quản trị viên', '1234567890', '2026-06-04 10:10:00', 'table', 2, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-04 03:10:48', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(23, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-06 08:21:00', 'chef', 0, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 01:21:07', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(24, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-05 08:22:00', 'chef', 0, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 01:22:32', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Thỏa thuận sau khi thiết kế thực đơn\nPhong cách: Tùy Bếp trưởng đề xuất\nChi tiết: \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(25, 12, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3125000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-19 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(26, 12, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3125000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-11 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(27, 12, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3125000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-20 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(28, 12, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3125000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-28 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(29, 13, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3250000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-20 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(30, 13, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3250000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-30 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(31, 14, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 1500000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-15 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(32, 15, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3666666.67, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-23 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(33, 15, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3666666.67, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-08 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(34, 15, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3666666.67, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-06-04 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(35, 16, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 800000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-17 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(36, 17, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 2750000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-14 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(37, 17, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 2750000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-13 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(38, 18, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3000000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-15 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(39, 18, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3000000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-07 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(40, 18, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3000000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-15 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(41, 18, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3000000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-26 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(42, 18, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3000000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-20 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(43, 18, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 3000000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-06-01 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(44, 19, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 1600000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-15 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(45, 19, '', '', '0000-00-00 00:00:00', 'table', NULL, NULL, NULL, 1, NULL, 1600000.00, 0.00, 'Completed', NULL, NULL, 0, 0, 1, '2026-05-31 04:25:03', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(46, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-06 14:32:00', '', 1, NULL, -1, 2, 'Mục đích: Hẹn hò | Chế độ ăn: Healthy | DỊ ỨNG: Hải sản', 3050000.00, 915000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-06 07:32:43', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Ngân sách: Dưới 1.500.000 đ / khách\r\nPhong cách: Tùy Bếp trưởng đề xuất\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(47, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-30 14:40:00', 'table', 2, NULL, 0, 2, '', 45000.00, 13500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-06 07:40:35', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(48, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-07 14:50:00', 'table', 1, NULL, 0, 2, '', 895000.00, 268500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-06 07:50:57', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(49, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-06 14:51:00', 'table', 1, NULL, 0, 2, '', 145000.00, 43500.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-06 07:51:56', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(50, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-06 16:56:00', 'table', 1, NULL, 0, 2, '', 220000.00, 66000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-06 07:57:08', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(51, 37, 'Phạm Minh Hoa', '0991796427', '2026-05-30 18:30:19', 'chef', 5, NULL, 3, 7, NULL, 8023442.00, 2407032.60, 'Cancelled', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(52, 36, 'Vũ Bảo Vy', '0941689163', '2026-05-02 10:05:52', 'chef', 9, NULL, 2, 7, NULL, 2308419.00, 692525.70, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(53, 35, 'Huỳnh Minh Linh', '0920234689', '2026-03-15 10:55:49', 'birthday', 8, NULL, 3, 8, NULL, 9607922.00, 2882376.60, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(54, 34, 'Phạm Thị Xuân', '0985291351', '2026-01-12 17:37:15', 'table', 7, NULL, 1, 5, NULL, 2291436.00, 687430.80, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(55, 33, 'Phạm Đức Quân', '0994414869', '2026-05-29 22:57:29', 'birthday', 6, NULL, 4, 10, NULL, 7035593.00, 2110677.90, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(56, 32, 'Lê Gia An', '0932506191', '2025-12-23 08:19:13', 'birthday', 2, NULL, 4, 3, NULL, 6147481.00, 1844244.30, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(57, 32, 'Lê Gia An', '0932506191', '2026-03-02 04:39:36', 'birthday', 3, NULL, 2, 8, NULL, 6680471.00, 2004141.30, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(58, 31, 'Đặng Bảo Sơn', '0939391711', '2026-03-18 20:18:42', 'birthday', 7, NULL, 1, 10, NULL, 2461854.00, 738556.20, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:39', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(59, 30, 'Phan Hoàng Sơn', '0948958860', '2026-01-26 02:33:16', 'table', 10, NULL, 3, 9, NULL, 5516881.00, 1655064.30, 'Cancelled', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(60, 29, 'Nguyễn Gia Dương', '0927194712', '2025-12-27 03:56:09', 'table', 9, NULL, 5, 5, NULL, 6725596.00, 2017678.80, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(61, 28, 'Trần Thị Hải', '0987490077', '2026-02-26 06:09:07', 'table', 1, NULL, 5, 7, NULL, 7869611.00, 2360883.30, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(62, 28, 'Trần Thị Hải', '0987490077', '2025-12-15 13:26:21', 'birthday', 5, NULL, 5, 2, NULL, 1464277.00, 439283.10, 'Cancelled', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(63, 27, 'Lê Minh Yến', '0958971878', '2026-03-07 07:15:39', 'chef', 8, NULL, 2, 7, NULL, 5889750.00, 1766925.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(64, 27, 'Lê Minh Yến', '0958971878', '2026-01-12 20:21:22', 'birthday', 2, NULL, 1, 3, NULL, 3492336.00, 1047700.80, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(65, 27, 'Lê Minh Yến', '0958971878', '2026-03-18 21:04:05', 'chef', 4, NULL, 5, 6, NULL, 2709103.00, 812730.90, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(66, 26, 'Lê Thị Mai', '0916261315', '2026-04-22 03:32:54', 'birthday', 9, NULL, 3, 3, NULL, 2686254.00, 805876.20, 'Cancelled', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(67, 25, 'Võ Văn Yến', '0952393534', '2026-04-11 11:17:44', 'birthday', 1, NULL, 4, 3, NULL, 2814244.00, 844273.20, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(68, 24, 'Vũ Minh Hải', '0995865619', '2026-04-18 21:45:59', 'table', 10, NULL, 4, 7, NULL, 9695115.00, 2908534.50, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(69, 24, 'Vũ Minh Hải', '0995865619', '2026-02-01 03:24:36', 'chef', 10, NULL, 4, 3, NULL, 6445778.00, 1933733.40, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(70, 23, 'Trần Văn Cường', '0997746456', '2026-02-03 13:15:35', 'table', 2, NULL, 1, 10, NULL, 2992285.00, 897685.50, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(71, 23, 'Trần Văn Cường', '0997746456', '2026-03-17 20:20:00', 'chef', 9, NULL, 5, 8, NULL, 4846552.00, 1453965.60, 'Cancelled', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(72, 23, 'Trần Văn Cường', '0997746456', '2026-05-17 08:18:00', 'birthday', 5, NULL, 1, 4, NULL, 3453361.00, 1036008.30, 'Cancelled', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(73, 22, 'Huỳnh Ngọc Sơn', '0953062208', '2026-01-28 15:34:50', 'birthday', 6, NULL, 4, 6, NULL, 6683159.00, 2004947.70, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(74, 22, 'Huỳnh Ngọc Sơn', '0953062208', '2026-05-01 13:33:22', 'birthday', 3, NULL, 1, 6, NULL, 9032822.00, 2709846.60, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(75, 21, 'Trần Minh Xuân', '0917904228', '2026-01-17 06:55:29', 'birthday', 10, NULL, 4, 9, NULL, 8143613.00, 2443083.90, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(76, 21, 'Trần Minh Xuân', '0917904228', '2026-02-24 11:35:31', 'birthday', 2, NULL, 2, 3, NULL, 7279423.00, 2183826.90, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-05 14:54:40', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(77, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-09 01:09:00', 'table', 1, NULL, 0, 2, '', 45000.00, 13500.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-08 03:08:55', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(78, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-14 10:10:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-08 03:10:39', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(79, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-08 13:12:00', 'table', 1, NULL, 0, 2, '', 0.00, 0.00, 'No-Show', NULL, NULL, 0, 0, 1, '2026-06-08 03:13:11', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(80, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-08 12:29:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-08 03:29:40', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(81, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-08 12:33:00', 'table', 1, NULL, 2, 2, '', 1200000.00, 360000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-08 03:33:27', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(82, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-08 15:38:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-08 06:39:32', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(83, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-08 15:43:00', 'table', 1, NULL, 0, 2, 'Chế độ ăn: Healthy | DỊ ỨNG: Hải sản', 450000.00, 135000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-08 06:43:32', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(84, 2, 'Quản trị viên', '1234567890', '2026-06-08 18:56:00', 'table', 1, NULL, 0, 2, 'DỊ ỨNG: Hải sản', 70000.00, 21000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-08 09:58:47', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Đậu phộng', 0, NULL, NULL),
(85, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-12 04:45:00', 'table', 1, NULL, 0, 2, '', 180000.00, 54000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-12 02:46:20', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(86, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-12 04:52:00', 'table', 1, NULL, 0, 2, '', 570000.00, 171000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-12 02:52:46', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(87, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-12 04:53:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-12 02:53:42', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(88, 6, 'Long Hoang', '0867081911', '2026-06-13 12:13:00', 'table', 1, NULL, 0, 3, '', 560000.00, 168000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-13 05:14:13', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(89, 6, 'Long Hoang', '0867081911', '2026-06-13 12:26:00', 'table', 0, NULL, 0, 2, '', 560000.00, 168000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-13 05:27:22', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(90, 6, 'Long Hoang', '0867081911', '2026-06-13 12:29:00', 'table', 1, NULL, 0, 2, '', 560000.00, 168000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-13 05:30:23', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(91, 6, 'Long Hoang', '0867081911', '2026-06-13 12:31:00', 'table', 0, NULL, 0, 2, '', 560000.00, 168000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-13 05:31:34', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(92, 6, 'Long Hoang', '0867081911', '2026-06-15 11:13:00', 'table', 0, NULL, 0, 2, '', 230000.00, 69000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-15 04:13:33', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(93, 6, 'Long Hoang', '0867081911', '2026-06-15 11:15:00', 'table', 0, NULL, 0, 2, '', 180000.00, 54000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-15 04:21:21', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(94, 6, 'Long Hoang', '0867081911', '2026-06-18 11:23:00', 'table', 0, NULL, 0, 2, '', 1530000.00, 459000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-15 04:23:59', 1, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(95, 21, 'tranminhxuan169_1', '0917904228', '2026-06-16 13:32:00', 'table', 20, NULL, 2, 2, '\n[Phục vụ riêng] Phục vụ Nam\n[Hệ thống: Đã giảm 10% cho khách hàng VIP Hội viên VIP]', 2245500.00, 673650.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-16 06:33:39', 1, 0, '', '', 'Classic Jazz (Cổ điển)', 'Warm (Ấm áp, Mờ ảo)', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Well Done\n- Hương vị: Chua thanh\n- Yêu thích: Cua hoàng đế\n- DỊ ỨNG: Không', 0, NULL, NULL),
(96, NULL, 'Test Khach', '0912345678', '2026-06-16 15:00:00', 'table', 1, NULL, NULL, 2, NULL, 0.00, 0.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-16 07:06:08', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(1095, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-16 14:36:00', 'table', 1, NULL, 0, 2, '', 530000.00, 159000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-15 07:36:26', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(1096, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-15 17:40:00', 'table', 1, NULL, 0, 2, '', 795000.00, 238500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-15 07:43:37', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(1097, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-16 14:49:00', 'table', 1, NULL, 0, 2, '', 630000.00, 189000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-15 07:49:42', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(1098, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-15 17:50:00', 'table', 1, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-15 07:51:04', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(1099, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-16 16:22:00', 'table', 1, NULL, 0, 2, '', 600000.00, 180000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-15 09:22:54', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(1100, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-16 16:24:00', 'table', 1, NULL, 0, 2, '', 430000.00, 129000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-15 09:24:44', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi', 0, NULL, NULL),
(1101, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-23 16:50:00', 'table', 14, NULL, 0, 2, '\n[Hệ thống: Đã giảm 10% cho khách hàng VIP Hội viên VIP]', 175500.00, 52650.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-23 06:50:28', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1102, 2, 'Quản trị viên', '1234567890', '2026-06-23 17:38:00', 'table', 4, NULL, 0, 2, '\n[Hệ thống: Đã giảm 10% cho khách hàng VIP Hội viên VIP]', 148500.00, 44550.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-23 07:39:22', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1103, 2, 'Quản trị viên', '1234567890', '2026-06-25 21:11:00', 'table', 14, NULL, 0, 2, '\n[Hệ thống: Đã giảm 10% cho khách hàng VIP Hội viên VIP]', 360000.00, 108000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-25 12:11:26', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1104, 2, 'Quản trị viên', '1234567890', '2026-06-25 22:06:00', 'table', 14, NULL, 0, 2, '\n[Hệ thống: Đã giảm 10% cho khách hàng VIP Hội viên VIP]', 765000.00, 229500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-25 13:06:35', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1105, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-26 20:45:00', 'table', 14, NULL, 0, 2, '\n[Hệ thống: Đã giảm 10% cho khách hàng VIP Hội viên VIP]', 198000.00, 59400.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-26 11:45:36', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1106, 2, 'Huỳnh Đức Thông', '1234567890', '2026-06-27 18:48:00', 'table', 14, NULL, 0, 2, '\n[Hệ thống: Đã giảm 10% cho khách hàng VIP Hội viên VIP]', 585000.00, 175500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-26 11:49:06', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1107, 45, 'Huỳnh Thông', '1234567890', '2026-06-26 21:11:00', 'table', 14, NULL, 0, 2, '', 150000.00, 45000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-26 12:11:45', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(1108, 45, 'Huỳnh Thông', '1234567890', '2026-06-26 21:18:00', 'table', 14, NULL, 0, 2, '', 180000.00, 54000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-26 12:19:05', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(1109, 45, 'Huỳnh Thông', '1234567890', '2026-06-26 21:30:00', 'table', 14, NULL, 0, 2, '', 750000.00, 225000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-26 12:30:44', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(1110, 45, 'Huỳnh Thông', '1234567890', '2026-06-26 22:37:00', 'table', 4, NULL, 0, 2, '', 145000.00, 43500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-26 12:37:55', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(1111, 45, 'Huỳnh Thông', '1234567890', '2026-06-26 23:34:00', 'table', 14, NULL, 0, 2, '', 195000.00, 58500.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-06-26 14:34:53', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, NULL, NULL),
(1112, 2, 'Quản trị viên', '1234567890', '2026-06-29 22:52:00', 'table', 14, NULL, -1, 2, '', 3000000.00, 900000.00, 'Cancelled', NULL, NULL, 0, 0, 1, '2026-06-29 14:50:32', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Dịp: Kỷ niệm\r\nNgân sách: Dưới 1.500.000 đ / khách\r\nPhong cách: Tùy Bếp trưởng đề xuất\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1113, 45, 'Huỳnh Thông', '1234567890', '2026-06-30 22:39:00', 'table', 14, NULL, 1, 2, '', 850000.00, 255000.00, 'Completed', 'Sinh nhật', 'Gói Mặc Định', 1, 0, 0, '2026-06-30 02:40:48', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '', 0, 1, NULL),
(1114, 2, 'Quản trị viên', '1234567890', '2026-07-01 22:52:00', 'table', 14, NULL, 0, 2, '\n[Hệ thống: Đã tự động giảm 10% nhờ đặc quyền cột mốc: Tăng mã giãm giá]', 585000.00, 175500.00, 'Cancelled', '', NULL, 0, 0, 1, '2026-06-30 10:45:21', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, 0, NULL),
(1115, 2, 'Quản trị viên', '1234567890', '2026-06-30 22:52:00', 'table', 14, NULL, 0, 2, '', 650000.00, 195000.00, 'Completed', '', NULL, 0, 0, 0, '2026-06-30 10:46:43', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, 0, NULL),
(1116, 2, 'Quản trị viên', '1234567890', '2026-07-03 22:34:00', 'chef', 0, NULL, 2, 2, '\n[Đầu bếp tại gia] Quy mô ekip (1-4 khách): 1 Chef + 1 Phục vụ', 1450000.00, 435000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-07-01 14:34:44', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Địa điểm phục vụ: biên hòa\r\nBếp trưởng chỉ định: Vũ Văn Chính\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1117, 2, 'Quản trị viên', '1234567890', '2026-07-03 22:46:00', 'table', 14, NULL, -1, 2, '', 3000000.00, 900000.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-01 14:46:52', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Dịp: Kỷ niệm\r\nNgân sách: Dưới 1.500.000 đ / khách\r\nPhong cách: Ẩm thực Việt Nam Đương Đại \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- Không thích: Hành lá, Rau mùi\n- DỊ ỨNG: Cá', 0, 0, NULL),
(1118, 2, 'Quản trị viên', '1234567890', '2026-07-04 12:30:00', 'table', 14, NULL, -1, 2, '', 2000000.00, 600000.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-01 15:30:34', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Dịp: Kỷ niệm\r\nNgân sách: Thỏa thuận sau khi thiết kế thực đơn\r\nPhong cách: Ẩm thực Việt Nam Đương Đại \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: Cá', 0, 0, NULL),
(1119, 2, 'Quản trị viên', '1234567890', '2026-07-04 12:30:00', 'table', 14, NULL, -1, 2, '', 2000000.00, 600000.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-01 15:38:05', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Dịp: Kỷ niệm\r\nNgân sách: Thỏa thuận sau khi thiết kế thực đơn\r\nPhong cách: Ẩm thực Việt Nam Đương Đại \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: Cá', 0, 0, NULL),
(1120, 2, 'Quản trị viên', '1234567890', '2026-07-04 12:30:00', 'table', 4, NULL, -1, 2, '', 1000000.00, 300000.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-01 15:44:43', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Dịp: Kỷ niệm\r\nNgân sách: Thỏa thuận sau khi thiết kế thực đơn\r\nPhong cách: Ẩm thực Việt Nam Đương Đại \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: Cá', 0, 0, NULL),
(1121, 2, 'Huỳnh Đức Thông', '109876512345', '2026-07-04 09:02:00', 'chef', 0, NULL, -1, 2, '\n[Đầu bếp tại gia] Quy mô ekip (1-4 khách): 1 Chef + 1 Phục vụ', 1000000.00, 300000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-07-02 00:02:26', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Địa điểm phục vụ: biên hòa\r\nBếp trưởng chỉ định: Vũ Văn Chính\r\nDịp: Kỷ niệm\r\nNgân sách: Thỏa thuận sau khi thiết kế thực đơn\r\nPhong cách: Tùy Bếp trưởng đề xuất\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: Cá', 0, NULL, NULL),
(1122, 2, 'Huỳnh Đức Thông', '1234567890', '2026-07-02 10:42:00', 'table', 14, NULL, 0, 2, '', 400000.00, 120000.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-02 01:43:07', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: sò điệp', 0, 0, NULL),
(1123, 2, 'Huỳnh Đức Thông', '1234567890', '2026-07-04 11:42:00', 'table', 14, NULL, -1, 2, '', 3000000.00, 900000.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-02 01:45:50', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Dịp: Kỷ niệm\r\nNgân sách: Dưới 1.500.000 đ / khách\r\nPhong cách: Ẩm thực Việt Nam Đương Đại \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: sò điệp', 0, 0, NULL),
(1124, 2, 'Huỳnh Đức Thông', '1234567890', '2026-07-03 09:47:00', 'chef', 0, NULL, 0, 2, '\n[Đầu bếp tại gia] Quy mô ekip (1-4 khách): 1 Chef + 1 Phục vụ', 650000.00, 195000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-07-02 01:47:30', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Địa điểm phục vụ: biên hòa\r\nBếp trưởng chỉ định: Vũ Văn Chính\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: sò điệp', 0, NULL, NULL),
(1125, 2, 'Quản trị viên', '1234567890', '2026-07-08 12:20:00', 'chef', 0, NULL, 0, 2, '\n[Đầu bếp tại gia] Quy mô ekip (1-4 khách): 1 Chef + 1 Phục vụ', 250000.00, 75000.00, 'Completed', NULL, NULL, 0, 0, 0, '2026-07-06 02:21:12', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Địa điểm phục vụ: biên hòa\r\nBếp trưởng chỉ định: Vũ Văn Chính\n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: sò điệp', 0, NULL, NULL),
(1126, 2, 'Quản trị viên', '1234567890', '2026-07-09 09:53:00', 'table', 14, NULL, -1, 2, '', 1500000.00, 450000.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-06 02:54:25', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', 'Dịp: Kỷ niệm\r\nNgân sách: Thỏa thuận sau khi thiết kế thực đơn\r\nPhong cách: Ẩm thực Việt Nam Đương Đại \n\n--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: Hải sản', 0, 0, '**TÊN THỰC ĐƠN: PHÙ SA VÀ LỬA - BẢN GIAO HƯỞNG KỶ NIỆM**\n\n**1. Khai vị nhỏ (Amuse-Bouche)**\n* **Khói Lam Chiều (Smoke of the Afternoon)**\n* *Mô tả:* Bánh tart nhỏ làm từ bột gạo lứt Tây Nguyên nướng giòn, nhân pa-tê gan ngỗng béo ngậy kết dính với hạt điều Bình Phước caramen hóa, phủ lớp gel quất (tắc) chua thanh và hoa dại bản địa.\n* *Sự phù hợp với DNA:* Món ăn mang hương vị béo ngậy, đậm đà (rich) từ gan ngỗng và hạt điều, được cân bằng hoàn hảo bằng vị chua của quất để kích thích vị giác. Hoàn toàn không chứa các thành phần hải sản, mở đầu nhẹ nhàng nhưng ấn tượng cho buổi tối kỷ niệm ấm áp.\n\n**2. Khai vị (Appetizer)**\n* **Hương Rừng Tây Bắc (Whispers of the Highlands)**\n* *Mô tả:* Ức vịt chạy đồng tẩm ướp mắc khén, hạt dổi nồng nàn, áp chảo vừa chín tới giữ độ mềm mọng mượt mà. Món ăn dùng kèm sốt mận Tả Van cô đặc chua ngọt và củ cải đỏ Đà Lạt muối chua nguyên bản.\n* *Sự phù hợp với DNA:* Vị đậm đà (bold) từ các loại gia vị bản địa Tây Bắc kết hợp cùng thịt vịt giàu hương vị mang đến chiều sâu cho món khai vị. Tránh tuyệt đối nước mắm truyền thống để đảm bảo an toàn dị ứng hải sản cho quý khách, thay bằng muối hun khói để giữ vị mặn mòi nguyên bản.\n\n**3. Món phụ (Entree)**\n* **Sương Sớm Sa Pa (Sapa\'s Morning Mist)**\n* *Mô tả:* Nấm rừng Sa Pa (nấm hương, nấm vuốt hổ) hầm chậm trong 24 giờ cùng tỏi đen Lý Sơn tạo nên phần nước dùng đậm đặc, dùng kèm bọt sữa dừa hun khói ấm áp và vụn hạt dẻ Trùng Khánh nướng vàng.\n* *Sự phù hợp với DNA:* Một món ăn thuần chay nhưng mang nốt hương cực kỳ đậm đà (bold/rich) nhờ vị ngọt sâu (umami) tự nhiên từ nấm rừng và tỏi đen. Sự ấm nóng của món ăn tượng trưng cho sự bền chặt, nồng ấm của tình yêu trong ngày kỷ niệm.\n\n**4. Món chính (Main Course)**\n* **Phù Sa Ngày Nắng (Sunlit Silt)**\n* *Mô tả:* Thăn ngoại bò tơ Tây Ninh hảo hạng được áp chảo và nướng trên than củi đến độ chín **Medium** hoàn hảo, rưới sốt tương bần Hưng Yên ủ sồi đậm vị, ăn kèm măng tây Đà Lạt nướng sém cạnh và bánh chưng ép giòn nồng nàn hương lá nếp.\n* *Sự phù hợp với DNA:* Chiều lòng tuyệt đối sở thích ăn **Bò** của quý khách với độ chín Medium mọng nước, mềm mượt. Sốt tương bần ủ sồi độc quyền của nhà hàng tạo nên tầng hương vị đậm đà (bold) đặc trưng của ẩm thực Việt Nam đương đại mà không cần dùng đến bất kỳ nguyên liệu biển nào.\n\n**5. Tráng miệng (Dessert)**\n* **Phù Hoa Đất Sét (Clay Luxury)**\n* *Mô tả:* Mousse sô-cô-la đen Single Origin 70% (nguồn gốc bền vững từ Bến Tre) đắng nhẹ, kết hợp cùng caramel muối tre hun khói nồng nàn và bánh xốp chuối ngự nướng mật ong rừng vàng óng.\n* *Sự phù hợp với DNA:* Kết thúc ngọt ngào và đậm đà (rich/bold) từ sô-cô-la đắng kết hợp với vị mặn nhẹ từ muối tre hun khói. Đây là lời chúc ngọt ngào, sâu sắc gửi đến hành trình kỷ niệm của hai vị khách quý.\n\n---\n\n**Gợi ý Rượu Vang:**\n* **Chai vang đỏ Penfolds Bin 28 Kalimna Shiraz (Nam Úc)**\n* *Lý do:* Chai Shiraz có cấu trúc mạnh mẽ, đậm đà (full-bodied) với hương thơm nồng nàn của quả mâm xôi đen, cam thảo và gia vị ấm. Tannin mượt mà của chai vang này sẽ cộng hưởng tuyệt vời với độ chín Medium của thịt bò tơ và các nốt vị đậm (bold) xuyên suốt thực đơn, mang lại trải nghiệm ẩm thực thăng hoa cho đêm kỷ niệm.'),
(1127, 2, 'Quản trị viên', '1234567890', '2026-07-09 10:38:00', 'table', 14, NULL, -1, 2, '', 0.00, 0.00, 'Completed', '', NULL, 0, 0, 0, '2026-07-06 03:38:49', 0, 0, '', '', 'Mặc định nhà hàng', 'Mặc định', '--- HỒ SƠ KHẨU VỊ (CULINARY DNA) ---\n- Độ chín: Medium\n- Hương vị: Đậm vị (Bold/Rich)\n- Yêu thích: Bò\n- DỊ ỨNG: Hải sản', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key_name` varchar(50) NOT NULL,
  `key_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key_name`, `key_value`) VALUES
('address', 'Biên hòa'),
('email', ''),
('enable_telegram', '1'),
('facebook_url', ''),
('footer_img_1', 'footer_img_1_1783398228.jpg'),
('footer_img_2', 'footer_img_2_1783398228.jpg'),
('footer_img_3', 'footer_img_3_1783398228.jpg'),
('footer_text', '© 2024 Restaurantly. All Rights Reserved.'),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d62672.35610614466!2d106.77477924863281!3d10.961691200000008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dd52812c0ea9%3A0xdaf793778f6b663c!2zTmjDoyBjw6AgcGjDqiAtIFBoYW4gVHJ1bmc!5e0!3m2!1svi!2s!4v1783854206559!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"strict-origin-when-cross-origin\"></iframe>'),
('hotline', '0456789124'),
('inv_auto_deduct', '1'),
('inv_expiry_days', '7'),
('inv_expiry_warning_days', '30'),
('inv_low_stock', '5'),
('inv_low_stock_threshold', '5'),
('last_telegram_alert_date', '2026-07-12'),
('last_telegram_eod_date', '2026-07-12'),
('logo_url', 'assets/img/logo.png'),
('logo_ver', '1783568271'),
('maps_embed', ''),
('meta_desc', ''),
('name_position', 'left'),
('open_days', 'Thứ 2 - Chủ Nhật'),
('open_time', '09:00 AM - 12:00 PM'),
('promo_popup_content', 'NHÃ tự hào công bố một bước ngoặt quan trọng trên bản đồ ẩm thực bền vững thế giới: NHÃ Restaurant đã chính thức đạt chứng nhận 3 sao của Food Made Good Standard – cấp độ cao nhất từ Hiệp hội Nhà hàng Bền vững quốc tế (The Sustainable Restaurant Association – SRA). Với kết quả này, NHÃ Restaurant không chỉ là đại diện đầu tiên của Việt Nam vươn tới chuẩn mực 3 sao, mà còn khẳng định sức mạnh của một hệ thống ẩm thực nhất quán về triết lý từ NHÃ Danang (Sao Xanh MICHELIN) đến NHÃ Tokyo.'),
('promo_popup_enabled', '1'),
('promo_popup_file', 'assets/img/promo_popup_1783342235.jpg'),
('promo_popup_type', 'image'),
('restaurant_name', 'NHÃ'),
('telegram_bot_token', '8935031959:AAEzSndMhjXuiIyXkeSNCtzTzj4TGoCo81s'),
('telegram_chat_id', '5676940088'),
('telegram_eod_enabled', '1'),
('telegram_eod_hour', '22'),
('zalo_url', '');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `origin_country` varchar(100) DEFAULT NULL,
  `transport_conditions` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `address`, `created_at`, `email`, `contact_person`, `origin_country`, `transport_conditions`) VALUES
(1, 'Nhà Cung Cấp Nội Địa Gia Vị', '012345678', 'bvhb', '2026-04-26 13:37:03', 'long@gmail.com', 'Nguyễn Văn A', '', ''),
(2, 'Ozaki Beef', '+81-98-765-4321', 'Miyazaki Prefecture, Japan', '2026-06-29 12:40:47', 'export@ozakibeef.jp', 'Kenichi Ozaki', 'Nhật Bản', 'Xe đông lạnh chuyên dụng -18°C'),
(3, 'Nissui', '+81-3-1234-5678', 'Tokyo, Japan', '2026-06-29 12:40:47', 'sales@nissui.co.jp', 'Yuki Takahashi', 'Nhật Bản', 'Container lạnh -20°C'),
(4, 'Rougié', '+33-5-62-60-23-00', 'Sarlat, France', '2026-06-29 12:40:47', 'export@rougie.com', 'Jean-Paul', 'Pháp', 'Xe lạnh chuyên dụng 0-4°C'),
(5, 'Oceania Premium Seafood', '0901234567', 'Quận 1, TP.HCM', '2026-07-01 02:35:29', 'contact@oceania.com', 'David Trần', 'Tây Ban Nha, Bắc Mỹ', 'Cấp đông sâu -18°C'),
(6, 'Fresh Farms Import', '0987654321', 'Quận 7, TP.HCM', '2026-07-01 02:35:29', 'sales@freshfarms.com', 'Lê Thu', 'Mỹ', 'Làm mát 4°C');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_certificates`
--

DROP TABLE IF EXISTS `supplier_certificates`;
CREATE TABLE `supplier_certificates` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `cert_type` varchar(50) NOT NULL,
  `cert_name` varchar(100) DEFAULT NULL,
  `cert_number` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_certificates`
--

INSERT INTO `supplier_certificates` (`id`, `supplier_id`, `cert_type`, `cert_name`, `cert_number`, `issue_date`, `expiry_date`, `file_path`, `created_at`) VALUES
(3, 2, 'GlobalG.A.P', NULL, 'GGAP-OZ-002', '2025-05-10', '2028-05-10', 'cert_2_1782736847_318.png', '2026-06-29 12:40:47'),
(4, 2, 'ISO 22000', NULL, 'ISO-OZ-003', '2024-11-20', '2026-11-20', 'cert_2_1782736847_611.png', '2026-06-29 12:40:47'),
(5, 3, 'MSC', NULL, 'MSC-NS-001', '2024-03-15', '2027-03-15', 'cert_3_1782736847_625.png', '2026-06-29 12:40:47'),
(6, 3, 'ASC', NULL, 'ASC-NS-002', '2025-02-20', '2028-02-20', 'cert_3_1782736847_898.png', '2026-06-29 12:40:47'),
(8, 4, 'ISO 22000', NULL, 'ISO-RG-001', '2024-10-01', '2027-10-01', 'cert_4_1782736847_289.png', '2026-06-29 12:40:47'),
(9, 4, 'Organic', NULL, 'ORG-RG-002', '2025-01-15', '2028-01-15', 'cert_4_1782736847_883.png', '2026-06-29 12:40:47'),
(10, 5, 'CO/CQ', 'Chứng nhận xuất xứ và chất lượng', 'CO-198273', '2026-01-01', '2027-01-01', NULL, '2026-07-01 02:35:29'),
(11, 5, 'ATVSTP', 'Chứng nhận ATVSTP Quốc tế', 'VS-882233', '2026-02-01', '2027-02-01', NULL, '2026-07-01 02:35:29'),
(12, 6, 'GlobalGAP', 'Chứng nhận Nông nghiệp sạch toàn cầu', 'GG-992211', '2026-03-01', '2027-03-01', NULL, '2026-07-01 02:35:29'),
(13, 1, 'ATVSTP', NULL, '', NULL, '2027-12-01', 'atvstp_1781489897_980.jpg', '2026-07-11 11:46:44');

-- --------------------------------------------------------

--
-- Table structure for table `themes`
--

DROP TABLE IF EXISTS `themes`;
CREATE TABLE `themes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `themes`
--

INSERT INTO `themes` (`id`, `name`, `description`, `start_date`, `end_date`, `image`, `is_active`, `created_at`) VALUES
(1, 'Mùa thu', 'ngọt ngào', '2026-06-05 20:13:00', '2026-12-31 23:59:00', 'public/assets/img/themes/theme_6a3a369bd936f.jpg', 1, '2026-06-05 13:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `toppings`
--

DROP TABLE IF EXISTS `toppings`;
CREATE TABLE `toppings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `selection_type` varchar(50) DEFAULT 'checkbox',
  `topping_group` varchar(100) DEFAULT 'Topping thêm',
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toppings`
--

INSERT INTO `toppings` (`id`, `name`, `description`, `price`, `image`, `selection_type`, `topping_group`, `status`) VALUES
(1, 'Tái (Rare)', NULL, 0.00, NULL, 'radio', 'Độ chín thịt', 1),
(2, 'Tái vừa (Medium Rare)', NULL, 0.00, NULL, 'radio', 'Độ chín thịt', 1),
(3, 'Chín vừa (Medium)', NULL, 0.00, NULL, 'radio', 'Độ chín thịt', 1),
(4, 'Chín kỹ (Medium Well)', NULL, 0.00, NULL, 'radio', 'Độ chín thịt', 1),
(5, 'Chín hoàn toàn (Well Done)', NULL, 0.00, NULL, 'radio', 'Độ chín thịt', 1),
(6, 'Không đá', NULL, 0.00, NULL, 'radio', 'Tùy chọn Thức uống', 1),
(7, 'Ít đá', NULL, 0.00, NULL, 'radio', 'Tùy chọn Thức uống', 1),
(8, 'Ít ngọt (Less Sugar)', NULL, 0.00, NULL, 'checkbox', 'Tùy chọn Thức uống', 1),
(9, 'Không đường (No Sugar)', NULL, 0.00, NULL, 'checkbox', 'Tùy chọn Thức uống', 1),
(10, 'Thêm Sốt Nấm Truffle đen', '', 120000.00, NULL, 'checkbox', 'Sốt ăn kèm', 1),
(11, 'Thêm Sốt Tiêu đen', '', 50000.00, NULL, 'checkbox', 'Sốt ăn kèm', 1),
(12, 'Thêm Sốt Phô mai cay', '', 50000.00, NULL, 'checkbox', 'Sốt ăn kèm', 1),
(13, 'Thêm Sốt BBQ mặn ngọt', NULL, 25000.00, NULL, 'checkbox', 'Sốt ăn kèm', 1),
(14, 'Gấp đôi Phô mai Mozzarella', NULL, 30000.00, NULL, 'checkbox', 'Topping Pizza/Pasta', 1),
(15, 'Thêm Xúc xích Đức', NULL, 35000.00, NULL, 'checkbox', 'Topping Pizza/Pasta', 1),
(16, 'Thêm Dăm bông Prosciutto', NULL, 45000.00, NULL, 'checkbox', 'Topping Pizza/Pasta', 1),
(17, 'Thêm Nấm mỡ tươi', NULL, 20000.00, NULL, 'checkbox', 'Topping Pizza/Pasta', 1),
(18, 'Thêm Trứng cá hồi Ikura', NULL, 80000.00, NULL, 'checkbox', 'Topping Pizza/Pasta', 1),
(19, 'Bánh mì bơ tỏi thêm', NULL, 15000.00, NULL, 'checkbox', 'Món ăn kèm', 1),
(20, 'Thêm Khoai tây nghiền', NULL, 30000.00, NULL, 'checkbox', 'Món ăn kèm', 1),
(21, 'Thêm Trứng chần', NULL, 15000.00, NULL, 'checkbox', 'Món ăn kèm', 1),
(22, 'Lát cam/chanh sấy khô', 'Trang trí tự nhiên, tươi mát', 15000.00, NULL, 'checkbox', 'Trái cây & Hoa', 1),
(23, 'Hạt lựu tươi', 'Thêm màu sắc và vị ngọt thanh', 20000.00, NULL, 'checkbox', 'Trái cây & Hoa', 1),
(24, 'Cánh hoa hồng hữu cơ', 'Tạo hương thơm nhẹ nhàng và sang trọng', 25000.00, NULL, 'checkbox', 'Trái cây & Hoa', 1),
(25, 'Dưa leo cuộn dải mỏng', 'Trang trí tinh tế', 10000.00, NULL, 'checkbox', 'Trái cây & Hoa', 1),
(26, 'Quả cherry ngâm rượu', 'Ngọt ngào, đậm vị', 30000.00, NULL, 'checkbox', 'Trái cây & Hoa', 1),
(27, 'Nhánh hương thảo tươi / khói', 'Hương thảo mộc độc đáo', 35000.00, NULL, 'checkbox', 'Thảo mộc & Gia vị', 1),
(28, 'Lá bạc hà tươi', 'Hương vị the mát', 10000.00, NULL, 'checkbox', 'Thảo mộc & Gia vị', 1),
(29, 'Thanh quế khô (đốt cháy)', 'Tạo hương khói ấm áp', 20000.00, NULL, 'checkbox', 'Thảo mộc & Gia vị', 1),
(30, 'Hoa hồi', 'Gia vị thơm nồng', 15000.00, NULL, 'checkbox', 'Thảo mộc & Gia vị', 1),
(31, 'Viên Truffle Chocolate', 'Trang trí cocktail béo ngậy', 50000.00, NULL, 'checkbox', 'Ngọt & Sô-cô-la', 1),
(32, 'Sốt Chocolate đậm đặc (Fudge)', 'Sốt sô-cô-la viền ly', 25000.00, NULL, 'checkbox', 'Ngọt & Sô-cô-la', 1),
(33, 'Kẹo bông gòn', 'Tan chảy khi rót rượu', 30000.00, NULL, 'checkbox', 'Ngọt & Sô-cô-la', 1),
(34, 'Cốm Chocolate / Cốm màu', 'Trang trí màu sắc', 15000.00, NULL, 'checkbox', 'Ngọt & Sô-cô-la', 1),
(35, 'Bột Ca cao rắc viền', 'Thơm mùi sô-cô-la nguyên chất', 15000.00, NULL, 'checkbox', 'Ngọt & Sô-cô-la', 1),
(36, 'Bột quế rắc viền', 'Gia vị ấm áp', 15000.00, NULL, 'checkbox', 'Ngọt & Sô-cô-la', 1),
(37, 'Kẹo Marshmallow nướng', 'Kẹo dẻo nướng xém', 20000.00, NULL, 'checkbox', 'Ngọt & Sô-cô-la', 1),
(38, 'Nhũ vàng thực phẩm', 'Lấp lánh, sang trọng', 100000.00, NULL, 'checkbox', 'Viền ly & Nghệ thuật', 1),
(39, 'Muối hồng Himalaya', 'Viền ly Margarita', 20000.00, NULL, 'checkbox', 'Viền ly & Nghệ thuật', 1),
(40, 'Đường tinh thể màu hồng', 'Đường viền ly ngọt ngào', 15000.00, NULL, 'checkbox', 'Viền ly & Nghệ thuật', 1),
(41, 'Lớp bọt Foam kem mặn', 'Béo ngậy, mặn mà', 50000.00, NULL, 'checkbox', 'Viền ly & Nghệ thuật', 1);

-- --------------------------------------------------------

--
-- Table structure for table `topping_recipes`
--

DROP TABLE IF EXISTS `topping_recipes`;
CREATE TABLE `topping_recipes` (
  `id` int(11) NOT NULL,
  `topping_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_required` float NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topping_recipes`
--

INSERT INTO `topping_recipes` (`id`, `topping_id`, `item_id`, `quantity_required`, `created_at`) VALUES
(1, 10, 44, 0.02, '2026-06-13 12:59:13'),
(2, 11, 12, 0.03, '2026-06-13 12:59:13'),
(3, 12, 45, 0.03, '2026-06-13 12:59:13'),
(4, 13, 13, 0.03, '2026-06-13 12:59:13'),
(5, 14, 46, 0.05, '2026-06-13 12:59:13'),
(6, 15, 47, 0.05, '2026-06-13 12:59:13'),
(7, 16, 48, 0.03, '2026-06-13 12:59:13'),
(8, 17, 38, 0.05, '2026-06-13 12:59:13'),
(9, 18, 49, 0.02, '2026-06-13 12:59:13'),
(11, 20, 51, 0.1, '2026-06-13 12:59:13'),
(12, 21, 50, 1, '2026-06-13 12:59:13'),
(13, 22, 65, 1, '2026-06-15 10:55:00'),
(14, 23, 66, 10, '2026-06-15 10:55:00'),
(15, 24, 67, 5, '2026-06-15 10:55:00'),
(16, 25, 68, 5, '2026-06-15 10:55:00'),
(17, 26, 69, 1, '2026-06-15 10:55:00'),
(18, 27, 33, 1, '2026-06-15 10:55:00'),
(19, 28, 70, 2, '2026-06-15 10:55:00'),
(20, 29, 71, 5, '2026-06-15 10:55:00'),
(21, 30, 72, 2, '2026-06-15 10:55:00'),
(22, 31, 73, 1, '2026-06-15 10:55:00'),
(23, 32, 74, 5, '2026-06-15 10:55:00'),
(24, 33, 75, 10, '2026-06-15 10:55:00'),
(25, 34, 76, 5, '2026-06-15 10:55:00'),
(26, 35, 77, 2, '2026-06-15 10:55:00'),
(27, 36, 78, 2, '2026-06-15 10:55:00'),
(28, 37, 79, 1, '2026-06-15 10:55:00'),
(29, 38, 80, 0.1, '2026-06-15 10:55:00'),
(30, 39, 81, 2, '2026-06-15 10:55:00'),
(31, 40, 82, 2, '2026-06-15 10:55:00'),
(32, 41, 83, 10, '2026-06-15 10:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `transfer_details`
--

DROP TABLE IF EXISTS `transfer_details`;
CREATE TABLE `transfer_details` (
  `id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transfer_details`
--

INSERT INTO `transfer_details` (`id`, `transfer_id`, `ingredient_id`, `quantity`) VALUES
(1, 1, 1, 10.00),
(2, 2, 1, 10.00),
(3, 3, 6, 10.00),
(4, 3, 1, 10.00),
(5, 4, 1, 10.00),
(10, 9, 1, 5.00),
(11, 10, 3, 15.00),
(12, 11, 3, 15.00),
(13, 12, 3, 5.00),
(14, 13, 3, 1.00),
(15, 14, 3, 1.00),
(16, 15, 4, 10.00),
(17, 16, 5, 5.00),
(18, 17, 6, 5.00),
(19, 18, 6, 2.00),
(20, 19, 7, 1.00),
(21, 20, 4, 0.20),
(22, 21, 3, 1.00),
(23, 22, 9, 10.00),
(24, 23, 10, 12.00),
(25, 24, 9, 5.00),
(26, 25, 10, 1.00),
(27, 26, 10, 2.00),
(28, 27, 9, 2.00),
(29, 28, 12, 2.00),
(30, 29, 12, 8.00),
(31, 30, 12, 8.00),
(32, 31, 12, 2.00),
(33, 32, 9, 10.00),
(34, 33, 14, 5.00),
(35, 34, 14, 5.00),
(36, 35, 9, 3.00),
(37, 36, 9, 3.00),
(38, 37, 2, 10.00),
(39, 38, 8, 20.00),
(40, 39, 4, 10.00),
(41, 40, 10, 2.00),
(42, 41, 12, 2.00),
(43, 42, 24, 7.00),
(44, 43, 24, 8.00),
(45, 44, 21, 10.00),
(46, 45, 26, 5.00),
(47, 46, 21, 2.00),
(48, 47, 1, 200.00),
(49, 48, 2, 2.00),
(50, 49, 12, 1.00),
(51, 50, 3, 3.00),
(52, 51, 15, 3.00),
(53, 52, 13, 3.00),
(54, 52, 29, 8.00),
(55, 52, 30, 3.00),
(56, 52, 31, 10.00),
(57, 52, 33, 1.00),
(58, 53, 28, 6.00),
(59, 54, 10, 1.00),
(60, 55, 17, 5.00),
(61, 56, 20, 5.00),
(62, 57, 34, 2.00),
(63, 58, 7, 1.00),
(64, 59, 40, 2.00),
(65, 60, 11, 3.00),
(66, 61, 39, 10.00),
(67, 62, 16, 5.00),
(68, 63, 18, 3.00),
(69, 64, 25, 2.00),
(70, 65, 8, 10.00),
(71, 66, 36, 4.00),
(72, 67, 41, 7.00),
(73, 68, 20, 3.00),
(74, 69, 19, 5.00),
(75, 70, 35, 70.00),
(76, 71, 6, 2.00),
(77, 72, 26, 2.00),
(78, 73, 5, 10.00),
(79, 74, 37, 0.80),
(80, 75, 48, 8.00),
(81, 76, 51, 8.00),
(82, 77, 42, 8.00),
(83, 77, 45, 10.00),
(84, 78, 46, 10.00),
(85, 79, 6, 4.00),
(86, 80, 38, 8.00),
(87, 80, 38, 8.00),
(88, 81, 22, 8.00),
(89, 81, 47, 8.00),
(90, 82, 49, 8.00),
(91, 82, 50, 50.00),
(92, 83, 38, 6.00),
(93, 84, 50, 40.00),
(94, 84, 2, 5.00),
(95, 84, 26, 3.00),
(96, 84, 49, 8.00),
(97, 84, 21, 7.00),
(98, 84, 8, 20.00),
(99, 84, 6, 2.00),
(100, 85, 55, 12.00),
(101, 86, 38, 4.00),
(102, 87, 14, 7.00),
(103, 88, 38, 8.00),
(104, 88, 44, 8.00),
(105, 89, 77, 250.00),
(106, 89, 78, 250.00),
(107, 89, 67, 100.00),
(108, 90, 76, 250.00),
(109, 90, 68, 550.00),
(110, 90, 40, 7.00),
(111, 91, 66, 150.00),
(112, 91, 72, 250.00),
(113, 91, 33, 8.00),
(114, 92, 75, 50.00),
(115, 92, 79, 60.00),
(116, 93, 75, 50.00),
(117, 93, 79, 50.00),
(118, 94, 70, 150.00),
(119, 94, 65, 80.00),
(120, 94, 83, 40.00),
(121, 95, 81, 40.00),
(122, 96, 71, 250.00),
(123, 97, 74, 40.00),
(124, 98, 76, 250.00),
(125, 99, 67, 100.00),
(126, 100, 40, 7.00),
(127, 101, 78, 250.00),
(128, 101, 77, 250.00),
(129, 101, 2, 2.00),
(130, 102, 82, 40.00),
(131, 103, 69, 40.00),
(132, 104, 80, 40.00),
(133, 105, 73, 50.00),
(134, 106, 33, 8.00),
(135, 107, 10, 2.00),
(136, 108, 30, 8.00),
(137, 109, 7, 2.00),
(138, 110, 3, 3.00),
(139, 111, 20, 8.00),
(140, 111, 16, 8.00),
(141, 111, 36, 8.00),
(142, 111, 3, 2.00),
(143, 111, 29, 8.00),
(144, 111, 33, 6.00),
(145, 111, 2, 2.00),
(146, 111, 17, 18.00),
(147, 112, 99, 10.00),
(148, 113, 98, 10.00),
(149, 114, 39, 6.00),
(150, 115, 40, 8.00),
(151, 116, 11, 3.00),
(152, 117, 66, 150.00),
(153, 118, 72, 250.00),
(154, 119, 18, 5.00),
(155, 119, 41, 8.00),
(156, 120, 38, 8.00),
(157, 121, 97, 15.00),
(158, 121, 37, 8.00),
(159, 122, 31, 8.00),
(160, 122, 42, 8.00),
(161, 123, 22, 8.00),
(162, 124, 34, 4.00),
(163, 125, 35, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_blob` longblob DEFAULT NULL,
  `avatar_mime` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('admin','cashier','chef','waiter','customer','staff') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL,
  `doneness` varchar(50) DEFAULT NULL,
  `flavor_profile` varchar(255) DEFAULT NULL,
  `fav_ingredients` varchar(255) DEFAULT NULL,
  `disliked_ingredients` varchar(255) DEFAULT NULL,
  `allergies` varchar(255) DEFAULT NULL,
  `visit_count` int(11) DEFAULT 0,
  `total_spent` decimal(15,2) DEFAULT 0.00,
  `drink_preferences` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `avatar`, `avatar_blob`, `avatar_mime`, `phone`, `birthday`, `email`, `google_id`, `role`, `is_active`, `created_at`, `employee_id`, `doneness`, `flavor_profile`, `fav_ingredients`, `disliked_ingredients`, `allergies`, `visit_count`, `total_spent`, `drink_preferences`) VALUES
(2, 'Huỳnh Đức Thông', '$2y$10$vlK96vUHCNST0Vln1xdQ6OLod2VhDOqfVEvsQeJmc8flJ6nyohnqq', 'Quản trị viên', NULL, 0xffd8ffe000104a46494600010101006000600000ffe1057245786966000049492a000800000006009f9c01007a020000560000009b9c01006c000000d00200009e9c01007c0100003c0300009d9c010020000000b80400004947030001000000500000002588040001000000d8040000000000004300f4006e006700200074007900200049006e0020004b00f91e2000540068007500ad1e740020005300d11e2000530069006e00630065002000320030003000360020002d0020004300f4006e006700200074007900200069006e002000a51e6e00200054005000480043004d002c002000540068006900bf1e740020006b00bf1e200049006e002000a41e6e002c00200049006e0020004e00680061006e006800200047006900e10020005200bb1e2c00200049006e004b00540053002c00200049006e002000a41e6e002c00200049006e0020004e00680061006e00680020004b00f91e2000540068007500ad1e740020005300d11e20002d00200020001101b71e7400200069006e002000a51e6e00200071007500a31e6e00670020006300e1006f002c0020006200e1006f00200067006900e100200069006e0020007400720061006e0068002000a31e6e00680020007400720065006f0020007400b001dd1e6e0067002000630061006e007600610073002c00200063006100740061006c006f006700750065002c0020007400dd1e20007200a10169002c00200063006100720064002000760069007300690074002c0020007300740061006e006400650065002c00200073007400690063006b00650072002c0020007400fa006900200067006900a51e79002c0020006d0065006e0075002c002000740068006900c71e70002c00200074006800bb1e20006e006800f11e61002c002000740065006d0020006e006800e3006e00200044006500630061006c0020006c006f0067006f002c00200069006e00200070006f0073007400650072002000500050002c0020006200a11e740020004800690066006c006500780000004300f4006e006700200054007900200049006e0020004b00f91e2000540068007500ad1e740020005300d11e20002d00200049006e004b005400530020002d0020004400690067006900740061006c0020005000720069006e00740069006e00670020006c00740064000000740068006900bf1e740020006b00bf1e200069006e002000a51e6e002c002000740068006900bf1e740020006b00bf1e200069006e002c00200069006e0020006e00680061006e006800200067006900e10020007200bb1e2c00200069006e00200067006900e10020007200bb1e2c0020006300f4006e006700200074007900200069006e002000a51e6e002c0020006300f4006e006700200074007900200069006e002c00200069006e002000a51e6e00200071007500a31e6e00670020006300e1006f002c00200069006e002000a51e6e002c00200069006e00200071007500a31e6e00670020006300e1006f002c00200069006e0020006b00f91e2000740068007500ad1e740020007300d11e2c0020006300f4006e006700200074007900200069006e0020006b00f91e2000740068007500ad1e740020007300d11e2c00200069006e0020006e00680061006e00680020006b00f91e2000740068007500ad1e740020007300d11e2c00200069006e006b0074007300000049006e004b0079005400680075006100740053006f002e0063006f006d000000070000000100040000000202000002000500030000003205000004000500030000004a05000006000500010000006205000001000200020000004e000000030002000200000045000000050001000100000000000000000000000a00000001000000300000000100000064b20000e80300006a000000010000002900000001000000923d0000e803000000000000e8030000ffdb0043000503040404030504040405050506070c08070707070f0b0b090c110f1212110f111113161c1713141a1511111821181a1d1d1f1f1f13172224221e241c1e1f1effdb0043010505050706070e08080e1e1411141e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1effc2001108035d029d03012200021101031101ffc4001c0001000203010101000000000000000000000304010205060708ffc400190101010101010100000000000000000000000102030405ffda000c03010002100310000001fa819b91819001900003219000c8c64060cb00c80000001922f8c7da7e43c7d3cba5d1d387afc6753b5e3fd1e3bbbf2573d787931af727f3f2a5ddb91b6b3d6ad5e62ef6b99f4ccf497e69f60fcfbd38fe9ee9fe68fba27a3670803191ab3800018c8c031960ce33800cbc76f5eb73e56f1dbd7c3f9f3eb6f9d60fa367e7dea0eca8c51d3cf16e17a1afc1abb179aab5e9b3e3167b6b7f36823ed13fc8bd24bee9c8bb1695e73201930c8c320031013fcc7abd3c75f9ae37c78fe85aa56b15e3b91efb85dbcbe516aaf7f3b19ca359724bd7c7be9a7aae5f577cebfc1bec5f1c963b752397f457b1fca1f54b3eb6e575930063235670000619c0063218647e7bdeb6fa74af79eab1d7af5ec274a4e7adb5d4f383d367cbee9ea66f33b1e861e54d57f10ee47aed118ab2c0b036c665eb5c3d2df43eabe69b9f70bdf14ebc7d65e73d046e018338a1f223e83f1bf3b31eebedff0000fd032fcefcd7d97c0f1f4f988a4df87aa8f17b5c1e9cb970dccf6f353c75fd4d9e13d27afd37ce2b9bcbb9b5fa71c796f9cf77839ba60959c0bdf47f952bf44fa9fc9fe8d3f4abe6bed53acd76356da80018670006327e70dea2ac691582e6f1ea4914958964a3216508b8af296acd5b96492c39b648368c8a29a2934d77d48d9caed9df31574b4b27f67e32e2fd3badf20f327dcbe6df2e8b36cd6d6e4bbd4b352bb9fa67f257ea793a3a6fa5785f23f4df11cfb702ad1bbcbb57f61eabd077f3f91e67a8937cbc367d9d0ae2edb54a9685df171e375c31a000000da68307aefa27c3b27eaeb3f99bedb67ac6708001803191f9d34f67caaf392daac92ed8b26f1d969c883ad5b369ed26e62c2c99b5acd6673b6d5a6ec18ab3568d35b05af3c93112cc9654b327123abe6fce539ab106b9cb1b6b22d9c46dc9e85da58b67f4efe6cfd2f64ff159f8f6791af3433411bfb2f143efdd4fce7eff0059f7f4aecb5c48fa30d51f967d0fe578ba0940000000013c03eb3f4ffcb9d24fd458f85fd1acf5ed3718c8c03e4f46ab686ade9228deb360e74776bd9147d4d97916259882548866422df7c91c16eb14e39abacd66b5a4de3c7968f63cbf2bc796fd0c33701406fa6c6d6aacfb9ad69a1c5f45eefe59f6ad67e7feff00d77657f2d50f41c0960368c5ee97a7b29747a37353cffa88ead7ace455d23c578aef70737025000000000033bc62ceb16b5edfec7f99e693f58be55f50b24647cc6b5c96f681dc913cb47dac4bc2e57b4f29ae5b58e65bb9de586cad1de7dd219e5c9124c91476073abdea86f663de3c5f0368f1a605006c6a01b1b59827dcaba18bdefb67cd3ec9bce7b3e5bd147e73f2ddde0cd67d978dfa8a58b53e9b8bb5ef924fbce53f9bf67911e26b18d00000000000000001b7b4f17bd7dc29fc8fdfcd4da71e9ea7a6cf92918f5d6fc548d7b1a1c0bb674a3a9aa5db9ccb674e5ab6ea5d648e58b6c6b66d1eb21045d7ccbcbe0fa1f93455c19a000ce326019ce24b379a3dca59c6d2fd03ed9f9ffe91acfb0f947bbfcf72c7a92e7d1f9cb35f619eaf4b799baae8441e27b7e48ad57d3f883c60c680000000000000000cedada26cc50d9ecf81f42f17654bf0db857f4d26a79c9fa988e65cc585b5bdde869cdb17e134cef29463e869250d7a35addb7ad525f15e673ae180a001963358119df497525c4b5a219a1b12fd2fd7f13d1ef3f2bf15729e6e0ccb8bb4b63eb9d9f2be9379f451c5cbaa1a67a44bf1dfb57c0336219a0019c67000000000000049629ed53daa12c7e8ca1d8a7a9e6e69636bd7c78858f25c8b746a2815d3add2f3374f45b71e63abbf2f074dccc1d2ad46b575fcbf5be7f2f2306340000338cd6046f24326e4d0cf5635b94ef66fd679fe97e49acf074ce33ace7193507b6f73f34fa56b3d1a17e8e9177b99d93cdfc4fe99f34c5c0940000000000000000037c3a07d87cafa7f09b9bd2d39d97a5d7ce4e9d3e8f1bb6e9431bc771bcb0c89726ab2d93c904d6e74cea90416fbd2f91f0fee7c2e6e04a00c98640cd605897496b48e684c75b93d5c5fabfc6bda78fd4ab92248e488c258a5ecfd3be57f4cd4f4143a1ced4e85fa08f9179d9a1c680000000000ce000000cb000dee52bb5f5cf0bea3ccce95e0b90a72a4da566c7a3e1f633d6ad4b95f58c4b9b3731cdbd858e69645ab15fac43f54f967a5b8f91f2738930250339c6f6631bc7a36d77cb49e293658630821b358c5ca76e5b5563decafb36ad312aa06dae2dafa7fc9fea567aee5f5397a97fcb7a5f97479bd0ce80000000000000000000def54b47b2e274f8fc3d91c5aedae7bdc82ccb3cd0e5ad9b4dac6b774b52ace962ddb3b0549eb66c5caed787d73e18e9c00033be99d49b5df4b2349b2e25c6d0931ae6c104f05338cc59ced9b22df6c18d3780884b9fa57cd7e8f67b6e75ee7ee4bf14fa1fce317025019c00000339c2c6338940000000026b74ed1dbaf574e7da6deaefacdfb105c9aced2ccb5ed6f7960b172c675424e9b4a19e8e538f0f6732f1be5feff00e7bd384418000df06a4bbe931a4766bc4bb64a115629621be9216e343a8c688cea4a067e87f3cf7b67b8a76a86a7cf3cecf06340000000012626af6604a0000000012dbad6abd04fe8b13a7271ea72d79abdd0be9c39fa8294d7f458e54abbed1cf9ba6da6b5ae9bd79af9df8ffa57cd35e6c04000cedad9b3693199628f68f52ced8db35aed195a3df41be9b9356b15e8200033eefc27b9b3dd707d078ad4f9f0c68000036c56046738cd8d6d75d7cf258a00000000026b7cfb967d163a9a5be8ec79d9d7ab7bcfda3af151d17ad0d0d52fcbcb94b9b535966bc70a4f05712fcb7eabf308a63340036bb4ee5658d62bef058b27c92b5da22aea0df4d89609e0a08000cfb7f11ec2cfa37ce3e8ff002ab3ce99ceb000322b7d3394d5ec7e847ca3ddfd2abd9c58aed0d3e77e2fdff80c5c0940ce00036d4033768d8af5b1f94ca7b0b3e36f59e9e6f3f62bbd9e34e74b6e78e8cbcdb05d5758a13f3e58953313f8db5526b6c49ba418bb4a5033bc637c31662cd6b92cb8c869bc45301b6a58af247410000f4be6bb55f5bf90fd6fe2b640491119946f66b9c6da63afc9fabe5eef7c5fd4e3d7f49c2395cc8be666792634000000000cb0589f7dee76bb05d2d4f8b7a41665b6b9b12dc5e76dd6919e43a50573a0e9c5272b6b38af9cf2fd1f9cc6b7921c1b684a0019c6558bd46fc6c64c579e91a0248e4d4d4000003b5c5f595edfe3df4af9a598db566819cb7b37db68ab3fa13e47f683d0d5f93fcf64fae7ce3cb173a92800000000019b35bae97a5929ea5fb3c9b275acf2ec57567e6da5bdb57cb56ae72b6b3b9bf12d497e9cd1a717c97b7f9c472eb6dacd0400000066fd1be6718c9a52b752cc0972c0000000cfb5f15ee6c9fe7fd7e418128c99db12ea20df43a1523d653388000000000000037b15243e9fc49bbfbcf88b3e93cb17a6f359cbd64fe437b7dd49e2ed1eb35f3b2af4abd7b155e49e69a783f77f2f99ceb62ba6825000000def50be6ba6b9b315ad55302500000001d0e780064c024b15e4d483066ed9c2cc636d650000000000001925ed73fd2d9e47d3f921f5eafe0fd86a72a8fbea71e466bb215ac599886c15665a514df4a5e25bb9e0f8ae97325630c80000000cdea12d6d88e4b114f01819a00000000006d8b305699c6d12e966aea683377c6fa6a6a33400000064c66411673830ce058af64e95ea152e69ea2cb3d3ba7a5ecf808acf4b57cf625f47170707661e617a31d245d53ba295ca6600000000df1de3cfe71b18962decc6b246604a0000338d8d59c9a800b75ad54ac6fa4a66292346fa4e69869a319c6280001933586fb23468b9db445fc529cd2c6735ac135bb39033736aaddb36464a626800009ad4592b6bbe2b5c491c000000777e9fe4bddcebf10abec7c75e4cac5959246604a000000000ce325ba96ea562686431a67119b94e6b36833a981280001b491cba93546b9a000066cd5d8bf628f6cf3206fa2ad6f0594a02500067121637db041b690d9bc44a000037d2e1f47e878fe9ee55f09eb7c966cb3d7b16410c91ca100338c99df796cd35db623d2c6d1456ea5b8368b54ef52ac6728d41befad8d4a8ca351280033890de160cb696cd759315032cdc01b6b316f7a77ace4ad55940dac55d8d40000b35ba0662923b2a37d170200000cf6393e853a1d9e2f6b7385e43d9f8ccdb32d6dd6be08000025cc3b56317a739796b00017a959ad59c31006d3d6163585410001926a8ac6f94ce36d658e296b6a6066803726e952b364dc2b35a5000000024df58c303380000006c5deaeb2596badc9ebd9cef15f42f9ecb6f4da1221280000373bd3f9b90dea6700005882c57ac08000000000de782c592662ac5bafaea604a000df4d89fa35b367344a0000009e1b854d6fd334000033800338c9e824c49732f6391d3b64f9dfbef0316a39602212800019922c9b4f5b6b378f78ccebb6171b6bb44f5acd6a0800000000646dbe2a3c4bac699df244ce0009672a6d66b697fa1cff7b27cc04a000001664c6966b092e00000000962ba7677837b2d74f93d1aabe37bfc08b75278170200000000000670248f7d340c8000000067129166498836b18b2b47be8a10066cd51d6adaf42e69fd63e51f69af830ce800004f05f330ef52cc633894033800004e69ddd26b24de1909ee52c279ca99c4d4b14b10000000049374baf67908fb5c69700037d648f4c0c8000000000cec6bbe836c6161996a05cd25ac93487678d2275fee7f23fa9ea7e79df4b79d411f46a9000092e45b91d5df400000000cdfa1d849f7db366b2625b77e7f53811cbce369768e780000032cacc62788ded53c92d79044b1b4b5566b12c72c5581000006700036da72a4e11e1a99db4c98ce06d2637b25979b657487b9c63d07d3be5bf50d67e2f1f5a966ef273fa273f4bd44c67134b671b43559840000000c977a389ec8b120d264c67cd7a3f2ab8db1b45de7c91800006658a4d4ce32d27db39e758cea6d8874310849a67158100003233666aa19b9aa66adcd65d21da4b2188940012462dd592d9ad9e4f613abeffe7feff73e6f0de637e6b4f41c149a9e702dd6b74ad3d64d44a000000b757b44d2699d4c3544b3d69ace7f1ae53ce924731982588000019c64d9aaa492b92787185cb61a08ce00019319cdbaad36d3447b800d331d6ba69ad8c6719a00002e55d4bb8eb71ebd47bbf13eef59e0732671f472f87eaf9b73e4f5e956df3cebbc76458c23025000000b7d9e75bb2657d0b79a9216f11d738ba92ad56b257d4000000064c3b731e7990c00c984b215b39944fbec47b326bb606da908b5877368f08c0940000000bda43da3d0faff002516b3d3e0f6b839eb1d8f35049ea38aabbceb1e8930250000004b174cb79d63b99631732c5293f23afe74884b26d25630000000003bb4292b7d71bc46b1019b292b49a9ec9635ceb1be75896760634d22d4b6a5b4635ce0c094000000003bbd7e251b3a9cade44f6d47d473357c4ddad6b2eff85ef799ac63388c0940000024eef12ea5fad620a87120da4c4c41c4e973612477174ad246000006ddd380fa7fcc8c13d906f34c41bc9a126332491986904f82b6d2c16627c57977923db7358b6cc4633400000000006fa0f47c5eef17599331e92dbcf3a2976ce92198e48ecc0940000000ded52b27560963b9d3494baccd53951cd2cd6bbc948d00019908ef53fa0cbebfd970bd3eb35bf33fe9ff0082578f9ebed5736ad2e526b8ccb9c918d618b7338d46d2d766dacd5dac61aacc8892c6c18128000000000c9dbe7dea7a519239634d6ec52579e392d8704000000006d83162bcc7475db173aed1eeb2d5af0c4b986c2e2bdbedc79997e857f3d3e77dbf532e7a7cef95d4ebf5f3f99f5f62d27a5fa27c0feed52fcafea7e76cfce2923ceb6c606fae06fbc22c471ac09400000000000000000124739d4a96a0b39825e9dca976cabcbbb40c094000001247df3876768ac8333d78e861a9bc1dbf4b35f3df47efe5cf4f33d5e8633bad8b11e771c73c1351c7996e69fd3bcffa2f4f8badcae37ae3e53ddf41e1abe891f3ee9f00f37f5bf92e2e0280000000000000000000000066d57bb64d2c37d38b5bd2c0b575ee79f28e0940000000bde87cef76cd3cff00d0e859e235ea529a97e8b27739f6d76d37c74d75cc6bbe98d6378f186b1aed0c9b34adbe7f4ce766bfa3cbe37ec1e2fbb1d0f9dfb1f919f40e979dbda4df03fb9fccf2f1e33a000000000000000000000019c6c66681a97a4ab3c697789b1eafcad9a8604a0000019b515d4d3104b65cebf9fb0bd7f5ba7639f78a0abcae3e8f45232671be9ace9aeb01b3122c2cf3e4b14a68b59eff0057c1fa0f4793da55e4f0ace9fcce8c59bef7d4fc8fe9d659e35ae2d7ceb1d9e36340000006da8df5c19c0000000000000338c99ce31a92dca37a4e762685a963dfb11c35aac6000002c97e6873673359ab45bf79f38fa8e7af6abc5c0e1eac5cf37f42d73962a389bb9ac3311cf8599a1af3d37c6f72cad0ef81c8e8d0d6387cfefd7e9c7955da267d7791dd3dbf1294356f8f2c528000004c87735c000000000000000067056dd0e774ec979beaaa25ff00b3f8df7c70fca7d03807cc7ce7d5b9c7caf1f51f232f9b6712edd9a9d7b2ad6ebf30a75248e1e87cf6d35ed39343e9bcfb4fcae870b3d39f051b5be7edee6b173ebad6de3b28cb2cf64472cd6c72ba9ac6f477e25cc1c4ce9be58000000000000000000000000000ce0338cd19c23a7cde81eb2af66a6a7d72d63054e176b967231763acf83f55f2e8a7259b38d7520b1a5b1697ae9e2a9fb0e3b3ccd3a7ca8f45f41f967d079fa36f3963cb12fbba1da8e773b9bc9b3d576fca7b3cef6669b54393ac7be37a6a1c2d66ef1345c600000000000000000000000670000659d400640d4c74695d8f7f5aec1a9f5e8fc1fa4cea5e6f5f85acd0d378abc6d1b3c8c3d3712bc33a5aaf673734ae51ac9e8a851a0b244466e52c96aef23a12fd238b6391cbd1cde5c9e8fa71bb626f35cbbfa3e770e8f5e3d38b930eb9eda000000000000000000000000000000019c0049a334e851e8d9efa0bf06a41a4d2797d98db7f9ff5f3fd3783e077df39b343a99d661f555dae1dbd35b8938f2d0860500002eeb54743e89f34f5b8eb9e3679699a66f9e000000000000000000000000019665b20ccf5e5cb7d2b0200db32cb6526dacb9923cd99bdcee99eff005deb6a5e9e39fcfecadf3efa1784ebe7e1cb9e8b31d4f6bcd5b37fcae57d2f91df92ce000000000248c6da800000000000000000000000000ce06d3c12ea59ad3e73696f1e6989890338973d0e75a315efd4a8b38cc2782dea77ba5e16cc7d4a78ac71f4d1f11ee3cc74e5e6fadcfdb5cfd343c8966b4e36d51000000000000000000000000000000000000000339c66cdf3633514d0c19b2d7b311992b6d566acd2a549a22deaed6c8466ed768dfd2861265f5a921879fa35f3fd3f33be5c5b35ac5e7d3a9051350a00000000000000000000000000000000000000092392ae88cc72e2ce7aed3973a026806fa06d245b59a338956aad82bd88763ea15a6a3cfbd6f2bd8f35d38f67d2f27892d2d0b90000000000000000000000000000000000000000339c66cbf98e55db38ca6b1cba9cfd2fd15c08019c0ce5bd45d7e4fa93bfe4febb16b3e4a9747c6f2ed5a9e926b9ed5c00000000000000000000000000000000000670338000001be9b16e682c5631669259df97649e9f435978e96200013c1291fa2e1772cf65e9be4bd3b28701ae373d5db5b000000000000000000000000000000000000000000000369b5dac9ec72f53a54779ca3676ab2dea0c9a80003d477ac74b79f0157d9f9c8f3033a000000000000000000000000000006e635e873c0000000000000009ed52b766d5ad66398bf0ad79e2d0b10243a5caf61e70a0074b9feb6bd9ef8db798e974743e3839e80000000000000000000000000004a6b7b1ba6797d6e4a8000001bab410000001bdba77353736cb5c6f0d6f88e025871d697ad8db6d4f21afa4e566d2f5fcee9d9d2ef791b367a9d639f4f8b8e7a00000000000000000000000000770e55a8a5492c56dec727a9cb970140000df1a80000006719acdcaf650d29cbd49392b3b19e4652fdee17ad58e48ead5bd348c9b30e91d5c73ecd96fafc5d2bfffc4003310000202020004050304020300020300000102000304110510122106132030312232401423415033341524422543073536ffda0008010100010502fea2cff1dbdac699750babfaeab0dcdd06d6312e9e7312cff5a3e93cd501efee6e10b0d39ea98b5859888f75de29c50be19e01c56de1b6e16457974ff7edf6f105e9cc95f76cbc55baab12dc78ce34b676ea9d46798658c082d2a3a6b1fe9c6af713eb3c130ff4e3c41ffe8eaeebc1b8ae4f0dbb83f14a388d5f97d4b06bd9b2c0a1b3745f3e1e24d178934ff92ed4f1146955b5d83f0b8fa05cf695cfe2ea51e6460b0b2ca2c4f481be542751ad0b9e1784b8e317efe3cfff00c3531feec2c9bb1323c3bc61389d3f8a9c563713ed5f12873d3a73789374a7126eaaf8936ade22f31389b6e8cd561fa9ae365a4199dd7214c6c84d6564831df659a33770d1c982d7438f9eca71f8a4a33aa782fa8c5bab6f76eb6ba6be13c5ff00e478c718c4fd452e3b2af6118cb3badeb2d5efc80df245dcc5c77b5f0716bc644f9a46a789ede9e1351967df31322dc5bf8778b3b60e663e627e1ab99e634a72350e58d5d7759957c3fc56dd2d5e605873e2e76e0cb8336365b19e6b34eae479346866c894e53243c45b54f126de0f13956654e11d5bd9e33c67178757c578a657109ffe3e6d714d0238de11162eba618f323e089d0274013a445a998e2f0a79479752aee26f61f53c5d77fd6afeef4635f6d16f0bf15d8828f11f0bba5362d8bf80208df1b890452442f37cb714c0603018a603ccc263733cb515996559ce831b8a1de17105b223061ccfc6665e361d5c63c556db36d63e4765f0336b8f88caa4716e1ac91195965b2fdce981099563b58f8bc15f4b5538ca2bea8a8820026f50b4e3f91e7e7afac19c2b8a65603f0ff1563db3173b172bdfdc0d19a2f728396e1301e46080cdc53162f3630fa0c1c88844d4c6bdab383c4081467ab45b15867712c3c4af88f8b1c8cbcabf2adac7741a969db7872efd3f1c1c9c02bc5785ec831fe5f5168363e0f02257f6f1abb0dcf3c9b8c5c5b62d2526f537df3af1463b12cdecee576156c2e3dc4f14f0df14e3de6ab16cafdeae0337b31b737034dcdc06095c5e66185611ccc1cf502c5495a912ecc5c55c8e3998f2db6cb189d851b20010fc3fcd7b0f8167998d09d0c9e23838e78b710e077c6cea3783760d8fc20f0a22dec9e5833cbab47cb12cb165afbe42788aef784e17c532b02ce07c6f1b88afb5e5c2935179208abd9d232f31162c48be931b98115674ce98122d72d3552995c54c7b199b90dc002cea3b3f131875647091ac2cac8ab1eaf10f19cec955eebcd4953c3bc479f8c387714c6ce46d3070d1f6615d4d46fa4713b3cdcdf7eab1eb7e09e2760b89998f929ec350a4594012daf5c96208218c2149d11522d716b8a9144d4d43c98cfe674c0b1609a9f4a8cae314d532322dbecf421d18bf51b3ed9c2c6f3a965a30f8d71339d957525b141fa3d351757e19c6edac6364d77d6f58d3564c2ba1c42ef2ab2767f014caeeb2b3c3fc499f41e19c7f0b308f51b904760d2dd4e8889153b159a9d30a40916b82b8ab35353535088d0f253179a899b9f4e3cc9cab720fa93ee1f2a06ad3cbc3eaafc53c41c50df31312db1b8660f95389e3fe978911db9e36335b31787a20388845753e33e0e7a343d2d320ea71ebbf6ff0831136a4759df08f1065614e17c5313882fa1a9658aa6794762bdc5a8081069c08256a0c7ad601f51ed019be439086308c27f2a22f69758b5259c51cb5dc4326c5f607c895fdb67ccc7764b383d35ffc2e063d7d3d0b3c49ff00f436fcc03671b01b745317b141b86adc7c20d31c64d4b6dc96a7197de4fe1ef9d36d955bc03c4dd711832f2c84ed555063133f4da9656562a961650cb0fcd2fdbaa2fcc3f222c5e66111c724e5c6b23aeedfb88341bee9c3b1cb1e089ffc270e7faa78a3b71e3cb8260eeb5aa2a6a748dd6b0445ea992eb55794cf66564586dbff001c4e0bc6f2f0457c61ed96788326b676dae3195388e565c01942096a8d64a7d6aa600749da03dba62a415ce89d302cd4d4b04d4512c6f2e9762cfedafcf7e8e5c217787c2aae8e1d59f2729dd52ae3795fade27cbc3d7f9dc30cdcac18b2aacb4b99694e23905d9c79587f90275995ee70dbbf55535ff57eacacff0090ecbc48c19dd7133008f97d40b6c9d74ab4ea954fe2b13a6110c02186149e54559c76f5ab13dc51a8df1cbc36e8c98762b559b4754f15f137e9e7e1fbfcacef98a92baa51447d56bc4720b3535166f109f2b87fe4a8d903b6e58f1ac9d7146e01a9d53ae57676f322b72a9a0695f230cdc2d2b3353a44e257ae1e35f635adeda44f8b3ed83b9e1d53a9c2c970bc578bae2f0967667e78a759359faaa94cde9736fec475363a76f1559bc9fc9a963b8110169c46a2822cabe0f2ad0ce98a25622d7d96a3b5a4c5ed04d461351962769d7a0ae2788b3065657b83e13e6de54f7b786d3a4c6a44f16ddbe21e8c1e9fd6567eba4ca0f6cabb4b79ea9527744fa78ddbe6f14fc841b666e9812769c5e82d4fe9ec9e5b894d3631a787970fc37a6793d10af60bdebf9ab5a4035a8408208440b08844d4c96f2e827dd5f8961e58ff00e6e1e3f6bcc4a2ac9b4dd7fa01d4c4b3cc5a8c56e9aee6dc3f38b5cb9bcaa5cf53fe4ecc1b69d5d32dc7560d855eadc35de1632f5e36322ae4d00ae6200489a84ea536c4b60b675406754ea9d50b42d019e23bfa71fdc5f9021fb5fe6627f9b04fd1e2fc834e1b1d9f4f057de3e3c7fb5a22ecd2ba5f11dbe570afcbd99a31ec5d1c84d5d92bbc5c855b28cb4d65e6274e55e19babb33f72d11a2d916c82c82c9e64f327991ad86d89676e2991fa9caf753ee2cbcf0bfd8e0d8fa4e3b97face25eae08fa6c5f87f87940ee3b2f8c6f1e57e588804c8760bfaab035f90f06658a6ae26e25bc46d795daccdb3a727ab92ee2930404f331e698ccab0d18fef209ff00ae5c257ab88713cafd2f083cfb7a3841ff00b7827b3fdad31e37c7886ff3f8a7e583a89ad5dfe3b9bf72c31a2983e31fef51dae1f57258b041072300d9c6c42ebe2b5f26df7445f87e7c29ba33fc517ee6cf48e5bed35f4ce18759f827b9fb1fe68fb73eff00d3e1d9bdfe58f94f873f464ff9c26e595887622cc6fbd076c91df50080402288a201088655f770be85c3e2f9072b897a84fe3d0bf6af4ef4237dbcb0c7565711bbcfcd6deb97fe473c56e9cac43ab7e6b7fba9f8f17e474e19fcc51b30d9f45c776031cc3f28b291a656ed71dc11628804410080422345fbb8a667e9bc35ebd41be64f695fc9f810f65e58afe5d83e5a09fc0806e1ec79629faaaef4d9f7553c4991e7715fccabe6757d37bfd66c309262fca45818cf9e49144022cd7231e28efe22c8ff00abebf93ff86e42749d28d0fe25bf3cc680f93d13a674e87f1cf08ed317fc791dac6b4534bb75bfba3dfabe6757d37773008822f21162888b144022f331a27cf1b6eacff63f9683b84fb3e06beae567a003d03e3f9f88489bf470dff570fedcdfbf8fdfd1c33f36bf95d685a34ee26e298b1601c9445114458041cb51b73a4cb08a69b9dadb7d4392ea7dc08313ed5d11df637ad0967dfc97e7f93b041ec0f727d3c20ef030fe389f69c7adebcaf7506c9f7d27ccdf2d18a0cad4c543154c1598b5195d512b8b5c154f2a2d70d73cb82b13c4f6f958df23d8d1017e08ec866845821960fab92f2246fd7c08ef0b127193a4b9cd96fba7e8fc0af97e9e263883121c5d4aa8831e793a8b56e2d31575144511674cd4ed08e5e2b2dfacf63f85fb258742af9efe86efcd637b3c03fd3c69e257d62fbc4fe057cba06900dd6069d205ef58ec56289a862725301ec5a7546221613c454f9f89ec563b0edcac2369bdf3684ecf25f9fe3d8e01fead3f1e297fdaf607aa9a6cbac1c1f88f9ce8c8fef061a368d2d9deab469acd85795bc769d73cc8f64164f3279905b0db0db0de635a4c07aa5dfe5f508bf1c99b66ad7a0ea1f9e4bf2db1ecf87ffc49f678a1bfed7b03d1c3f8665660c3f0ee356b556684b05938cf0dbfccf793ede48d3acc0d11cebaa6e7543160e663184f2abe7335faaf52fcff001fc9d74ca7d0fbd7a3e47b1e1e317ecf10b6f897b23e786700cccb1c3fc3f83405aaa4175b580f6cbed6238fdfac4f647a5186bcf13ce112e105a20b22db3cd9e64164f320b20b2079d71ad965d1b2226477cdcee8a3d80c44d9e74fa1be3d07edf638136ad5ee9c5dfcce27ec09c271ce4e75607435c745843d51e6432575f12cafd5e47bfe63cf31a55634adda2b3404c52d06e0dcef141801804223acb2b31a93168332fb6445dce9dc2005f62af8e761edc97e66fb7b1c28eaec73b19077910fc73075cbf83dcf83516bc45a1da250aa0f4f4dcd388f12c7c499f9d7663fe00ae0ac45495ac45edd315622caea9e40d7910573a27488521ae1ac44ae71452b9f0311030018efd94ecbcede69f77b5c3c77c6b7a683dfd7a8fa02b42efc07145347f16baa2f13f10e0d073f8de664fe1569b1045dcac4ae288a911257da298a27408d5c2a66a111bb446efe200067fb89f6f373b6e49f3ed706afad738f9181eb5ec2cf9f0ae3fea38a656761f0faf88f8b5cccdcecacc6fc3a7b20ac408b15162811351488ad15a069d712d312e81b73e6148e9324696dcd7a9f22d7bacf70736f8f7b807d9e26b3a717d4b2df998997918d1d8b37e22fc9b65c8c87ccd41744b0c56317713712013460533462b9112f9e70319c4b74c38ca79791fc7b6bf773f95f7bc3da38fc5723f5399e91f08010df307e38526595ad832312795d317422b081c45b444b9625a2798b058235a235b1ad33ce78b73c4b3738c5de767052411af6d7eee4b3bf4fbd4e43558fea58bf1cfb7e2aea514295c3cb570bd2e2ec7046487acf9ad3cd682d782d7897b417344b5a7531803182b305316997f4d1439ea74d0463b3ed0f983b30dc6df4fe2ac7fb7f2284d1a7412ab1ab3819fde9b16c1938eb68bb03bfe9ca91545a22d11688b4c5ae2a09a0392cf11e4775d43f3ee6c4eb83e0eb7f823d15c6f9e5f3f8b4ebaaa1dcddd1cf0f31eb98b9aaf14abcbe91a6fa4a3081845713cc59e689e788d90b3f5020beb55c8b0e45fef13a117ed3bd7e0d5ad37dd17e4680276797f1eeeb5364faa9f956d0b2cdb1f98ba2519eb3839f2cca52b9190091910e51872cc39661c933f50679ed3cf68d63d93e17dcd1d7359dbf0abd68fcc1f3cf5f4fc9fe7dddeb929e923447488ca44a874cb1fb28eae622f752b19acf694683fc7b606e645391ff001f0725f97ecded0f987d3f09c9236f9ebe8ee7dc0a4cfb4124fa51a02ad0ce9dca93b73ffeb58ff6fb150fa8c63f57b7c171bf5199fa147af2e96c7c9fc5ee57924277cc4eb03da100eff0a7d63e55b6e3b445771cc7d8bf367dbec20fa5d499d2d3a4ce93ed78693a29ade78aebd677255ece3f089fa792b6bd07e91ed02624277ec83a29d4cd510abcf66552cfb7d80089df5d4f03f6eaedec28db621aeaa572167895c3e341f03babfcfaf462033a7e9f2ccf2fb749f4ff00f5fa57e4eb5ed7651cc2ed4fa80dc4fa46cfa6b3a2df6faebfbf93bfb78abd56a132b9c6ff00d79de57b0aff003eb43011a2c361d6757d2350f4731f2df6c13f8e49f71d6bd9f8e60133a608fbf529000259ab5d8f40ec7abe8f5e38db4b37af6f0574b54afede3a3f625626c43ec2b113e7d96e43d03e7ccedfc17dfb206e2a68ea341f57b1529dab002e4e93e8dfb150e94848835af6546cd7d96a951edc73be345f81dd3daaa92f2cc560a7b1f49fb7f02b100006873761eb51b85f42b5d8c9d7b89adb58cc7dcc44d912b957c7161ff4a57f6b8eded25c3c9c3cdf22bc9ff3fa5bedf7c45fb39368476dfb1b32a101ed6585bdce8edee088a1144494ce229d58110fd2ded11019d407ad8fd3ef8f907f6f7fb7367d91f2210dee562124fbcbf0224a0f6ce3ff0046283d3676f6bff22326e326bd23e5fb2fe06fdc104c4acd87dbe9dae846f9f71476022ca6677fa507d8fadfb7b33a9b96f98f97f8f7c68ce8edd0668cfa75b1e95506041a283a655dd7c318c2cabdaa446e987ddafef1018b2af8e30fd38b17fc7f87bedf82366741d761eb57806d5d653f1e185d70bf6b440eb07dec55db8822caa719b376cffebfc3edef833b694a80351fe083ec50fd25936bf6ce175797c33d9a86cc75f7514b9a6b0aba822cae653f99913b74fb6aa58f92d194afab5dbf0ba8fb587675435eef4fa47b28348e7b7b952f53a00b3737018a65b67451cb7a1edf07e96cbe278d68e299d5742fa4fdbf81dc7abaa0e8d14854f3adba5f0156de25d7c80261523d4836d2c3eee28804d4d402013889d57c8fc7b6a4ab79eed75b6358de97f7f4797dca091cf5bf42bea23033cbd8642bcb86d9abeab095959d88e9af4d43e96877eee30d5639a980ce22dbb608df6fb604e93ae969d074120ac429a137f4fbbd27a50ed596560edbb1f4a1d164e55da561516230d37095eab69fb5d3501d4adbb1ee1c68f31d87bb455b83d0b165edd56f2b7a3f49ec2fcffe408a009af41f866044df6f6b4668c0098a3e9eea7e47534f91ea56d4640566259d166757a9c1934b4fd8565a914e8a1d8b87d3caa1b696376f72a5d26a6a01008a25c4255c87cb7b23e77d97e399204679d7f4f2fe3d941c88dc55d7261d53ec2dae9f62b7286c40cb2b7f331783efa68ff1cb1362c42a558ac2c08e483e88c767dbc75db2cdcdc066e219c45cf3afeeb7da27b03d83181be9f3213b3ee2a930a451a1e83090431fabd9c6b3a1f2aaf29f18ea7081ff005b1ffc66b222acb93b58bd27901b24cee14fcfb42535f4a6a6a6a6a011665b755dcab967cfbe06c9f64081468023d6cd37ee27fd9c103a57848ffad8e3f6dea5d3d32c43ab9211a329e9e9df7f73147ee0e5bf431e942767955d94f73ef8f6150c0a00d0e626f9b36896f7b12df2acb9b73852eb16ab0047c982c0c1b5ab6a0c2eac88146cfd2093d3ee5034826e6e03045998daa799ed5fb98784d78fd0838c7b7a950986bec46a55af40f4efb13efd68d638e1b798ac2aa9b342cbf193567d04dda89729177410ea16750d37cfb758ea71f0219a804022899c7f77920d9b4fd5ee6265257855dce2b7fb873036420014f706100ce91af413a0addb6213d9893f81c257f72dc9d4b32099633337ea55d32ccb5cc567886c22d627dec74d299b9b9b80c0606ed6b75d9cabf8fc0504c35c3da54068f71d241479f303433e20ee25a66fe9809fc1c42568b2ddc4fb60c4d0c9a480d5ece3e3ee2515d75da76fee27764f861089de77804d4bcf4d3c93e5fe95f780335a54edc993700e9e6c9031585419b227661dc11a11b7f86bf352eead771f112eac8cc74e9b2c1d745fa9c43303d1bf7429d51da13db7cd6099addf952a34edd4deb130b8467e544f0c04c5e4abd80faf90f9e6796fbbaee699610187713abb1f9f9847e1f0d3d42c6d581c42f2bcb65966596527676613ef751d631eff00c1e6b37d9cf53ca9372d6d0f5e2e2dd913c35c371d386a2683099959a72e57f039ebd07b4dcdf704efccedf499d8c2346068ada875f85c38e9f2874df146e5834dc906d98fd5ef63fdfb879a89776af5b81140eadc63b3e9d289fcf096d61786dbf6230ede2da7cae33018addbad609b1be4e4cdfab7f8f8fd9f37fd98ae57d0bd97dfa3effe0cdf2ead477eb9d73b1961e695d8f2ac1cab26370724f15aeba72946e5286cb7199e89e19bb5c421f8f1e63fed720796f975181cc0760eb5f969f753f39ffec722a1a7946749dbfba06f9d5f7f3fe2c7d983e6ce95baba6fb8e3708b5c53814d53a4003b72cefddcfc4e159441e0d9a0f1ac67b0f0acaf27395c18671cc7fd570d3fd2d5f35ccffbf97f28bb1903a149d9f6d558c1d891d4b17e77ced794536dcd8fc12e694f06c3495e1e3d53a7b7f2443ad084759c0c45c17c75a2aaf4bd3918555abc53015a706ca76a7af62c1b1e21c6fd2f14fe92af8596d7e607a88e5bef4b4cd6dddee54a16bc8d4ada5a20f9e587c372f2262f05c5ae25688baedad72fe218d3530ff66de0b8aeb563a2db766f10f2ef1f6e6d6b7d43756463d9db7b1e33c50f8dfd1a824ac328b1455f4986b13a4c7ed5fb9889d4ea9b97e19657add0f51225219db85f0bae85df7f41e5be6df03eaa30bac53c4a9cd35f09e1ecbe2107418284e223a71b189e80fa996ab918f7a1aadfe8818af372c3aaabba2dbdbca9967f73dcc67e998f7f7aec465c8a55e5f8fd312b66b384e02e1a1826e3efa566f919fc473a80cf8c8c4cadd5939191526163a60ca32d6d378aba73f23a93875be76275185a789e9e9cafe8bbf25d4b7fc3371ede9a98edbdba44edb24894dc44af2770fee9e1bc3d3183b6a2b4077c87c7227b1680cfe4c76eee5fcae1d925e8c13b7b92ab5b19d44e319ab878983617c2f0ce4ffd76b0cea9c568fd4e1fbbdba7f14c41ddfbd5cadb36bedd485d993a47598ac1a28d4ad5ec6e1584b8a8ed326de85a2d36e4a8006f918c636cc0935a8edd8d84c63f4efa976f53d1c4b19e55914d733f8c6252f9d97765db5bb56dc2720e366ab12975be5d78f9765d95c569f2b2bd9009fca5f91dd1975cac3b6f6f1474ad9ad59f729d10e0ce0142ae36fbdd66865d9db8363c309d42e61304d084812cb446b37003d6c227c9599758b1337eab02334602b8493cb87666ebb2fac84c8c9a866ded93ed56dd2d67cfe4098f1e93a646da233bb70acf55b69b6bf6695d910cb179f09757e1d6be865e4f48c2aade219248456c905bac4acf542bb9d31b52e6853baa6a0512d6e98a3b58c633346db4b32bca0edd4dc918a9f3d086b963bb3fb7bedf9386dabd94c6406784b17cfe29e5a997e386199c2b16c993c14096e1df5cf8f401b35d7a523519f51dc11cb84e77e9a64e6a344aaccdbf169af1b1f36c26397488ec5b0ebe94ec23b4668477022772cdd32dd4a8ec381a73a1997010924ff5783f55e54e996783314d581aed67c644b7bcf2b71b06ab0717c3af1e1edcb15626a1008bab8ebd3cd652a6eb30f1abc4a6c6999674cb8b19c27f73297b2b346847651b3a0177a96bc6ea708028bac993780acc58ff005985fed1f865ef8952d341971ed7778cb152748d71760f937105f1abf36df27b3564450d2c56d5db806e74340753808ebe24cd2d7e91674eeeb0df6f0dc35c5ab2b2ca16e20651966eb3ea78aba8fd92d72aacdd6b429aabb2ded9390233163fd6f0ff00f6c884458d2d8e23ced388e5fe9f1f22d773fa2b3a71d3ca897767b44a5d4ce85617e3020d5d26de9ad670fbff004f94f60132afdcc9b19cf05c2f22bbade919366dddf53817d76880cc86256eb3ccb681a96e4055c8c92ffd7afddc3ffdbdf20237c59f371d0679b24713b0e4645342f9f695d3eb7a8e0c476595e499fa80c2eb904b1cb9e55e4da8ad7d8d385279b9c5fb64ddd9c46fdcb3071c62d1fa8eed91a9959110cc9b7cb8c4b1feb401cf03fdb2396371ac9ae63712c5be3e8cc81dfa3be530ae9665a516f259ed6215bba9865a226c466e91eae1f7fe9f218ed6cd4c972c782e19d5f2dfbbaacd5ae443637f62219c37fdb8579377155b91405e32032e4d570cfb763319b7421e93599dc4468bde32896b8dfb15e4da88f7d8f387d62ecc3680b916ecbcb6efeb84d1d73fe37387ff00b7b9d5c91632f6ce60f91bf2cf9f64762c716c12aa83add8c23d04103a6645bbf731acf26eebfd45563685cfbfe9868c74e5fc7a69f961d408d7a7867fb8da8608a2647f8ee5fdc6dee84f32c7c522568caf89700be6298eabacdbbadfdd56658493fd3d53f871cbff003e95f871e9ab7d58f9bd48978d08bf17fdb76faed138526ed29b175023f5214bcacc8cc6b13fb6ae2fc37759b32b853d1518d1975e8c71f5f7056f6027c2dbf6640fddb7edc2212a5b06ac712e65963eff00b70bdb4c223f2b47207515e1413e3955f32c20fa313be4f2ae34b7edc9500dbad83158cdee649dbff6ea3b6a3a4ee0870632eb908ad0804183b10db24ec73c26d5f1627684cb1a669ed636daa6dc41332ce9fee135be6ebb84688300d8e42c318ef90875d3ce8f994c66d42d2c6ef98fde9436db6d1652de73202767fb751be7fc7f046c30d1f6ea33514ea3ec92ddb21fb6536db83216cccf7a531d8edbfba107c01350af5461af6f8553e7e66470c5e9bb05d5ec3a07e32df4cc76784db8f4636664364dbfdd0ef07310c75d8f8f583a960d34f0d1e9cf5d10f8ea4d8099738519566d917a8dae5ffbb5f951d97906104d42b2d5f62ce5c3bf6930b384aed52b90dd35e55ddfe493a1fdd888dd51601d9ebd45765897452ac1d361d7a4fa8f74ad4bb846a903ca731d57372b6df558d754d57f7eb645b62b031ab53190af24b9846296a9edebe014f5db9146c5d5953b8cc4c566584eff00be450604da904725b59679aad1ab5308d72d93ebe134f91845773228dcb71c86fea80d9fc5523535d993d02c3348d08d44a3789e8e1b4f9f9839111e9eff00d4a8d951d0bf8abf23e20854186b9a3cc188a146655e55dc84f0f280c07f58a3715751fecfc55ec57e396f53a818aca66ab319069416387d2833eaaacac8d1801271ff006a9c7cb2b2b60e3fa945ea806808c3f6fd90099a3ed88bf1009d33cbd1f2e1569dc4e1abb6d4e9ed7e3a593f4b64c4c7f2db535db1ad353556ad83fa745df24800967d9ececfbbff0092408186faf73a84ed3b4b5763041146cc1674866dced0c569dba162f5a9ab27e9fe9785529659f0f0727ff1fe285fa7e03af69b33667534eb69d6d31fe9c673da7c027b963a1013badccea33aa7ffc4002711000202000505000203010000000000000001021103101240501320213031324104516070ffda0008010301013f01da7f1bf2271b2717014873359a8fa37a782c296967df249593c24359eaa2ec5c16162d7865d98851f06f86c3c5a1e2c5929f1cb615ebadfb1f0ca872bcb50ddf63ceb62b6d79dec56c287e85b15ef79c7e77ae01e19a0d24224a3e4d068341a071ad8ad8499625950976627cd8ad83c4359a91a8b2fb31256f81e99d3474ce9942ec7f760b63ad9d467519d4359acd6391a997b05c52da515b55b459d0d70f65e543e22cbe1ab895ec8c135c55be2d714b8a5c52ff005cf8bd3c5ca3c4b629bf62ff000b5b44ac7c2a42c367490c4c7c128b1618a09672f9c2430ff6caedc47e3b56d57a70f0ff00b3e77495928b5c0e125a8f827abb2f3915c0274296b3e176f2bcacb1bdfd1f32c226c847f6c94c8f918e4396e9c6b3437dcbe92656a747e2a894cbddc89a5921c7d184e894fcef59310d51ab87965ab8764b896c65f8e224f8a6effe51ffc400221100020104020301010100000000000000000111102030400212213141500351ffda0008010201013f01d4fede8e2e0e3c9721a3a9d483d1139a08208208ba08c1cd4a1899c79b179af59a3a2cf24e18bf9ff39f2a9c692c4abf7f0f97f3917062e3f83358a2b53abda926de5456458f14eb3aba2d2791bb96cbd1e363b9e55926f47ca4d8aaf4a6f777110fd0ad5a5d912891f21324ec763b1362205491d914547a0951d66ce362121fb26d9aaa3bd61ea753a9d48204a902562b951baac3e96193b1d8ec48f02b10c56ac0b1c10884410455d9f2934557621dd1578270c91990c5456ce9ce643aab2749e1f97a1d8f3ce77b089248caa8f0b11348c8e924924e2421e2431136c526cf59e688789510f2a77cdc8747962e4b335721916acaab3a8c5a5f08d678de6e3a0867cc4f047e13c5241155ab0410c8cf3556ad0549a460f148bd58b4e71a1bbd1e85ca890f712ff49c50244e279e2690781e198c4b241e8915bec6b1221ea2749f035581bb249c33a3d8ee220559ace93c4d9d89a338fb3e7e02228f9936f0f76bd49c3272e77af02e49d245a7347879bf02f27abd593a7f2c8228ac8ea7b22d812d54359399c51c9fc38f11d1216bb1513ab15e91e8f625b88e2c9c5c90969c5e854471ac6e7cab56a24471de44dd145442de58d0b7b8e1544b7d6144fe547e42dd8bbe5eb5a32258ff00ffc400401000010204030505050801040105010000010002031121311012302040415161042232718113425052a11423627291b1c1d143536082e1f0051524637392ffda0008010100063f02f843bc93bcd52ea62e1658a2479dd5849774c815dde089e6ae8d67552e0ac57fd2a3a838a27e8b3b9358d1de753c91633fc394ff000b354c227bedfe4754c8f01f9d87affb00a8adfc58ff002b2b9b26f05cc95203604b92e980cb7e2a6506814e0107be462b857a2ed43ffad10a708cd84f799c0a0f84fafbcde237cf10fd7528aeaeb82aaeebb7398f784f09e1de1359a190aa34035827d1088fac4e1d105da67fe949145363c07963dab2bbb91d9e36cfebbb54ababe06aaebc4bc4aae2aaaeae31babecdd5d54aab95578c2a386a97c5786b45c951e1c23f710db4eaa6df1b3c3fd69c9a1597b38627ccf25cde78e31bf1507eaa48e0d8d01e58f6d8a0ded90bfe6ce3e883fb3c50f1d0db74babaa95757d1be9dd71552aa68af5543a2733cbe31b30231233a50c78182ca30e2617f2ac9ddaa03730f7da38f50a60cf62f8d97341b22e3c8059bb4bbd9b7e4175920b0346c42840ddd33b4224088e84e1c5a506f6d86627e36a0df6ee844fced920e6bc381b4be194385e4bbe54da67b3ed3b4466431d53a1760061378c43728b9c7338f1359a0c1c1347ccc70c3c211ed3d91a08bb983deea14db8530aaa0595ad2f772689a07b4bc421f236eb27668619d78aef1d920785941a3382fee9bb4d901daa1be09e6da85381da18ff23f0dba96655530517478cd6f49d516f6285947ceefe918bda223a238f33866c3b2c426433c8fad10ae0518fd9a4d8bc470722d70caf176e160b2b1b9de6cd083fb61a7fa6cfed7b3ecd01b0c740a5c30baa9c5cfe3245c6e74e6090798425da4c46fcb12a10676a67d9dfcfdd41ec787b4d88e3f0ba29c47cba2220bbd9b7a5d6788f739dcce1254c2e81179a86ee246396376984d9f0cd5fd1777b4911059ec6152f699bae52a7da3b6b6137f219fec88ec51a0ba9dead5192ae1c364421c75e702290de2c3628367923f161fe39fc273c47640b276712fc4b339d99dcce32c2d8b07e250bf2a31233f2008883385d97f0dcf9a74f626d241e88322bbed10f93aebee9f277c86e150d769e780a6e01ec716b8588420f6e9ba5fe41fcacd062b5fe5a3296f999d2002cb01bed1dcf8231223c93b33598fa2a9c610fc4839c640356507ee9b401450caf766a5b41d0cb838710833b5b730f9c205ae0e078a9b712ee414cee3541ec7b98e1ef36ea5165da183f5fd506177b28bf23d5f6afbde56f7dfd177dc7cb86d8c0514b0861de6becbd9492c142429061f35f7b51c547ece0d18f23d36790555659a01974e056589dc7e394713b9d0a9f1543faa0d738c583f293fb2060c4ef7161be95f732e79900a505a1bd4a2df6a65d04b5a6cba807289915a2cd2565dafff00d14b090538826792a852d890efb79714eca6a2eb27ca37711213dcc78b10840ffd428eb08bc0f9accd331c25a12d99eb7b269eeb7529b01ea18e89ccc3b5191aba78fdaa236fe0fef6ba619e151c38f34f887de3bcfb3cc6241e46b2538516045329c84491523d8e27ff00de8537073cd8045c789d4a05cce20a633a2ad2a8bdee01a05547ed009734bbbb3e588638f7a09cbe9b5d5646951a2f26d3cf7b9da56217de38088ca3ab29f5c298571ba9ee0608233bf872d5ebb021bfdd33594159c2ff00dba1389ff54ff1b1927dd8a32faf0d8ae0402aa9ace2f7fd06f52525c54f76310f8bdc1ccacef3373afa87c94ee7192cf0ee85e89f1a7f7be160e6539ee3371332762111c1e11f3d832c26a1c2e0c6cff5dea6880aea7bad4a0c67821897af1d52bd316f9a187d95866c8025ff002e3b30735b38d890c64a3bbf1486f565538502f0ab2b6e51227cad9eb1d86a6f927c67f8218994f8cff13dc5db4c7ceed9ed3e21f71a4fe89c799def2cd495b0b296e4208bbea758d14c991c42680a1f6369ef44ef3fcb6c0e465b51ab577706fb6575746aa6a4a851aeb5d39dee8a37726a0f7dd468beecf2b3c86dc46f91da83d9fde273ff00e7d77daa3546aa7b8d1389bca9b9c21d5467368e70c8df32af8cc6c4b9b4ed4420cdacee37d37dba28ee5dd0a140e3966e1af4acf821497ae30c9e6a0f6706c33956d9fdb085e72d98b1fe46cc79aaefb5c0ee41e654132a3c7f99d4d525715647cf186de6e511f3e320af4db84ee4f0a5b10fb38bc474cf90dfeea7b944c8eef3beec68da788a6c71467c71cfc8530b5905e8a4a5c7627b0f00f7610c83e250bb34fdecda23aa98c6d5f35555e1b57aa972539afed5eabacb6219e6d18ba31b31a5c539eeb933df4f96eae1f28034479292b22a4ba6054f625c5554b0ff00b54d8807f0e221f18ae97a0dff00fbdcdf19d6684e88ebbaba5d171457f68aaed744480bd5576a17afef84d36183486d97c49901bfe4333e434f9e00495b42455b407479c079a73cfbc67ac2db8530b2b2b2b6bc307c3929a97c296d036d1ff99c1d5f895d07b477a17fe1d1ae365455d037d1ff0099c18d9dddae21c2617b899482f65f66766cb9ba49163da4385c6bdc6e7509de67438ec75d89eb4bae10dbf867a3d76334387dcf9dd6538e5dda1dd3bad5960b21c16fe06a9e67b9769edaf743609cc30bab2b7c0e2e5b6632d1e989d19cf45c3ae0f1f2803e9a7ed1e040846ce7f14270bed0ee2f896f40abc2ca866a6d6fa95572c9fea3b70babababeb96433de77d354ec1d9969f683f8c8d286c10fda09cdc3a219e7ca428029092a9738f45dd64956a8c48ae9347159ecc1468dc2fb93872c25cd59190d596b0f341443cdc74a2c5cb38914c879053750725546430ef3b3bfe46a9c43268b34586e37dca28eb3c655d6bfa6b028bcfbad274073439a0c6dcd026895b09bc803aa2d86f31ddca1d07ea8b5844167265ff005dca73dce63de68d51b9c63f825faedca6a565c1032a43ef21ede33594a0e27d11676283978677ff004a7da23bdfd274dd25b9192cef333bfcd42842ef74cfa6d9c5dec229879ae45d125c5c4dc9dda9af7c72f3aea8d89ebb84e5de4e70f08a37cb6e5be506ad157071e0da6a8c2e9cad2d7890d8659f426779e657de099fd90c29acf8a6cd08bb99c27ab294d0a79aa9ddc6f33e2aea88072a6140aa30b2b6837b337f33bf8553b8669af4dda4a53dea58c954e95d7882cce727c677be75ef815e26ee671faa9ee35520aba739a912bc4a8aeaebc4bc4ae55caffbc2a69c97a6acf624afbb137c78614d6a6c5b0aedd28a449d20afa9250da61343615662f5dde9cb624719e5aab6a51586d570babfd15b68e95757a32a9cc759c2454480fbb1d2ddbcf6e74b2a0d30ab2d39b672d7f3545656d37443ef1c1b187f91bf51b1696e54db96a4f4a6a6a53d8bea755655ba94b44379a6b0580c20fe6fe3024a1a7d70bed7a6d494b4eb7d89cd4e5ac740634d31d3167e6fe31ae8f8b0bd309aa2953629b76fa6950e3456e0bc95bebb5d572dbbd743d30a0d4cdcf167e6c26afa53dc2ca7fc2b695709fa291a29eed5b9c24afa5240623f3614e4afa60f3536b5ca5b43719ec568a9a37d5aeb66d87749634d360147de7c84a4a27b4cb11f13c323329f50eebb43749e915cf56730358346c45f29e349cb4e46cbb80edf9ee33e4bcf57bba99b92aee51bf261d14b529b74b6fefa6a01b9c5fcb8568a9a9757da0ddc2a6aa7318dd78766fb31def6d330034e655d78cea8f3d9cbf31c2bba4b729570a6dd719299f7a238e9803d5546b4f96cb59cb0bfc1386149290d1929a9aecec94886d74bcb0a4b56414b65eeeb875d490df2ba390a0ce6e9296979abcb564a8365cee98fa6a358e6ce657b080d9b6872fa27ce927812f4da1b8cb68f55c1536015d9b94f37e98d37627683799c46a4c14223cb9eefcc8b8f3da1e5b8f51a53071807ae32c29b335d782aea8da0390c4690fcbb555c5530ff00ad69a92a11a15531874530a499f84cf4a4ba6ae676db8e30e5e20e74fe92d1962796d5b0969db19619a655d54edf459861d1676d8acd8d3432ea81b4e76c7a695fe8a7b155452e3ba48df466107c3b7ed865376227870da974c7cd52cada93e5b6d670d81a7d71b6bf776ba2e3454d2ef780dc2e89ff00950f3c2db525d24b86acb6cf4c4a972f81cb565fe487fb27fe89b8d3649e2ba6acf96d9729e3353de2b85b6eca94d69f02a4993dae38482e1abe7b72e7b892a2466bda72d3d7428aa8e8952d70c68992a6f2d6faa0ce414a6a6a8abb0ed503432f2dc62c07b092f14213a18264efd3cd196d71c2aada35dc1d14fba2430a634525427091d69f3d02ee78cfa6e54d9aec795f19296e713f50a9b6623c51a268996a8d038da68335e6a5f5523b530a4a6d52c253553f552dd08e8ad8dc2385d7b161bdf5a6afb61a0db19b913a33870086fccfa05122468e5d1030e50c149cb19cd70c7f5da96135d70a85e2e1852bb9e53c93b0a2e38dca96b4a68ed9384cd9651a1f76df54d899186312733f8a1845847dd71181d1be135553e2b2f2c6c30e1b904e1d76ccb7590e2a4dac94deba29ed5e7e58063479951619f75d8c47584401c3192be12c6dbf029d3da277699a0e0a810f9965c7bac73bc82a4223cd4e34590e4d460c21468135c1084d1373ce51e68c078cae65c2890e7e3663d9fb434787b876ef854db7eaa279ecc94b96ad342989ca733458aee437bbd1668af0c1caea8d1fca92a2928eefc6507186c8439c6329fa26c5fb4c01598a380fd97da60b1c0fbc2f3f2506293e1748aa611a08bb9b4f347e0edf2da9ea5061d70a6c650b2c286e79e814e2c4643e972bbded221ea64a70e0b07a6d6413ad2974c6185f68edcef172843fb4d739a1af7994ddcd582a0ca7984e8715a1aee0f01361c5a3dbdd38c5681dd777dbebf070ee4a9b0470d5014d48a9ec4e5919f3394e2974677d16463434721a27b4bffc60b91ed747457ce73e2a2468ad9969ca01ac942c91a79dc21d0cfa9c1c04b3b2b2430ba6f6968ab0d7cbe094c729e3b04cf5666c30a2aa961918d249b2117b40cd13972d48a262665fba637236597e64f1d83b4086e778a934d87da62b1cf642cd202cb92706c87926c4e5fdababa7c0759edca9d0dde26991f825b0047355c72f2d592ae1654418d1374e8817563713cb62865b74c03858b32fadd648732e95f80f35f6787103fb53fde368439a9e73df02f57177128813a2310b1a5d2baedc1ae25ad76465682dfd287107bcd9e2238b44bf98f828acf60b94f52654e4a8a85554854af6afac5fdb0be8cf0905dc1de9e66cf9afb9a1f741e1d3d1389f9ae6ee2bc53c9c2c9f02b386647327ba1bba347e2e8bb5c171e01ff5aa89038c374c7962f87ef0ab7cf5baeee11c72ea482a2b614595809257b48bde8a7e9875421975d4b4b32aaf6b0c899f134d9c83233bd8bf93e9f5b14eff00e4332933ac508b98f1162ca4443e3e6e59e29e8d68b342f68deed289ae9d0f74a9928ba4e7741529cc309ed1eecc274bc2eef0f829d7adcecfda4f89d6c0abafb4bc548ee7f7a1754c2451532b29af24270834e5a98628e971e8a824aadaf555c2510bc96a3df94d097681947acd78682b3d21c94c4abcb7a96218c1371b053fb3b97de42737cc68cf9633c6106fbb4575752b426f88ae41482a954d99a90c25d54d49594c9a0520268bb1985de691f9577213479d5778cf4f8ef56be3ed3dd855565646705ad3cc514e14597472ab27f96bb3252c2bb05af1dc2a850850fd4f208428761f52b2857529a1b13545652e28b8f9ec52ea67e18d9f3c5f19d78869b5df6029becee78639a5a0d80d6ccbacbd9b2fef1e6aea5c54cd55a72c2eaf84a58dd1f240e35f86b25cf16416d9836ea7c2a880e0a9b16d80f3ee349c2651738af65045e8ab579b95217559297052c6735e6812e552b2cfc94cfc3a1f9e8922e6ca559953264baedf5c1b13858f9298343848597b78a3ef1d6fc381230793c36080a6ae0aa7c1e7a0143f3d1c8df0b10cdeeaa6cdf0ad4a99c7287505954a861de16f7b09224d106b04d4fde75d5d482ca0f99c32b54cfc3af8c3f3c7ef58228e62880cf94f27d0a9fed8922f245c6e8ba7b254a4baed879f09a153cd359a755d17da5e3f2ff6ba22e0f5fcababcbe270fcf1b294a8bee62b874350b2769865bf89b50b3c388d70e8551eb293b555267eba3943a9d554a870dde1254aca855549b6f87cf62e3087e78db09a2382ee3882aa67e6a654b67234d389e7a8d88382f6ac200553557f8349534782aecc3f3d92d08aaacb8d709acacf08faeb775c4792a9f839c2830ebb77aecd0d909df60a729a2fe58d155646d071f8b9d992be90ebb45140635521f19a9d9aec1c2fb0cf3da9ecd0fc62b7c2987782e988faa9cb12a676038dbfeb0b4f43237d7e315dbeb8d76253d874f960760d5060bb8e1d54cfc7a5a8e1d360aa619be41345f105780e6a7fecb643fd55158a3b0f744748959dd41c072f8dcc6e35f97668b92e121cbfd873d0070f69c4a938e145438651ebfec0a530aaa688e8835b7597092396ca8092a4f12772ff0061d7091a85c9da0f8a6cd12d9ee923c957e3f355c2ebbe14e1955d1683e27778e16f864b79a6c77866f35dda1e4703f378b6590f85ca9636ff0062d9515b19154b70465e1351b111e79651f0f3e5bef053695268994d6c69c8052846678291c241060527d94c7c2e983bcb7799d9bab60f7f218514fc279f35400f59accff17ed8de8a87e115b6c3bcb4a5af7da9f3c67b53145dfbfc1b33eb2e08faec1f2dda784f1babababa60e9a7fffc4002a10010002020103030304030100000000000100112131411051613071812091a14050b1c1d1e1f0f1ffda0008010100013f21fd271fa4363e5057eccfcb162b019d6e04b3f35c57393970fee5062183067e0d4ac7544aa8cad33a4ca83ca2e083921700c3cc7b999f1132f69229ef78c7f28b0951762b998c28ee9f93c4338832ff0044bf826040d1b7d844c001a65bff001b41e792a69f5f3fabe3f5df8ec3acaa71ee5c0235f1180d18c52ea5d393293719bba9fc54d2f6b7ba96aadb4f350adcef17140a2a9b83f4bdb3a622518b5cfb076b82e83bb9133b12e9556836b2a0d1b0d76079e664eef7f1393f725ea673ff0081983df6b97c9c9e7ebe7f47c47195a833125a46287304749f5a75788741fcc53681d450cda6393182afde5a03dbf44e48e0f0671f7890e7509a399be8e4a00cdd5603c4e593b91edd0e89805f459367b687333869379366a522af2fc3a22822e72d8efc32ea043baf0ee3f5f3ebdfa33385482a5862ae56a1a8678194d97de51533e608bb7de2abf713204c4f84a1885b5c55f6836c87586a68bf396c8cf3cb394599394c420fd84d197dd82960f68263e48d57dd411309e9bac4139f6aa97cf5d775bd5ff734b1a8bccdaa8f71e2310a88f6238e9317f88658a3ab2a239fbca7719e13ed095e7c517230b2a276cac01f170683c3e61085eeea1221584a327d5d6793fe0c72bf7b3dc6feae7d62f2fbcf365d207aff0038fcc3de0e67949481801fee57a59472b28cdc6adbef340b1dc618c7274db1e1ea1acb8f306cafbc0d07ee80ccf963250be4c098c0187d0dc18da0edf9e081b780e417fcb505f6935f0214347152ccbe499f03bf8ec44b00ee47de1cc7570b917b23b7996ecb8442eadbf13b61db8b2a00d96e7eef1f1057afb47155aa5509e1e478088058c556dcf51a6655178ecae8856b7e4d3f88b812c5c3efa817ded68cbafd08cdd14b0b50e209b6226e5fbc1630e756c1be9e2dcbc6e3519dfa4a732a0959e96ed899a2b022daf7958238dca781f46ccd6371c5338b65f697456c4dfb5da2c93f3911da4dbdddcb6bfd6dff005051e2391d1da24f2cf607d8bf696e94f3c4f7985d2e67d3714f29c61e5855f75713964168bf77897433ceafeeee1abbd7cc368e90025ab7a96130ff009a1bb3b9f5b1a61eb79b2dd78e27c840bfee135ee4ca7c3fe20fbfac560cb6180d4688f538ca5444c70ce34b450ba8be7a4e3d1ea54c331428d9376540a17b5cc092fc4aa19f2a445f11ec59466bf68e4e2574b127063de03b668c5ee623e587830fb5fec876bb5fe3fdc73b8629c3a80e36abaf8fbf996abe3b6c8af420be2ff39851fa332cefcd5adfcff8405727ef7de1a81216b57716e59088ba2e5da5c673860798a05a5b05327a36bdee51a9aa6a4fb4530db4fcdb96c04c5ad7ccf08f9a27dfd3a66750e8a0e92ac84419161279cc1f4db8a0b8d289c7518189588f4fe29cb0949f7d06d97aba3953fa8852b96d95b9cfb45a909a8b2c1f69c945e6a24da029f32efd1be848de3bc74f835553f941b006749fe4971f0a57f12887ecb5fb708955d81bfbde66885a7cc5364b9567572be3f084ba2242bde567ba36f8af548abbc057864dfc53ccddce395ff3c73e99864a957131e82d2679640768e5ca465f4da5e5ea2c4254606212ba3888c5678e0fb4bcb99e394a63cbcc5df65be5f8227626f64b5e7a59873c42ef9a941ba8ea6ff6e9e731fcca573487e04e59642b6bd9f84a502bbb7e81a55a5531d28f2e1eccbe616d8ca8487ddc1809c89ec8cc9329da57e3f4086c6d148ca75700b3f0e7e20a08f7527c4b38ccb3cfd757518da5c0589552fd1c4994f1f495717b448d5103a241025d131cb600ad74d505410b90be9b056a58796703fccf7910d7b7d465bec3a4e67e224555dba7b08fe65329d63365f9185ef2cb38478cccb5f52914b369165701e7e4e60863e5392bb9322e3a7179a2336456dfd098f6ff00135b107a1ef04a6e2ffa20886de0fb3a63bcf67d570822378813413d9d018109599f40ef50eba02741d2231cd3e8d9c4c71033530f8f32d18edf0f78f1a9783a4b7bb8fabf223dbc32c14badcc47cfa5635773da02cd2c7447b895ab33b38ed2aea889ff0038840bc35afefe84ce7e7bc34e4fb4e548d9ef99112aa34b87da50b08f6260bf03fa226fc270439710c0c1346c31b07bb895b70689f49cb638c5c2d2dc7553773c0267d30179e8943129a4b0d744c3aeba35ea49d1944eed5d2babf7e3549e1dcfb4b5e7d0d77ab94b73c351abb86b9eba60d609bef2b1a0abba2c52d789e0c3ff000ec4c28c1bae88016ba081200a7c7de6adc459e4de008a775ed32556fbe66b45cb48c5c20764220e8bf773fe3f4820a16ba5b1b48e5d240b6d40ff0087b9055832b2b2fae78131ea66ea2f426f3b4af200ab1a521946384cd40590a4692dd19eb4a944acc301ac6e7b9cbcb040873e9d7bb19895b1dacde7a284dcb68697f30bd846e9ef05f135acee1fee2b6de94972c8594b952a0da5c10e61276cc2914d4bdd62bfa1956b6ae35fa859cdbe20f41e8bec7682515937d86284df18cbcbdc1e6a59a7528625fb700c767085d3f9d965509c0cc6fb25bc4c1087c3a0cb84c0e25ae3950b95a66e5117eb0c5fd04f3967789852ceb4576a96b95ce30da8d165c29c9701e63b99afd9d750b01b47bb27f7f6986a2b8dc4e585ac456dd4480c3af75151c2b6e6dafe7f52e9b4b8068af961a015648ccc88108e0710e31d4a31630dcd290e507dc1c305722d1364c994ab9436815980e80855010704749ec9f1cc524947239f42deff004131ddf23b4417edaeae85687785b283ef28d659c475c9beef6ff3fb4b6ab3d545d2c3fe3feb80c1c4420d5c5ef0871d2017ac9841f1be0bfecfd551d82104d60ccefb0e9a894a97932e7a16d18330b07c4d2638d46599ed30d33155ca545c746f30e903b80499a51f28a88d5b9a9fe3d02b9fa3f029c0c8e7c405fa1a7b9a849a83aee4cc811b23845bfbafb7f88e812b1cbbfa137249f794781456c2040db7c47096e35ef304c52bfe4bdd7f80fd485b44c5b7de728202dd6a12050c6ee1cca4626222b1041844b96cbc4e3200b85c6599763c1bd00a841bd4799c24e79e48af2fa8d2837b981a9580be8684587bc5578993bf28a5c3f46cb59ff799c743f12700455b96e930899627127c231fd7ea482ebe66a1c311eee8f682389b17536e72e52e50052feee377fccbe090633a5433027052953b487134d4b2648732c73966d027e111dffefab6c25e3bc402eb8e202943677e86cfccc640c9a4fb6711b4b51eeb7d42dae888474d9291d2f973f99aa5cb15bcc171655cc583f028e8b6a2bdfebbfd1e2abc11550a80975b942a54b0a25b611d80968bcd47d8cf922a0e8d332f625066612224cb987de7be3148132417980fb3fc07ab913cc2a7b0e0b896e336353f8baee60652456af1d7ddbfb4c95752cc9d79cd75fcce3138dcdfa08f04b5e0264eff00e87f581143062c4c0258b2a9a14530933c4b480415d43c2d33da50c973b944d53c93cc4f213dd3dd2fde79e67dc1854ef09e3d6ad2e34e178c473be9c4ccbac6365f12864ad0ed88fe3e8c575b8decfdebfb8ec41d0b66944ee305e00a3f9fd605b50a37e701c129a5a89cb44aa5980620dccc0e4f98f55b8347330bacc163ee9dc44d6e7790b99ef2dde5bbc1fb4c472a7be5efcfa9c74b1f06e385db8eb9a21583815ae0ff003114e7e3cf7e86fbc30ca72e2ea2ab96fad1e1c7f3fd4b41e3a0b30cc886e76e8572617f7bfd41bce3e8714543b6cfbccbd8941f99792d7d070a2f8a2065c7144c7348f462e5d15f8955b416fd6717e9f1bebbfc330e660c5b6fc8ea3a3610ddb1f3a98fc4b736deea6dc425ed7c87ce7a3b57a2e9dff21528af99cdd1fbd04d77ff001bf3160b5d17e5fd65f4dc09de82a21689c98431b962aa265a9d68174de3458bd74b34c1480b4f68e1d3fc0c1fc7d7eeab843a3109bdbd086e3cf8985520687da03475a9cd205c7ae55a8e4307f12e699334cd1adc36630cc726f55e268e73d499d35fbcb1f2ea0aa6ce64e4ec7f6fe22b7be0fd6154b50c6b13222e20c84f30de3306a504abea6007e854d4e423a18e7339dfe25a99fad634f6b8e8851edd410295b86fbccdfb44096030fe4b0dd176c75b1e6efbb885699c39964c02cd09b9952f995878b7e615cce2c8dd91b60b078e86e59e7cc567b74d5133060f70dfe6ff005a05df69c4e2b8e32ba0537d218984a54c554065bad1c43084d3a51c37302b5a7f6c7a05e45ce2140066d2a8151af9e96e26a7cd1da4b3263b7cbf3d1b52f15d4cb50831bf2332c907c263c95c54df77df69a197063955947e8f32ff00c0ccab29f24a5c7e10cd44ca14b3cbf57cfa02fedfa0031a8cf663771ba2617534825bd0c7d1188747d24ee9d9027edfefd031315e88f8035e61c0d3b3dd94f21fc4a0de5bbf699ddcd3994769a50abdae1a4eef3f4645c3bc49892bcdcbee5eeea0140c976b719593df1a8f88ac157f465e2cfb477187ce500e26de45fbd7ae7afb2f55996698e2014be82be20d44413140eb58f50b5a89da060315026897f139d8ccde016facafdba2c2eda1b95d9a6ab70466c06389f071732a2b9dc5d0ad6a5d869f13c04ddd7f8199cacc20e00eee53cef2988d9088f2fcbf4fb781f9c50f1f299543fc9cbfd7adf01994bc6bd70abed302f7d14bde78d9774c54ed22b15286a6e4f04c3a9e1835a9ece887689b9d843559ff0090f2c5403e7cfd76d540b688296a56b172aa445deaa588f151ac46f30edbed34f960cb8afee5b141b8825e7aaacf6838766a8eccd052b54fa143ec3fb9ad4b1bdb7ed37bc9eb2c305f1fa03636c8fc40a31887923a4d2786225d511eb507c256d4a0d4eca63e81c7415894850c3122619bbe730539f4374ad730db077e21acca8043737fb47330f74ae8fe392a2db3f3d42b421114dbad69f4769ff544c025b0a3a3d6223bfd02073ce3a537aebc98219841758533c31849ee8e0e80c79e03bca5b20232019b06c7d165530ea1a51d2a7648564d40b0417d42d53f1de358f5f72fc454b0ed0ac7a3c1ff00144c4fb4f3a2d7b1fefd61bd5ca01d01b88c1f3eade0e97c46f8b4864f5ea24aa63a2a8cc0c2c1b8150615ed0c2074dc77d2b5b97453a0d211525c3b9819a2afbfd615a2727e5d5929c456fa1c82ea2b4f5ee4340b6d66fd176fcbfd4a917766bef7fd7a22f1cb5f43ea7dc57fbc29daec07b7765432e08fcca79e6236a283b03c7e7ea3d025cc9bb9588618810a3712739169be9b56e27a34944c4a40830480ae65b8bc67bfd6a8e6a154cdf98de1477449386bbf4d3e83c06ffafa2d8edfc6bd1e77fe54a7d911cff4c7fbf4083df5da68b8380c3b1ec266007429ff001cc026ba380f6804f8106b053922258aed3b01d17c19ff001e9619fa4b54a662d666e84ab03de79203b92903aaf34f24718c12ae67925f4b9d942b37d14d5d63eb3a8d4f257b7d6709a778fa680a59577af46cccee7de1291d54f8c7f5f513883acd4026f2b1a485098a7785c4514f80405b1daf4465bf0e10d543c99e6833bbc9d47407c407e8080efdc5353b88c257e99c7667cfa4eda256a2f688e23896e5dd40a7caba60763f94d4354df99d943ea379c75157adf3f45181c9bebfc0ff001d1b2bf1bf476c8f9a93cd6bf3d06146cbfb7d0b86cecc379c434c5ee5033bdc6053a8e3fdd32c3c0262c80e20a9e697476d4cfcf6fd15e0c2722138bf785507b42e1035d0bd809a96690af129808c8bb42ed3b2837a8aa3fd9d300557b400b22f117c4ede893dc3e8f77ab3077dfa6b4087a833f05c4a576f4b6aafa9b9c4d3bc4231810bc5728eece2f736eb11a9d6d61fcc148f1acf758fb5ceca2b81e768aaaadafe808f9d3da07885ed0f644ed035d16788c7130424a8105e251a82e23e3d681adcb846527414d3e9f65c74f9e9609d557b8a8d71e9667de35b55d3ef87f9fa8cb53b07f2e22c9d397515e3f222262a57be8824e996dfb4330a130e4bff003ccf7f5541ec6a18fd11b962fed39e4ed8e92be89413b68530c3a963504e607783ce0021d741192542d45779fc1ea1350e61cf4be256719f5ce7d8b0681f600c7e57edf5186536e09a0e2b5d09c4e18abe764796b6ab5fd29bf132c63154399ad46e8919c47114a8b31ba8289cdc130b03453d90a862f8484c1f53f23e86b1f3c7af888ad6bc4bcdc7f87d24c2d86ff118233b9bb2b58cf455fa73ec89399b58175243c1006a568138e81a71d2a3b84d3d1a18883931fca2ed7282ec7e25c8d5cb9a7e4f4ff3ba1541935de59dadbd32c2aabdf3ebbf058a9c1cfd68ce5311db22ce6a3be86f30a3931ef1fd215c2da08f94e00d46728e8ee3b4663b2b1297705953a421b638e67913b466f08a35d0a333b9305f2cb4b91940af9dcb3f4dab359df45695f2d133d97c265d07e8deb66ce296376b933d46a5ff00d51f0fe8cccc8201069f12c0e05d5412abc4c84229668c80fb7519bb12882431180a94962ba3d9809d04a5ab5ea1b89579d4165a17bcb64403c4b391e5cfe885b51eb9ad30997b4b965abae57f82517bfb7e92ea25cf770095fcf4146c698e5965503708d91a92a0eccee214dcee6669572469d93b0cc107f841de27d9c1eda8ef1eb2003d5cb7573bdedb97649fa20b42c9f9dd10592fc4b24c3b12e7ab253192ef994fa86676add5934cfb4d28202e0fa37cb0b8db0bf3377bf4a61c7f72844fb32aa8adb49982210023b645ff00acb7ba2d1f7e31f3f785b65305a67ce7aa4021a74f7fa33caebc1128f97a778afae83496ef32f7bddf4d6f999a4f01baea6cbfbf3140bc63314398f317d037d72c720b4be1b61464df4ce55c58155da2a3817e272a5456e1980d2ce4711ef9d47a60dc5459393378863051576afa04a5264cc45f4772bd3454caea660ead65b67a5337be3a6a3bb0081af4c88168b855e7528b828fa79467aa82b75459293e5eb97b04aca7863d305688158b8360b3ef040747d029a6a57637de6030c0ba44ef58bf0823ea238bf388b05e657e2f46eb3c4455e90949a829ccb6abd262b315ff00a82e2a136b19f9ea3fa22af3a8341a1eaade95e23edd4502c0da38125b57d10ba8e586c7702da2e704c95aafad55bb4f1856a32978f796228ecfd0047b4c595454bf3f478ee79400b7e621ca795f6889758f7f4a9f6717b12b2096c0dffc76ae94dd54cbb3e289457f35fe8469b233654d5f54bd45b7a1b9818366e2fb7a422862adca38b8eed7d1a98aad4d7bcd3cfa0ab6fbc5637bef37fa04110ab0a19795349ff5207f1260c6cf3c7a2e66d5444f489a03108d8fa2ede62b73c24c6b8fb7d6441a46e291ac5c31702fe679096f244f8e2fe9afa823673107556f17e902b82195b75e22db6f5c232ed336941fa92d5c173142b12e77f4e07867e37a02fa0d732cc3f98abb6fd2bd719cc72d81a9a7d230ef7ff9050857edfac6a252a3e5d4d7b74768aa5ca66888c5fde01f928961a23064a9d757199bb0d74a733f2ed5d5509732d15e8988e44cc55dabd35a8e1c6b6b810d53b677730168fbbeabbefdba828b478888fd2f255ca2efb1e858ae8e8d1b2fd4b45cbf1d2228ff008e9e8759f1a82601f78ad5efe863788ddf1c622db9e829a596d55b5f4f0c5f8ef8e8a9b8aadbd5507b4468ae0b47df748de8016e222a23594621d930d2d4856a2db71b98a795ebebc838a944099c07cfd2615e865e172435e77ef06468f6af490c6d610d3445d2e6fb5ff9e89f27f944b8d13d2b7bcbf0a340372d08ff00de2051613ea6df7c7414d3ebda29a351516e798177befd32dd287372a52cf8faed78370301bd312d86d75ea237d4e25e2eb8c455dac55dbe9dcb306ba77898b17bbf974255a2e106979cbe999340a63ffa510040e037c789508a8d539fa8212e4fd011ddfc10a372df10f3f151d662f2bb5c4f13d0c15a3c4c4930c00e087538f5059c1e63ea0b6bbcd441369bcd4951ec7ede956ae0b8bc1e9284ae4b959b2048d8395d79239fab837d98fd01ba7795d07644e4e11a839d4c9779f44881d4218ac4ec1e7d40a554216adf478fa069b2269ed14dfe8d8401ac212817dafd2d07b947de6d028e1de21b1b80ad129abeb7a56ee2c012d5fa030dc10270cb7d3de7f59545aabfbf4c2da2515c57698828a2506057a816d1307dba673d2eb27436963fc235e2f4adaabe81f28b15fc3a082862ab6f455696b8e777eb98955bfb2d96b040886715de0c2990e657317c4a8c0bf398e5fa00d47b4a11df79658cd6e253116bda5dc2c5ec67f9f4c2daa8c17de5b6b47c3114d7b59f5459c23f103bc6464065648fb74162978e7f464527f4214d2cd22bc4c85a2a3e4629fa8c4bdd481bae1d2e92582b9e201e0fe47fd7a60b3cc50fb3d0f517071d01e218b152ade2afcf4395aebbf9fd2698bd7e7d7c748606986a68f7cca2dfbe372b1f92a59a5960b8f432070cff00119b39599dc06458fb9cfa595e328e65d55f67ab4d6d947e5e6020218c371fb8e1d1c783d45008819df6820ba79fa9407bfe88534c3e4ef70a768d707a005dfc328f2eafb9987a01547a55c739304a64f696ddf31fa0c7d79a6b7000a20a0fac7780a1d4a1e6bf9f50e0c7feff00cc71ad090ce4912f318dedc9fb430dfd3f8bfa00b68991b577fa4dccd0d87e10602dfccbdd0f983e2fdbaf8a58c2ba57b85a9579e8258b835a7d5547cb14e521a508fcfabfc7469682a8f1ea7ba9740df89dc2b5ea09c839862da5daba26a46cfdfead3daf48fa705d35d3cbfdc4e531da517878e7a08286b7d4bd442957db316a7118131dfa669c8cf9a960e87c04957372d5fc7d352e6c47ca1a727e7d5ca37be8bc412a040ebed6d138a155bf402e1bc442e9ff002a05c4453882d8313c5f9865545a79560940bca5caafdfb74706f5c8f5897ae6420de63dae70b8ae60b4c7c753a9760e260b71d180730b3edc7651af052c5651605232b25c739e61216e750b688111c1151c9a6e2fa8078382604b65b05ea7b9bd052a9d4c4bc37b1b7f6f406a0bb102ae0bb71a7fa8ee78e60aaed401afe7e8c14a814cefa1c61f7e3d205d13cefb443624d6ca6ef987724ac60c6ae6c8bef07e7eb459cf64fe029d1485cdccc2b9cd007932f49e3941602a25c4ce40535d721c11c95750042efd40568dcb2b8226ba461d41b9db618f78b6db0dc78db0d25767a20af6f78d90e26bba02dc8e21a315d40caa0d565e63a57e5d71cf37af449519da71da100532cb4b51d6608330b7c5e310683052bd1fb843bc502df7daecf4073ba2a58b77a8784dee5bd134a52318d769516deaab23cfda6d1f125c7c80f533331fca28e3d38618cb6601dcf516621c3b7a2446aab9942fb186736e3bc1b633e582e446b9f5557c44bd03ccc75df4de26b1d02ddd7656e512af0687408fa2156587c5de616ed699edf767e430631acc62cc3749852ea5f4a9ee8a34552528d86ee2f0f8f4c2a06d945cb6c21c22a19c4859f613ada89da61e257a26e3f53504ade35e8a2d108b157b2e205d636dee1ef2be85fe789629e3997b5b7d4a21ce07cc32726297ce67ce4c68e9c97874e6b6ff0012e0cf4361ab899bae5551b4cc5bf4eced09a4c4a4b2090d4063412e7b9ea2c5128b9fd024d29e82bb09a059e67681ed2a20b69368f00eb84877fa25bbf55dad8299acacb6cba9a8209c0cd865db44152f022f392a9f79da0779cf7c37eae76b329a8aef2dd14c4ca7e6fa85b5126265c7a84c09032e65640ef9ff009702a93301747d04f0247caefbc4542a660d67a5e08c5c7d23b6e85acec8e995d73ebb1375013784e326189e2b8ac2d042e6350363374276b99c305c12c5ceae53444aedea7e7084e808f5bd3989e21d52a1a9653b7ab4c9c334ff00c4e4754bc3d9de57314eec55d5aa26436be2114c39c813163534225381c6a719c743a158c16ce33510acef528b19887e8156abe419de46a10d711a0ba8b98f7082933426fa29f59acc2425fafe585084bc11136edd714cc439f585d5f4b4ab9832b798154cc89b60c11d37cee28d69de60d8fda1ad2276b20b3572d619492012737340e330a2a19e65b0774d5c5bdfe80b9aa6a09ba5dbadf4582ac42a6ae0f0a95f4b0f32d3dad7c768f8f50d27bc056aa0745203a227b217075e4c06e279cdbeb19d4714ef1b5964d25f6af7f1d05dee1e05746eb1b8d7cb72e510ab38ba873987982daab4cc4d6be630c1be2005db9be7f46448ea773af50d4f08429d657088054c174c19477496efeaef758c4b8326f3d0bd4f31109be533efd71979f88cef3e864d55c246ff00fda62f94293896b973e262ba0e419d4705f0947694555626a7fe67a5e3a2a2e56f79f329e6ab947157de62408c852e72167164ce80bde56c593ca6c0be257b7957e8de9ee28ddaea279a95b8dce5a3766e258b0568fb93019d06bcfade11128bc44b81988f799f31304479c5e96a9afe5087e7f4106f437c254e0555ed80ed0b0aebbcb0a6a9c7c4b863f9ce9902e0fc42558df9eb4b5ef0035d3e2242d9e264b68bed2d95afbc1646e6c0661a4d528220d586ae0c20a5b1da32e25f69fcff0044dc7b88b777450d5c2c1dbad4f8cccd7ae55b710c3715f5b65c9a9c62f621767e254e1c259f77d26e3dd5f67de0ec018981fc5f7189e63c6a9254ce884579758a7f23f7e884aea324ff008113794f68220e4eb71ba798a76afd422f3bdcb2b59fa0534fe842da8a25bdd74028a8aae5beb98357afd07f0422d50ee832b945c9226b7abcee148ac96d5bc4a8075fc870ca4a279c253806c3329e222bbb6aff00b8b6c8aeeca6520f938880589d89785cfcc74d953bb42be1c9f9bea85d732e2d6ef3051c3530d5a11cb78941a36be66d0b77af1fac360cfc4a8297078007f1d7c33e23c33028971551e1dbd5775088d3be8aa0711ae982cc5e304b6a0b05d5f329e08aee12c569c8aa00ec932ff100af9c5a98bd972a139e25a697e23edb40fb351bbcc9f3ad96126a1aee3de2b85863a1ef4dcba400f65ddf6dc24744bfe22c543dd83e2327f13070ae2b93f651bf69854cd7bf5d54f0507cd1ec7a82ab21b89ce64ef2b1d42228c48794154b612c2cf76781e374a339d9ff0087e67f1953f13243392efde148ae131638ccaafcc555ab352b1af7b28b4fa29795dbcfbf139a5c82d357cc4b90971b27ecb7efde5a8ecfe7bcfb4c06bdbda14a9982261217c15927dff6405688768aaa31a582a68cc44c38e96a31b959223d5f11730da866e7f5506bbe5697abe8df1280dee1a3e3bca921e1c7dbfee52d5e3a21c8ca6445c4602f789b332b6581e62165d617cf1f98355a433a73ed3c729d19473c5840e47ec55f996453c5f7e226aca96baf1a8962eaebfc4b42bb2670084aaf88efff007fb254566140317e2691862b6227965f897106a1da5b77ea0ffee92b6236631a6b98d81b21b9c7824db0083e47aff79c42ce35185dccf799625952b16e55ee00550e467c5ed141d5193c3b54b8c0640e718ecfc473866ee555f6fc4c018042b9a9be4df3c4c835fe14942ffcd4a91bcd8a1ef4bc5fb1112a88a6287b4050f79ce830886d5da03947c2651d7aab4d37082450ac863425e7489190503729498c9af023eedca5dd446a58b2261b63d0808845d63ccf38c44c916b991ee143fb88248ff39c3e21f475a029e41f8399b5195de0a77b63520fb6bdf863f2920f3da31dec324dabe544d20d570caca67dc9c3beb1d7faabf3fb15bb8ab6ac1a8c991630f13f9fa08d2c3b2d1888cf6b7ea1e4afb44d05ca8695cc7aec250d200329a0945ee31351a733bb0ab0e21f32c7425ee109edd0b4c6652d909be6302d6582281668069fb595e60869f3972fcc186dec794be2f5e0ed1777021a6af8ed0e2358aac4b1f9bfc4a10e6db67fa37da650357be3fc9f88ca380797fb97351bca80fe05fea2228ecf54cd6bdb38fd199840154f13275bfa292cfcfa943bde36268c4ada4402b4c0c208ebb043358b071ed88cd4ce8a9e3c4371d90d4606f33863d198eef4ca71a84708b5412edf69fd84c7a55d81e4d21a6020d2697f1f912c03d9e0e83bc588ab154e3c1e094ab41f60012bf80407362527f3318df6d669c7704541a180c865d91319135f338f784af73efe9585175fa8be98af698434915156df4b34d7a82ef911f963cfc4cb50cc46461a0bc4bb46e16620cb55c2ad4c21e3fca76c1c09c08c0bb8d5aa80106dd46e0925b39a88c7b4a2edf88afdc8526d56591c46b4e82a1470e5de60293e2dff0032b0b3cffd476ddbd2830510764014d0e9ba97451c0b83ed2b129bcf4b305cb24001ab9d7087ea1532ed6ebc43dcff00c82652c652da0e5978a1e261e9f953d1cef51686c8f9fbba1b9868aa8f39858e05308c9de5f8bb7cce14651e09f96739c0f986af28d1856651752d2a5b92030e8737353f30136b708a69b618c59620134382e64324e588e6dea0f67f32a81b9100ce48778216be99b1b526bb57ea49437b1cb0c5f114b4254f09ba73c401dded1e629daa05f229fc4baf662ff31bca7bc115488fd0c036c221c6e54b0e80b43a93bafc2c0cd7c5ccd58e5b5de617273359ef3e7a41c48186f3dcc89e225911f04b4a165f62d12cc8c13dd2536d6a50603197188cccc0c0441e425e8cfecc63a1d37d2c1577486cee05ea11789f0214b419db1ee624983213e62e998b568b90d902a929e9914bc63a18c4c2e222f1d42b44ba5daf17dfb4275d01e57103102ab4bdf89981f7418283b252bf13d84aa66032099c612e4310a6c658e72bb940ab1976e9332c374778ac1f12e0bf6c23a218903a710d8c2c9c2b9ffae6912d996c22dc0b9bbe214a18b56b3b4acb0a60c4d44518e924bd9ed11505cf15cb9743ef0f80a9e75fdc14be605e66594483134a81cc76d3877e220111e6798762152542d07056650a8c7597a7c25a02b16c252a986102aa02ec7b25c0fdacd751b79acc312e53a3fa40b80852078d07bcbb94997bb0a06b71323bb98045bd4c54708c40ee5b001159dac12e27fb8a950042efde5ca75328b3af783a818767f965ebc54ef0208b72cd2f44a9a4d431897afe2715f8962ded3146f9e221553ccbfd9a9aecf439dde5337b6363e224c3afb4350fb9e999672118a2e2c778074565f32eedc7477ac743cac047819ce3b04fb043b750ebee3353584db8b11ed9fe6a67d3f796ba5ac2e18ef322d2d077b9603616a14cf0ed291cd051378212b5de56302f6fdbd023b1e842b8378c50f8968c200f62cb7fb9ee05dc7f105a29ece4457c66793988e1a03cc4f2cfe5801b5ca45b11bacb0999a8958710e7610ebbcb5155b5b7ea012a0d7d9957584b2a305a1816ad7bb0dc9eaf117b6dd61d18e09c577b8ac00dee2dc51da2ab6b7fb86dda6f664ef5539bff00711afc4b98a1041568c4f7dffe259d9ffb86e63b9ee1dc4eccacf319ef8e6178890ef32837145e9de3b40fcbd1cdb7132a84609bed0a7b17fd4af34283c4592f77c1da32df516b97f6d0f044ac31f402da8a82aad9da59ae6f5d0971c0c3b0fbc066b7f32d6786b52f6eff007da723a311b9f3d014304b5044db895f49ea051d77d77225b285f8f092d8ee31a9a45d7b7eca6fbcf183cd73282f5e7a19c2b1f9fa916398b830a88a8575c574de75068d3037883a78fc832bacbab8d7725377cce9702a9552a4b895623201e61ada60bf59f94354db0fbbfb295cc3da69007b3cc3da19ccb7bc4ba68baef03e814c910e3cea1e72e55f4b1641c8f79405d8c5e4c150c0ccb3518cca57ce5b5a347cc0c84c84c4198997cc23d2bb6e5fddadd4bf755711ae4257782c0ed111a664bbcf7832a366229681afa1d29e350096614ed19e6b3f42a31ec1255a55b0cd1ab9721de1e0f331a076c5edd0652825c74f2feea4223637da57b2cf9815cded04748c029e828e68c42c80230c2dff0031154efa2ac1c45a33a20766478eb78a97ec32a8efa64df7e202aa52c7888817282f7c46a95b72c32d43a6cadf6fddc2da9572425ddfde599a8f683ca19447c913233c1e8a9d5cc96e2fdd337be37026ca8a8489955660b8ddc1c7d00f30bfe5d2d9afb12f18770eeb3332b89a0c5339b8ef05258bb3cbf7842f6ebf68393be2354c42eb99951c3897069b207ba2bb7a60cee97663e8029a0fa862bbcc543d3e3175382c134c710375e1da236c7f787755d0dcadba0d8a8b7212dce7eaca556be8a8a36e28d8916f5058b819a264aa1b66d828e92df8870af8e65199f2fef274e73821731a30879a8aa9fac8470f4f36dbec9822388a8c1e263947bc5787e227822dcc574ac9594e023c4fde843312dbd5f107998e2172d52d01fbf11154fd6cac963e7308489e610cd7ea3a42a2e5d8a3b1600b562c0d02834fdc4f4bf81841f69a57da5a15300ef05ea51276a27c37e867e73a5e9b2455065eae1554199d42ccf9d67e6a77fdf15372a37be9b2a913b8229bbf0c0ef99c372f00dc7a78fad6c41e569c54b4f44a9dd4ced711cb02055e0404a172e7c3f7e14d3102aafe60f2a41eac637661e9614d350420600d1a18154efeba41a0f765e6263630658cf0276885abe55115a57cfefdb973da000bba8861d3bd0f30e2109ccbe222a14c23b0dfd7fe470b0840bd42101fdadaa7e98f9589db9846caf9959777a8a36352a5118773ff0046188a92a6858e38fc4ac7f1f47941f0199c295da1925f01bfed45a882a99c65fd35f42da8d722983e2013cc73394ef3e1896d7dbad81afbf2421630cbc4ff00598f6fa1526344fccb8b84627ed2ee8843189f92fd336c1a992b45f0f5c0be219c6658984973d9c4bb3b5b20d62704cb8d0c7798e31cdfe222052742416ba20e7c657bb12aafba057bb8fed28bb1308310379952797a5ac224a564f4d537da27cecf4571cc7beec9bd6d4d12df6806adf1113223174980f989a86c118fb1108b591d0542d89d04f8b30da6f0f1965cddbf68dcff00643128b98fe62c37cbd208a3896efd32af3a8251ca799b46a3a8652d5b460b036c4d38826818732bf32efed898724a0adc27dd3c234cd46e599f9416622b4b04d747ecd4b28f6d4f7191faa35b92261d810d5770743065c40b02811579cb368904138316d2d9741510c23a53bb86da033168a27fffda000c03010002000300000010045845f7df7584104114dc41041041a6423f618c082009c1041d6dd79065041024f00d7f9ed881fde34d04905d37dd9bfbe916746bdd3bc2c1075f7db41841656ce5b598e882d377f5ddb3f79f4bcfa7ec753c45fc2fbc308349d7df6d049a576f3253fcb05547174b361f43598cacee6d2bef5cdcf3cf3c5a2d3566d2512a90614f60bd50406dac91586fa03639d73cfadacd8bcf3cf3cf3cb0f6dbe4105fbdd359d44f24b656634fcf2e9b6562e3c65c44b07cf3cf3cf3cf0ed2766d73dada471568f686639dcf3af3a67ef5151585995df3cf3cf3cf3cf3cf3c907f3338874502e06aff00273cf3ef07eac8ce700163dccf3cf3cf3cf3cf3cf3c3b5ef3753dd8b054d446bf3cf08f13efe2f1f3a5cdb5af3cf14f3cf3cf3cf3c33ec9699c75fda699039cf3cf28f1d8e81f62beae5a7af3cf3cf3cf3cf3cf3cf1e5622cc69316124bf3cfb8c84549cea38987ed391f6f3cf3cf3cf1cf3cf2ef3897cda6469e4299127cf137a7d3e0681fbc76eb97def3cf3cf3cfbcf3cf3cf2edbef84d7771d3eef3cf078de996d3aabe3fc0454fcf3ef3cf155bcf3cf3cf2a91c9e966f12b2e1f3cf0b293c1c5baf7bfbc46adbcf3cf3cf27f3cf3cf3cf085d17a30e14c2e62f3cf0178dd3c5ae53cf3c062f3cf3cc3c158d3cf3cf3cf3dfbf4ceed617b690f3cf0cf25f2c7bad3cf3c057ebcf04b453c107cf1cf3cf2c6a2c65147f7d6cc356f0e06a728f3cf3cf3cf2c078f45debfd973cf3cf3cf2634f22871bc6f64197cf3c438e2ef32f3cf3ca21fbc9c950325cf3cf3cf3cf3be63f4d118cef99cf3cf3cf18723f0ef3cf3c161f382c9f36f3cf3cf3cf3cf2ea696f82a670cff7f3cf3cf00693f3cf3cf3cb3cfbc3fe0d6f3cf3cf3cf3cf14378f967c9bac40ef3cf3cf2c0c1f3cf3cf3cf3c2aecfe67cf3cf3ce22bb4e8304a4c4b5c382abefbcf3c8ae0e3f3cf3cf38f3cfa8ad5676f3cf284dc708c16f2a33cf3cf2ad3ef3cf3ceb19ebf3cf3cf3cf3ccaef8fa7cf3cf39bbcf3cb9ef2c93cf3cb2e8def3cf38248c5cf3cfb70fb338e2ef3707cf3cebeb47cbcfb5dbcbbcf3cfbc63cf3cf03961ebcf3cf08e24fbcb9ef1c73cf3c539ba7f3cfa45bcf3cf3cabc73cf3ce2c0e3bfcf3cf3e696f3c0fcf3cf3cf3c9fd87cf3cbb4f3cf3cf2a4bcf3cf3cf2392af7cf3cf0e1615385bcf3cf3cf38936abcf3e0a2f3cf3cf210bcf3cf3cabed292bcf3cf3cf3cf2cf3cf3cf3cf2ec99f3cf0e3ddf3cf3ca257bcd3cf38c1afdc7bcf3cf3c77ef3c23cf3ef3cf3cea2cd5f3a1e7b9af3ce16f3cf3cf3fcc81fe5bcf3c15f4c9db683cf3cf1cf2ea3e32e26c72921987cd2ef3cf3cf2858a32a7bcf3c8c01bc89e23cf3ce38f4a8fef3cf001dc1a6fbcdfcf3cf3ca8072d5bef3cf3cab8c7403c7bcea4a8c9286d4f3cf26c2d2461202fcf3cf3c83a097f3a73cf3cf38dbcf38fa669c11ed47f3cf3cf3c7b2502f3ff003cf3cf3a5d0f1cf3af3cf3cf3c922fbc1fe69462187cf3cf3cf3cfbf002ca9f3cf3cf3a23e53fb9cf3cf36c8fef63dad3169abfcf3cf3cf3cf2c2dcfac07cf3cf3cf3a9dccd2ebcf3cbab08e638fb1c6e2644c3f3cf3cf3cf3e23c7e8fbcf3cf3cf3ce70cf2dcbc910cb1f679eb9ff003cf3cf3cf3cf3cf3cf3e03cbabf3cf3cf2ee47d02e63dccba7c55157f3cf3cf3cf3cf3cf3cf3cf3cf2d1d18bef3cf3cf08cc653db8b8372580ae96df3cf3cf3cf3cf3cf3cf3cf3cab973d87cf3cf3c0e1385866102737e95424defdbcf3cf3cd3cf3cf3cf3cf3cf973d63b2f3cf3812bb4dad5dac3bd49a30c5e65bcf3cf1cf3cf3cf3cf3cf3cf1ca632ef845f051ab1ba24decdbc9d2a83cf3cf3cf3cf3cf3cf3cf3cf3cf3ce338d317dd79b19a92164c3b95e234b89f3cf3cf3cf3cf3cf3cf3cf34f3c3bcea18d7543e5b0ad484f0919bc3f14ff003cf3cf3cf3cf3cf3cf3cf3cf3cf3cf34f2015de6ec6a6872dcf3cf21136c7cf3cf3cf3cf3cf3cf3cf3cf3c06653cf25d093df708d8a84bf3cf3cf3cb3cf3cf3cf3cf3cf3cf3cf3cf3cf3ed4a713d3e22a87baf57473bcf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf0344aca8740a7e43a94aee7f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf38b24fb6b9c476fb2a6441cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf03f09b14f3cb8880c20c73cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf9efbcf3ae02b96dbcf3ac55594f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c765c7a53cf3cd05f3cf3cf3cf3cf3cf3cf3cf3cf3cf3ce36f3cf3cf3cf3cf3c7eecb64aef34445f3cf3cf3cf3cf3cf3cf3cf3cf3cf38b96f3cf3cd3cf3cf3ca6e3a78b85aaff009f3cf3cf3cf3cf3cf3cf3cf3cf3cf8b97ff3cf3c3bef3cf3c5fed1deb410e4c9ffc4001f1101000201050101010000000000000000010011102021303140415051ffda0008010301013f10f22e928d44a7d66ee2a82aa822a2defcb72e5cb8379ba8b2e5cb97c142c1800de1b72518058f4115db2792a56facd372f45e1ff82237116d1510d04eb43c2479c865975176b08da5bd4b5ef8dd5516e19b8ba0d01702562f0e94f095602c59de873f70c0b8406a6195a97938eb341dcb2f682c869cd4acdcee06a34be26bb8f16dc19b6841705602e24ee006a741e63186197582c1a8de1a9d079416c4c0902e573070687c83d417094c78ce0c1830f89422c8a9b6dc0853d41fd8cdfe45ed0cb8726a7c42a8c146be46d81212a6da6878de13c232978ab00c045b841e50acb0f0d6f7c4efda6cee6deb0b962cdaa1ddcef01e542d2f172f15c4279d9db55c5c1c151db86fca7017c3dc6042d1a44ac308794ca95c2b508e830b4a60709adea1a0e725d421815ea3fdc01c27a19572a5cbbc15f622b68ee9df09c758be321b11ee538057230e3315c7584fb1597c961625358386b41cdb75aab5c372e1cb7c67333ef22a8f6b1c1f21a5e77d67f24f33cd55c55a1f01aae5cbf20c1c2f0be5628d078abc9b32ddcc5c35393c772b82ae09d4fa22def2b59e5543819516f26a7158382f84789502b8c95aee5414d434dcad152b8c792b0af51ddca8b6506a50dc56deaae7326b72aea7f5c00108e930ddc3060f8c840d6c186f2c86cc18b8c4fc973bc76f1dc4d663d40bda7d62cd91953ac300d4467702a3e4fbc2466e8d0b65cb8b5b4b8ef14750bed8b2af710612e579070c1978ab8c25bb8db6146a0e9951823427695f98d0e09528a8d8da547719492bea30496e345cd846b9475157cee1b216c1768ceb23516cb835b41508b508914fae8c14a830750d36c1b46616fd4e141707445dd0d94c5bfc4a8ceb06a6e3f1d45b4a815f8f4476ca15f6b861aeff000876fca150355c3f0487b5e55a837a58f95210e54952b4a4ffc4002211000300020301000301010100000000000001111021203141304051617150a1ffda0008010201013f10fbce296ca8ec0aa16824f44a39b09de912fbbfd1fc1457a46244c44f06c8dfb9a5ce9c48e0c5d0f698d1a20e215ab225d1d8b83fad9913e5617093843c3fb0234e33b89c133488e11724cb7e3459b9597c50e15170b623b824c5a22f0586e2e49b13e6d120b32920b2c84c5840d885a567983e875e13c170ff001587c35e9af3e4d1763485cadb3a637506e6878416133b7c5398279ec82d16fc2e87de52b8435d6128ae0cefc1f5f553d293884a14d31348a2138511609f11635d8d6d2c25541eb0a55314487d7d52121044271b0ec9117827309e96ba23c3b63a62fc032f479432c2d294a51a4e296b091423a731a41bac4e2c74cb7dbfd3c2947b610b306c798825509e893638ec35e8957b1f43475c3ebea9cc27ac562057855097d2308123437954c70874bb12d31d6c6b85b1e9c3b0f143e8bcfce6f581283084e0c274651ef07496080f483d8d08b3a1b3ec4dfb8ef8ef95c16b1ef9be84e524266372cb64083a679ae0cbc1e909d1b2ec7d9d84a328f4378ee31f3ffd3e1742fd4420862ac1086da744df7c2121bac486763b1d87c3b8fde296ae10dbdfc1ed11093352251291227212a3ac25676c5c5dcea25728f0542531db9aad4585826529de1e1b33a1e613684dc62c2cb7c3be3d1e1176ca92d176745a38f826ce90b1708b0b70d6cd14e17595fbc4d71ef8a731d05533fa5f911d1689e1629707f0ea317538f61abcc8953c213d7cd3876486c265b94da36c6f51f3910da9f198ed6c6c4f43f99464930b0ba139b1baf9227d97711e57c612825636dc11aaaf9c1eb584257a1ab2fa441e451323642f04ae1fa9e7e2d69b16b3be0dd7436df04cd69118a145c135d1e131a8f8a46d8efbc69696123ac7aefc5f58b744a88f38aeb8a62ef103fbc6d23f43778a1d8e91db2a7a49d0db3fd1afd62544c27a4364f8254b4d9b211e1220d4c29ef042dbd10e2b0ec74bc5a1b7bc5c35a45d4cb778251cf04d90b2953b2f2df34a0b047df18270b97525f04123ed952e0abd216df244e68585d714e150a7a39e7c93f46d8953ac350576a128f925a27eb9211d8ec6be4f3cd4c12dc1a69e689fa84ebbc925db1ce4b5f24b44cf9f821382fd9091ef08c58234f2956351ce4b087d08ff0038276392583441bbce6b0f1d1d04c6bd3a63bd8ff79375f248874317176d41ef4367851b6363e3121685aeb1b2d8dde1d9fe9d04d3241cf306ff005c923cc2eb0b8a29fd0d896ae1bc25493b1e9e876d6311885d71e87fbc37d061354f41abd3e4b1d170bae70eb30484df47652884c3fd39aafa13847e8d7d28e21b2d7258bc17539d126c874342be0b0249bc1937af82360d246bd27a873e65c52a42e56686cb06216f16077cbf829e1662c7cd10de26c7ae0dc341448e0652328bab29d144d7a59d3179647c10a289c64a251efe4c62637fb1aa298951ba824531d145139f721f59d392547a3dc43b1fe83765b82d83a8168ec8353f096961aa49f031d2a6e36c3c316863544a61357f054f4b5a43d60df2489e8d054889762e0951db5141521d31aa1f506a7e0282a511fe8897424353937b18b43c093048821220b14ee1398377f093a16771b2e26aa835d849b56688a253105f8c192ec817496561f07b1a2b50a087a08f4d58b78114dfe0257874a616c6a97f30625f42c30985d0de5ad1b06d25126f45cd8a2fc86ef42730cd0d194f62770df18589afc34cd1d7435ab9d4986fd8c3c7a0dd744eb3b17f2909f43a7189f8c479844d0b4424c583b109b1bfcbab095a1cc269f6412dc669d886ec95eb0ee2db1bfccec219fe14b84eac21210d111dbf9ad193087fbca3c3b8d56587f929c1b47f99ec76c506a383435328d0542d7fc069af4b3b2a7d91ae2bafc7353e6d74c64a34d0b635f925c93726b462149b3491d9e0d4120bf112a38651f14df5c58de86a248d4134542621e1899fffc4002a10010002020103030402030101000000000100112131415161711081912030a1b1c1f04050d1e1f1ffda0008010100013f10867d6a1ea69fa0fa0e7e83ec2a57dbb30bb2af6615a53a38036a808280bd531264f65514736f48c76ef90ba07f2f6995305527a2ac07752ba4be3c2a6fd5be9e2331e633da3b7acc89e9965c6f7a88ca8507115836af798377b171727663af10c5b23d992dd7559e61e82accf423f6ece14c2eb9bde5b3a46a5a688828f06d9b13713227e419ec5c57c21e2b9fc1f99c13f52b417cdfbb081bdf429bf001f231cc404f7de3549d9c57bfa57d2fd075f457af3ebcfa99f535f41a7d48af5a95eb5f5d4a95f72a1a6fed50874502bbac2998215b9495614d372f012d54768710b6e3651e4d5ea22542ab42068df7eec10848c31635ef07bd5b114187f2671cc1964852dd3c5cae0425be9ffb2c08c3b3b1dff88c1dc19c8ffc877a9b00d0f64e47a43ea35252cc815c145d7172e9b70bbd51d6594ca23c2c566101082991ec1405532cec57cd17f6456c86c41d34972832431dada1d0c150a356051d3721c0cf5fadfa0ebd5d7d1cfaf3f41a842052072b289f1082d21d99b007920191e195f41a8a02ba25e83271e210d03d506b028b8ea0b6564d423a2c6ee5c0ee1b8c0bed8128bd4e6192ca7c7dca952bd45825d9553a13c25a09eec8c676d95e258b07296626f1490d45b8de2d4e4992411e01ec8bd8666db20454226c611236289a4f44ad2ede825ee566bf31bd6f172f5ec5c08cb0e47877ef2d46d17433095154b796973203ac7f304cd5d98eb996aac18c3a1c8f23861c35d9067855bd0acd5f3620dc516895f43f43dbebe7d7da21a5d31410b0b5d6c8682e98b6a2e1affb2a4d44b009534833d514c95669c061b17b6607a05aa530b5575cdce1064daf301befdfe611690bcb509350eeb8850316b8d55e538b6628c067044a70c1191c31d655a2cb22a807bcb42a19dc04a44a6d8c25ec985a6373a9cacd112a0f2a6204937660250709e952a54a95f4dad4b6b046d2306305b5d601a1eeada2759adfb269abc852cb53d102b899a62a82ca314f4aa7cd7780804ace231dc2608e0dc45896ded3509b519cbd19889ac5c65b6732a7b845741d58d4d079b27fe235a3a4cabfbe25610a42eb785e7bc72690133ff878962da96e3c2f381e6e14e100ea7e8824435dd6bdae10a0ad7ebd2d9aa3a7b8f51d26930c64ec41e543495b4b1e086780683a30d83d30f9f4afa1fa1edf53f453f1437ecf79406deee534a37f7064c64a359944bf923593657596e14f7808171886b7e64a408f662a42f985c3c1033507bc62d2bccc8abc9b8f2c4ded94385f989b389cfc32eb52a92b90907c5d774afa35b14c5fb7b4a97c80aaa01ea443a11546ae0852bc0dfd84a5379c61a96450a254d0b4df4cd47b36a31cadcd95b3c4c569dd6cbf110e5a82ab8a8f90b5d0694d42a5f559ba82ee251a2fa7939805ab66556e9d200b59599604592ccc42df0331c0b9df1fa96944bcdde4f1d215bea0234f2cbb8cdbbd0632bfa947802990ec63c6cf88048140017ba9b638b79ed083e4857d4a99e900396c4f00f16fe259015a11aa88115d57d5813e3acbe2b047630b9d18e62de02e5b59576eada5bcca341b333eff0075b2c5e4e29eb7cfb46870f98239cb8fa5fa2bea053503ac088269944bb8bb2b121e811d2e5515f3155cadef0236ce7571de2995979ed32eea3f27cc528b73de0636ce25045b5d614de602e94c4e78979ed107423483733a662aee5b68746022b35965058446d899ed34527965db9e4e3e868e256c2acef9c7ce267ee4ad5381cf380e437517a56405ab3aedaccbf6edea179ce3de31a14e9c85fbaf105f64e5eb482d18ea22ce8a2f6cb7c6779c73044416e55b494315a009c6c015a3ac15754de6ef150aa4afbc2dca5e8e48751ab896039be636ae0cb3ab265f27b169f88c584b11be17eea742351f3773aabe5ecd4585dc3421511ae8c3e094cbcc6b8c0add4cdf0f36dab5f38f680eb6d6a8bc99fe3eb4db01d8297f12f648afb2e4271d99cee094c747bb1c7e3de1e37aa13929411aff00d404d2feefecd7af3e876cd418d4ace773215360f129d12abcf12b80b77011cc4b4d431a826b1323ac572cb29a987bc765a4c4aad40d1d22f563bde656d8dadb16a054abdc3a882dcad4e8b11ed13c42b9ec72e2182dd6ccc6830156bf707dba6b7f8e6508a10a11e3925e001b1777fd2315aca29d8383b4629957a3a2744098bb6737370b4b4eee599cc3178a557da16f018bbce07f06005013a330347701172a2e9b06fa847502eb515ecbea9b8ab175f4bd5ea7880a28e95950a96a828c742ae95edc1ddc12ab74bd01e8597c17c9280013e5ad2fdf1121875c4c03ecbd6590a774b33d94eac10857e63fcf12f3ab59d81a25868dfaab6c42c44e47ece52a514de6e0c72261e73797e63178a4aae9956fa454450ed4e31452faa2133a1941d4265baf6f106cd0c727abaf575eacd4c15b963a25d6aae1a615b1d05a22a378e6bd32b6e2a39e225845b0c6a11bb269ed30710ce8625e5623c133771768c7106b11af12fcc97512e2a5bc32dea599db9daff89d06642bd00c8f983a3545afd2ea110336df0e5c7b470b60b650ae9503d36f2e894990db7d65e20d3b69956a483c95165ba2e025336c9c6638ff00cf468e8765cf4a332c1ed0a159c0f8658d550280f446b36ed7c46b312d113c5ad4bbd628ec1423cb880e3d9362cb58b9ec62e3e05ca058ac2f15e3312649c9fa859803b10b765703303057c622a835e235b837cf255f32d588a99c300f7bfc7dd48d8a26922ac2070835642998a898e32297971e4832a6570a5b670f192f34230966badfabafad9395ef2fe3f08b5755313115122dda839220a8bc04682aa08e65e9ce788b47a4c528b648fa4766616cd410f3a2586b5096bcf48e561963514c90581424ea718660ba161d25e8d5a96ae81b81162f45cad8648c83d6c34e94e0e751b8a9bddbbf4ab7b6b4e629b5a5976819a95a6582779ebff23a5b4a593663d14f2eafe12f91495bd789551a9695ae26aeaa9ad732d2e8069eadd44b5054d76ffdfa1d015a80f649502217d3f2e0d0d930ac68ebce2e93b97ed1c25ae73b8b683ac3166cdb70b32af1168b9448e52b25b37c1fcdff00801fbab426c4786f364a66030adb41807de8f994c6c507c95920e22d388602addc8ebd6bd5f800dd4521f89a9d398b4d0c2ab0c4aba4f494e04354a4dad44581a95f68934ca9c31888ea006bb4ec4ed4ae66dbc40c462966d8c5966646b7006983480ac34aabae630495840ef9861837b55fbfd23126e0b56f817552fd2fd0051603c768e45284174f7ed330b3b17cf53c456544b0edbe9e84c69b7e10f4b016b2820768570fb46d70d43f6f5bd6553f0308ea98e6eefe3538fa475115461c25660e480089f19ee48b33894bedff00dccbf3475331ca30d11d9ab95d2ee76e3059f3a8972b23caff008353b246baaedd267ee28d4d61cfb7925612c1833ae47e4a80f1ef91e32be0c5ca01b584f0bd5b1f6cc75e9c4d7a5809aeb1e950cad0bed1aee094c31d7a7514e38e91ab71e95c7e62bfe1384865eaa09cc4e8c622d6350764edca2d50522b89a7b450772cc3980e043bf6467365d626f558daa8bf3c434106ea3cdaf88eadbad078e598c3b0cebea55ab5dc6bd9bb2ef3a621a882d337d61e037cbe4f4a89972a02c3be9895101bdd852098b0fc40ef3382676cab6c01735bf716424c1397566bf287a32aefd9dbd8c7d0d49c27bec20077976ed125855ccaea0c85fb1fccb3a94fca4efde0a146b0894c78d0a1a668aebb90cdfe63fe0a46c513490aa13a445403346d7d4eac014372a839eab0f5f44d42f699a1c387a45ae0d803ce1cbb74f1a9dce7d1e7d10aa9de1c17299763bcbb3c6650749d209b8234b060ce10a6d81976dd9118698b50ba73162d0e677e1a20e487136800cf5f42cb9538c45691ab1772f37ace65a6a25b2b8e91a679acb6b9a704c505023867394488a45bdf3d60a6a5fd4c15500acaec55ee1ac4044b4d19d150eb1576cb75e803225b46053a66953294a11cedcf58d557005711ae8aeab44a88bbfde142329c0517d7d1a038016abc10bb9096cbeaff00c6bcea61478a93f12a114052b8f1297c8ae3fba2bb21f81393f03d6265598d27977288b70d4bc89fcc626da0680b09d421ff000d204b60e1f4d19705425585b55de2bbc4afd32d4c140c0d7ec653a18818007178ae687d4288d8975322837da2b475ce239696431bbb4d7ba2aea58531d225b255ee3262f506ee1c41512cc41123bad0c4d258b64545731ee2102941c452cb32ad89e27c4bda80655a62f571d1cadd1f93c47c6b0bf68aae5bfb240a99ed09aeb0eadb775d3b456a85ab378b84b871a63860a6b20be83887392c1f1305828d2ea155082a13f0e11d157bdc398d6c4cfa0c347be0ef9e25a08b9baef2a112a281d1213a4768729c62086806ceb29a035bfc4b3932d30169ea18771cab474ecaf6ff00211201536dc24a16956e2dd5a3a38b8474c3caad1d15cddf688dceea453588c8414651ec43ba8a541dab51da1551c505563118e0d0caf896f100e5498ab6bac4e46e223308caf6f684362569221c44ad3e260c91af4cdaa1f69660e9e20f5a8350cdc61016fe0b961f9cb856febb1d8c215cbf4115b6b1d258002a51b1e5afd4b2a435dd7f8f4206dc7b884b0042474bb7f93e6363d006b0b1ff00d4e352d53db882643baa68178000f9f43985f48f2350bf904e474f107665c237485ef10c00b0e852ed0c850e5d4c43336ef1134ae8cb15fcbdbfc90a40a70c02828563fe91ee0f091e134c570d7219bcca0df73bc75afba583815d6566b5cb0ea1677635827bc2639f6673011e65c8bb99b1925259707587332f3100aa5b83211a8043170cbd4f6e74ac4d6a2709bc47165d910376c67cc0d261cd6bbcb596c93653e75ef1555555dafd7dc60a3c7a9cc2b80b5c46468a85390f3faf98c9b4e5955bd3bfa10bf214b85d18985820174c7fc23d342ad2acdff10e6bced352c1378cf9709c953a5e3fb83e3d3897618075c979bc5f77584d1879a62a9ab5ed0101733a85c6224708f47119ff004c31e92cddbcc4028743190bf25edfe48a646a6f0aed9689b455865152c6055ac5bdddfbc06e6e2ecc79836d2f98896f89b3fda3d6fe650a4e3bc1e035cccbeb28a8906ea16a444ac1e750e966710e4b98a12f7af4b2e23dea37c9e2e17b02fb46a5a078ba9556fe437f0351464392e703a07d83959318a2f3ea6e1334b503d1eb2c1c4c5db45fcc63a0a5535ac9e8e06cbe51905d275d08bc866445eb17e0ac6946bd8b6bf947ab714ad96f2defd6e5d4620dd85822b5a4762e717642a956175132ae4ef5399a8c318dcab980901eb5177745d1eff473fe4a005ab44a2c439bac10e7119cf455d6084086a18fef423765263113cd52bac5738307cb2b9571826f86582965929d44d7388eab98988c7b2c0699b698108d4a11130e62f133edc7e062cd92a451494b46fb224a8aab4efecc7b44558d145f4faf8671ea43eb014ab4400450950e5bdc32e85d8384efe96b743f19834dc5d4cac0e144343850142047ad087b44aaadaf3f41ea803aaaefb4711b56f3c897f9b95d2730a8b5b4b0e02631148aabc6655b931ba60f2b3955f93187dbeb02b40bfe100200dd72ed28570721807c471bd5ba3a85ed6eda332884b2aa893839e8c2b85f13158f1028a23217700b0145e102b89d6a1901c4617105a44f5475a9728ae42016a0fa213683b60f880692864200ea6e374476799ced151f311b4ab9576adcbd5cc7ec1ea6e6e91b252b7f3120d69866ba10b8214f2f4620b9310534eecb04cb4028b7c94af78de2d8a59c874a63e8800cad1e89087c03d666486eb8450f946b366ee034d35866704562d8b9b712ce70c7024f953e522e549376573f5881077fe1d85a52c1c92a8c381711b851a4345c68c8ae40e910c3ae90d58d6a2d3670d6a045636c1cd13064a3901a81816591e97b401c7ace86f10805aeb0b0300170e7804783c417b7bc02db959c90c4d93a6a336a353ae0f1050417387ecf1ea058b103f3382bb62b3aeffcc042020393129b571fd7a0533fb923505a0555d620264cce6f03eea7ad23820340d73e8c7d8b9e9ea4c28597870fdb2c960d7398f16556ae154399506772e4160c622186dea1476a77fca7aff9804006902cf7dcaa13a843c940b4330b141c4b64644cb348a7ac7edc2efb12e88217da11145d540a2bd7582126484985cb5b9560d54d38112a435fcf1264fca679fde7531f328bc27ff5e3559c9cc67cb6c3c5a7defec91f3f420ba16b8e8311e44395f3f11aad2bdfd2d5052e0ead92842ac698731b1127775f904fb90f43285d77995b4f40c7a8502d17c59fa4b68e109415315066c332b5736c07b529b23ec61930027f212bfcbaab51b5e8732f3957c0e0220622d3da5cf329958e06cb4b68e69dc142583ab2e02095628d8d30ee7703417ccb2cb3026ea5e32f98c0b83c3a87cc68dbf3185234f5d66172d758240a346e50e563f12ea9cbb2eeeeefecb350e61e87bec32a5e3a7ee672a92f414dd2477e9a44597d87ff00bed082716c194fb16f625e6eb2ea5658b63b42300a50724b058821811acf171028baadfa9ab6909d502b395cbc18d597a25502e52b10506d60aeb2bed166996f705fe00d098cfd8220545cb5a8359f6f5594aec5ace7bc159db6d93da258bcbf53cf2cc560c351dce56e205d417a258039324a1f117a508330128630788e22759fa6794d1f10cb8b8bad41af6b0c1061f289bb5d6abe83d45327ea17c4f7f4433d9727486e54395979bf0c20bd4971fc79ef080202e87b1f139f4af63217459500c206f657f16aeef7816e80b6aea13e30b9d6a5dbd6b9965106b35786cfe7e272cae9b8c1337b3dbd052d2fc8fe53c22b08ba711ebc4e01c8a206cb04e2e887c588ad46eadd99b7e5ff0031015b583cb049873cca15763cc38ae954985f28c6b84b98210ac13255b26ea450a3b952f311310f50473106e2388b51eac949bbd4144b5692fecc90c0055fcc452dbab81b07b09c7d2468ec3053a7316428564c657cc421428cbd731236289a485256b45bddbe99e08201a47f7330d916b355ef2da8d94d0c9008011835c56bd6b840745bb8c946dddd37c207de03b83c06ab442d1d450a7ea5106865d02d1b7b698bdcbd8e5fb80bb01a3a9cfaaa95e020acc7d67fdcab5ed1dd3bcd63c9984c611986b15d106bf84605743e031f78cfdeda6f4d6de91d6a83a541105aeb2f2abbc4da398e36184db5c285fa831296de21134a4778c424b41b979296a74408f5c4a06c25ed4c6a166652d99842082139ca200aeb4b1fa82952ede9572a5f98535ff3d0e61d6432a2d6345468f17c6e1a3696cb37c446280cd35160d79afd0b85900d38374fa85c73a74d7e4c1ec545aed29069f09f1002e802618c840aed60961a8a095ba5bc5ffc304a02b52adc6afa6e32d045ab9ad547442aac3e8902349a6635aa5afbc1eb23061d6759e3040b3a39adace8df2ec7f9a0c2eb0ed920503a45362d41425eb11664c74948bd3882fd92f85b177326603acdd9a86c8ac092c8d444126356e3965618368f2e5f1b9c44b512f402ae801fb3f59194b4b25bc31a33131cec2fe48043b406bd085d9c94d748090cb6833daa0d687a5d825f030409e96d615fd7fefa6606cabc5db0f434582dab74408b12acb95dfea17f330efe4b71c4e1259685ae371155644564c707686e0885948743dae2ac54166dce7d4e618961efab3f3fb96b8de2510b844a4f1a75651dd6d11e85aae4a2ddf397ea400ab599c6beb3997d2ea93f05c7ef050015543ae37305503c4b9b54d7596524b5618dde661b48811e025d5ccb31539443ce08b4f4ab1887aca3351a9cbf328f2c7e4794639c55c08143dfeb11aacc273d20253b3d29a73e626ece7343d8f7beb3130538e9e02e66405adef8357ecc1eb8de232fe06381dda640e388a2a854a5ae3a402925be27fc8a95d9c8dabb7a9b85c802aacbb663369b6d84fbcb6db3b0abd436c5a5eaf7eb190397a59dd33d8e500a67d4e6159547e62bf89482e250bb896ef691a38bde52fbc7ee9cc483961f7998d6df81186a831d43b76881db18b8cb001798f5140f0d4718849ed3a1834d112cc474c44d24e85ca3b421a638c0ca7a8e01c105afe903f1a756d301ef1a6b6d4d85bffcfad4690a4b9e2a0a5d314d4c1cb8aff9e265dd05ae47a0c40a4ae011e1abf31858d5bdabcc16218605f1effcc14400174696a50d16694ccb99da1b9fabfa3d5edd10ecd30bd08b0eb72e3774072e5afeea520da979dc333a0d5f5ffd8ce54e3257d0731acb77bdaa8fc24537e20b1c50bef2dc4c0aaacc79cfc3ef25956662aec38990e2968761f7eb268b0bc6707e615c34e114fd45072c52a1182e4f21430ea4707484c5f50e2d9a09dd188c19dce92a03f84079466bf888d004ed31b83e256e10294df682bdbda216b1fdcd3f820999308365e7ae8fac21815c817f31882d5a08a8c869a3df881981445be2549b5058440bba4d9ff99804434d34895db7ff004600a1b3b3f298ed30bbe9ff00659c22b17d592290da26c684973e18b4a0a3db172db04a2c97c32d772fe93998d71ecb47f2962c7709a00a781cc51d734e2dc1ec63ef500753972bfeb1555555e7efaaa8258ec7fd854507408a82fc5182c2cbde9dc802c7510b23de5021d749e21da625cbc40a6d04d36f682b6b44432909433956632c988e65710c29c4753de2900c7596a7b5844308cde1e7ebed150077b0673d6113a84e14d7e7cc08056865adcb1236c2dd660127abdb5002c2abcae5292a87a63d0bb5d5d8b8ead8b6d2c19e3d55d806d5a2a15a46858e2acfc7d83999f8d4b31da51925add36b58f66fee9cceae9da225ae8a3fc07e596841ac2e5f44541c45da82a2801f88595625c04ca46ba4034100d261b50178352c6f0652d83da734422e2be445340c4ba5c3cccc7e712e953ad5e6a1f5f3196d2290b73fac106d1aeedd4411104763002d80abc9d2216a45d75ed3076e7ad3eb5011a6ad4aac57863a20ec6bd4f16ad737ad7b5ca4858d014efdf6fd83997b8e5222d01dd2c22ce5bcbf91f1f640b968ae91fa0e0165ebacb3471b6f8e31eec719cc8c503976346f1a8f81d5e43627de1470d4c9acace4fe733016904c537c4cc0449b4ef0156958c262702a12dc199a4b584877b96d512c2d301a25675a8f517f300374e351ed18e2568227523b940d450e85b1f5d0c55d0405002fadb801a2a286da96d26a038a80aa5270db92bff20e69de78f5004d4df5aae9119ab5b6bd7037011a5d690f6b84bac9554f6bfeebec1ccecabd798aab6b11b0d4e575cff81f5f5f4a55aea112ab5d98732a05b46562e226b027b62fd90011c2cedb23f67b4c09a0905e2d16bdd864ac2588d2f2f159cc023ed0b68c0a5e9947e9548a5f6ebf615378f794cbacb2dd45db25543a6628bf309b3e63737980a5be614bfca39adcb297cf78816eca94adb708e4c2c89006615dcaa8a44984f9884a1dde23612cc6896fada866697a0efc4781568b5df12d1610d97f8837aec9b172a269bc158f9fa00c550da183febe80abb633addc03084b0d46301d3ec1ccc4ab2eb3da0022d1b4cff01f66e1f3e9afa92235a8e2166f6da860ab0508b792b7fd26872018e80ab6baa78618dae8109ae5df437d624f42630370681da37ea5d98c6beed2af172b2e31c4ef19089859b6d67b17f947ecac90384a7b8e7e9a320db5b861b02f63f31d8d18affa4267f787461f31e613b5c5b9258dbf32bd65aee60ffa9862ab5c4197e65abfda577fce6408f2cd30edea0aa51e22aabd60ec246dac4202b817e814c8d400821ba4b975d20b7581f04b6a870fa7f1ff00312f17de0df5f8f426600c953aff00cfa58cb485eafb73c7d9cc82f8fb454502804b9d67c03ce388faf105b4b52b26f9d461a597b3641260501315d609eedec68f05d9cdc47bd8c8b54e3bbd32ac50ac811a6cdd778cf4201fc3286745d8fe65c2916da2c3da0633770ab01dfa047b63fdb1eed17e0fbf701cdc7c40148ee627a602ece2290b4cd33ed3342c46ec8d32c14b4b111904eea5318e732999429039226c6f11cc058b0087ac4d73fafe881071bb747bdd4b043390e07fd9c9c2b4debbfd4440a9797a43d0a95119b6f2fa5bd58981b25b2af4343c1ebb3fb65e8b796605ad1e53e3ecdcf0b3fc4cb79a2ba45655c9bddb3896095daf90feafd06af59855e6ea1cbcbbfe988440a9796b50660dcbbedfc6fde24ca5030aae2522dc8abd80aeb6c7889089b15573af5327595a28aa80d1296b2c60851d4d9adfc7f76fb4363c66507f2f77fc156874a2e3574c379401a0034054be5c5207e10189329866d0833f84d67e3360fc251a486ddd4cac475d0d748a11fc25ec1bf1155c2fb4a44623c013f71875942354cc26704f06f862c154e01afa8f5234160a855f3e839301471e943562e6d8f50daa163b8ac90fb4cccb2d867711aab43bdfc229568abe950b281aceaf7ea902349a65d810c768341ab45d62ab03de203c0ac29cea52f9c9b437ecbf10d0804315942df78d560ab18d41f2f8d4395b1889e21a4a4e2d79522e81a8846b856ded4476c8b55b57aff808b14b3a47742bafe71de50c6d100df5316d6a3ab4c54d3a8c6384a4b1df12b454b6224a69b86d57e264525f709599529bdc62e09d79625b1a9634e4fe0f8f4b0b0b29a76455555576fd7ab958f44ba0d31e9ac0a933140b500db035e5c7ad8942a0b2f29899b6d1bebcfda305c23f2cb8ed2dad1fa31c7d268b05b56e88b79a8e5a07448662f40cb3c306868d9b3dd88ca5498bedec5a9d2174920543b83d2e18b95c82eac383dde48954ecf198c2509626925ff826e9616edd46d2b1cdf33824a745d046882a090620401a7c409607c4f61738226c981384685ce781a9c7235a06ba4aac26eb0b9bccd57862ef0d1d0341f702a016ba21431600d100e4d3b3d2d4a5e455d9a3bce7d48efecb2ee291ef9bfe63561c639c6f9407e9792869ba74c2acb0ae4e747ee240b40a70e7d2dfe4482341a1bc9a83200c17dd72fbff008b5d78c87408f150558dc642e8764c4536768d303282a9304b985bd4506256a4b94b530d524667329e0486e57cca1da79655687bc62a4c759a227a457c08f8a88758a7c7ff007ee1b0ea7f70d45a39f448a802aac94e3f53afdd60aeca2b068b5edcc77cbbb4473e56d7aadc7e848d8a269265691292dee947ee6acd639feee5aacd393388ccc15aa681e52bd31fe2d640385e6292161cee30e1aaf044557b92b046a57810b6b3e7d02be965f98115588d5b21b47cc01a200b4dc65b09ec71998c59776145ec17e6535420f8ea631a114abcff00c8f52275a3edfe0ff716d9c4806f91d660b6209607129e83655e8f53b4e5fbc90caf29a74f3af17f58b315aab4bc0f697aebb45a04569efe8c022c1c9d634aaf0304811a77fe2530e01855e598cbeb341d0860954a730e5849ab872043c4be806908b00bc2c5b0ace5165a696559f4334058a07c42f14cc2f716bafc41385a66a251300f2aa35d2d9f76e27916b4c2ebf738985c78fb7ec8787785d168be2031d8a77c0d788f055aaaa5f4586fb96a062ce90ff0958141471cfa9cc284178d6e0eb2eab783d5958709f2545bd30569195488e512b3fe185005ae0254a1fc25cd8795cc3166dcc333463241036e823510d39a8eaa02f12a4983a4a75876a97a4e4c9be2c987a3c471903da71840235f10401889b8d604b4dc0402e236830fc2bed2838add4141a870f6fb8e83694ecd9046b7c55b87b38d468e90a74020a9ceeba41a416477596ffc2731b7044adb984370396d0e482b61d2f8f53400a32d00756a5686e1a458bef5f781742c22dfd05035845354cc84295ad54bc41ab4e10b1b1461b50692017a8f660770d08c494b6710e2b52cbe1403d22ab18f160758f88852cbf32d743b90014cf24318f2dcbe01da254f08e2ac20e0083ff25442b2f0d55fde59e12a9d3d2002153b2f73324614701bdcb46b47185f6c47fc1b0d8dbac93f27fbf4041272e610810ea03a464397d08b7a5aa701d2fc13829babf6fb81540af682d5ac104f7200c42b21a1eeca1c14b2af4c42855e02534bc18bf5ab68dd50f07f71f101a0c62e33521868eb8d4a815594e2540180146177942dd994c7b4116a86ee237ab8a8a437be23f085c4200c6ae1211865f74d611e87fd8557c7ff00d98a91f9533028f16d96f11a0a95c678c6e762fb2b08b6ae33d0fb82057d8629ba79f43708a8d66650ebb5e900c592815b3ace3ed761577acfcfd77ae14f2e92816365a75f460a7005f98a874e5383dbdf519c40e5b756f654a7f50083f33302183a8ff91157ec5502d1796b509c7785805ab802651a9c814736d54d005b4c622aaaaabb58fcb0a49b30b2e0d5e72430142af84a4544d0dd7cc7b4f0d66a22acd80ec7f9a96cadd32f3fda895b5b86a2406c6e0b0b25226181572635adf994265d1a27e5d1bfb01500b5d10ea4c8f29ffb2a152285efb572413afb604540039596c3af74e8a9d0f460566942b1987328b01006ce218f42b1ed38fb561200bd098b9659add4697a41775f485a21054c71b8efd1a5b9b255bd23a15b7456df9e919c41803852b93ad4b152acc005c25fd83b421915a2634435b373325b686bda361d0028fa1fb65d46a1dcd7a56d4b00d39a4ffb0d4b52b15048aae2804b3872b667e8629a0087dc973b15169a84b0d0afb235d837efc42362995632ad9e658522ca6b922812a1a3a7da730739eb75f967da5ef1eae895376d076387c253efe8462e94b29ae631ff0003172cb35ba829c3c50d57fdf52962cbae87bfe2045c517359f4631cdf52df6ed2d075d745f88ceed6feceb8b90f9835435304ba78f89af80a2818810055038fad48ed5971338a50b62f72ecd00b95b7b89b401a3e821412b84ed0d30a141cf7812a0039137f66a6851a3aff00e42e600c8acf48a52de0bfd4150df0ed8a6da3f6ad330ac5711f957da38d66cc470418d3600abd6d2ea0b916e8a84332eec178d5e5255e5df7a71fe030252364176a4bc5643bc37e858a5274e786222d5bd0f4160ba8d30b3bed783fbcc6bbdab80ac9fb87d964a07888ae6e97c8be91229e8707d96238615980405d7f5fc41142e431f406857d2d1f74132df882dba53f9fb01500b5d10195d42cb9bf0e652c20b00de6a202b50ef8b6d01a1798008a44b5fb3bf787d8656867cad41995b26f1ffac000aa53a265f348840c4914db6e37c4cb0d6c50308613c423970cd04abe3bfd615a0b5c04a012d1598106ba3d13fa457150b055db599e2ebf312496a6ab6cb6bc049bed35f421360d5cf8f514c8a7a834a165acc190085c0bfc4095f62a48af010d514ac27b1888995b7d370d0492daccae0c0318dfd550e42f040a808ce737161c7e97cc0c2d900db4e7fafb0417437f19f4c174a3399b5839185885a2eab7f69ab393e78fcc796c15cc7955f98b21541bf28cc10c46b7d97fc4ac08dd83d23a8de49beefd68ac693507562565beeae6155794d53f108863c879dc52ccac58dca27162472d44ea582a555f7884ca05b41f1128ddff001e82cacb0ca584287453b138ae233327313c3c469b280c5363adfa8292cbd55c44994533dd6f51428949b2225637afb08b3994388fdb1d56fd2eb256f3296a0b28bdb5198d2a540f066549b52d5ec9528fa175ecd102a0029d1e7af1001757e3e93078320f5e23525428acdf8fe7ec086ca57a31ab6a2ed49c5d7b4bfb4731005383b8d7e603182e74e5d6222382b0610655a5b8cffe47845e808ae6d5bf61c2d77c89af118a16562a8a6e9fef68c8a57aadfa2c204d23a81b20b65e3e948750df9188c4a9b35dbbc648abcbea84ed0c73a0fbe096ef28a547fde256d00356aa3d7eb22542d854ad65caab405fee3762bc3593de1021522b26757ef2ec229d10e1fc441881e3c078fad390552552d9cfe20b3b5bb2e3b842be1f4a192cce1d6aafcfd8b5243d721c42a0a96559b22ce9061c2de332e2897592f10e7ec98ea208fa0ac33c4a52027c4015a3f90231b954b28dff0043104a76bbecbf6b665901cf104596ee01d37fa5c4320376228f8b1fb7da082a111e1fa896179ddf3539961616535c9f7b705204553bc660b0055d59c1146b295755e9be208e9b974dd1815b3da02cf332eb1e79dc55555577f5322e0ad7426fc55e51fcdc5840de71500f239be9f72afa8594bb7a4b22baa1c08a5a1eab282c68a2dd10534a78fb4b869a698581138e25a99618d5a4b74bbf9e1638871d8b16b3b8176ad8738ddfe7e3ed9d6570452f1ab77e65ed9e99185dba34f19846e5f3c20053dcdfd4de8044def5fa87df55b1acdd947563029554ce2e9afcc28d531740d04a5ad4565ba82701ac776eebe62a14871779fac8b20b452603e62b71c029b3983066bbe33fdf88605139794fb8c2b3ab65f6829411ee73f718cd5a02e652873dd4f4914a61be6ed0d4dfcc067100ee854d636e3cc3b1b9ba1c17cd7dae70070f19fd300456d9549bd3fddb0ab494b6bec05250dc4ab557bfd478246869410e7efd5deb43cf1f982a1bd1c5f5fccc70200396501a34e9d32eb1344b73895f6101a482f42120a02a8e92a83b0b9afb7dc3274581d5cb1d4e57eca94a1be5bdfd0c0948d91175ad5b635c5000eb2c0b6d47ea710a868e1f3dbb7e62155ad921d3ec8d74f726cb953a3b7e224348da58e9cf311396401aaea4c5079a3240016ad1064050db5af5b8d8514bd6364d8addf8edfe0248611b21b1b04475e7cc580568d1c1eb7f616d8c672625f392c5789882df777b7fe3eda005ab44a03402db9aff00ecf0d8204c01aa5bafb8c016ad11c0761982d826d300aa857f6a7cd4e2173950375ffd55cced60a5eaff003f6940921a2f52a0801868bc450ac25387a24403ba771022af2be94145a4686a557225d38e71f7d2ac51349057826582610c2b4dc0b255b438082596aed280abe685076cc2931156ff0048ac717d8a3e836ac35d5023881b06ee100a849ca2398b3a4b595cbbd43800c0bca23f0f9fb7925c07c0c0a11656944edd61918f0bf9455555576bf71ebaf0bf1799bd6dd0656630230a00ca5e95c38372305008554ba8effc248d8a2692392aad5795eafdfaf419003b07711522b45aa81a6216aedd45b6a52a63271f52558a24bd28d589719a8b18a98ba82d86e040ce88d5cbe2a471620fb6066f52b2eea2c84d363633105b561ddfb8c98aaadeb0ecb6653f8445c711b75638965300bbaffc8ca895bd38186bf139ff000dc16ea5ff0058fbe7bd7540eff72f39167221e3571312e327fa732d0c1b4899508f5aa0ab8ea6c16822877afb19cd601d330c019725aa23759392aeb0bec405325bf7f6b3fe25e782002258ec65640a16852ffdfba51e9f0406206785bd7bc01ae610c0403344085d1cc76aa21d8183f5e8ab8886db719fb8510ab57583cccc81d4df9e95ef7da1c23a06afa7d4c0d0b18ff8361616534ec80b555156561f33397bdeb7ef2f720776e6a67a7d2732844c2c7f12abf0b5dd07f33075006828afb4ce5e81e3a4645d378b59d3b4d42e577cdc4aab6af3f42558a2693eb32ba858390e20d03a28a80aedaed33ae5a6a62dfe233cd372565311555555dafa05a1541eebee194010ba6e81f91f04269c506c86d30aaeee0a1a466cc97b641ef1d141a6e9d3f411b468b7ee71f7d002d5a255d4dc0ba3de252ebe6e12e5c4811a4d329888186e9cc68cc633b7f333a43baff917ab8eb944451113770e630fc0c2fcf64fe42a3f12cae5e73e8f9206a590d5d6330fa5682c3e0219c45396b5282e774aff0018f9fbacd3cfc3fb72cd778a0d4a4cb6cb806226b6971c2791e0f44441a5bf3097b60bb70174543eba952bd2bce959355fba837325216807780c6712e20546d6956daed6b7cd4bfa700971f65222349a7e91ab6eaac7a61d7055bfe9fdef08a160966ccf696528d9d335069b2be231268bd01e834f1ee468417ceba4622ef2c4756baba58c88cb696a3cd24aed22956aabd2f848079556134260f4035a3c693ac1050ba88d2566cafa46d744ec4294d508ab37d9e250109c2d7dd035c56bdf5295894367d3045b1050f100aaae0ef9f4692cca68b5ff00be256ebca14a7177fb87d69805d172e5b633ad42540b4b46db47b3c05af42ea0e161b73a97aac35d2dd70f044f5cd53b96e4700f3de5998a64b3704b116b43fa7d01b1b602a1ae0671f7500d2376743ac147a0a2b27bc28b43946eef55326b2c1a20f15f3151d1e58f560e4b2bafad2409daf8ff00c863235ba5e3b7a06341544744ba6c3c5425011c56a2d50859dbff00b053ad113de3b5a460896261ae259288c09c6a929ef2ad10d91f4400b56888497417ab846a7220d244555b5e7ee1bdccb735d60a8382a7765cd5b2cd5bda05a22aeb36d5352f80c7a29c859d98ddfc86aac91f60d6a5187cec055eb571761b85197fe202e15dc4b0f1ed296497ae33f31fb09d855f1af44bf4421baae36f8962cb64e50317e8e366cd22db749c7d9b0b1a2da3444054c36aa1762772a3086e8b61116cfca31a88393a91b2bd49841267cb9c355f8ffc89bd42684c7febff003eb245930ac95cd4446a71fa28fdc445111363387667f7b4264c01f3b88245acf172c4be08d6aaae20e20d5c559ed3945490ced627bca4053257e61e99c55379ebc42a856c59342002de8fb84cad3410736696c81946650eb889b510028bb8ba6b0e7961112d5b65697557cea66aa076e6a12928a64ae2bec12aad0a95a3fdf100b05b253974d7fec7b91269d56b79dffe4f6c6ba76f5b5089666a4acaaa0605d569bf7f50c9d361c75b9c7d848d8a27243b41a884f975fcc1401c462763f899926605e7994b5a8acb7552daa5644a81a72217132bd140aae7e3ec87a91c268746365c1a4a2cadf91972e70d59ca5ee2c98e3e033ff065d978219d2a9be05372b0525dd6a0953d56dc7991495c639f5a32db70a4d0fef781611c8940cecef11588ba33eff70dad6abf84a1b6f53ac8abdb048e9a50accb0c0428736e3d50a686d6aeab3126296f75c3afe7eca4446934c1d504dadc328364ea1d62d58b4469057a6d41661f3c435dce94d458f9fd7a71ebc7d60ba161018a5abdf508f281b362746057c8dac3cd4aa70bef2b92eb1bbf46ab40ba307b7e6661c9452fe6014500384e9f9a895579fb3595ded8b747538788f44594349c27996adfc91afdc61dc5afc9972d7119295500460ba89ec2bda621b05cf68235e8c46d04662109462c750de72558b39d7f1096dabe6d5f9cfdb16ed283ab1160e61cbff9129cc4c09ca09b9514e097380d4be9a01f6ffefabaab9bd9bbe26074b7ebf65258ac7589556b3d218f4af537ef3d0eb05e3b145f5eff00602e032b5e20c0e93231da34ba59bd7d17fde21b3ab4115e97d3d0b59703b3cc228f2434d99c7c46999b76ddf9eb0fb20234d570bdb3e82b0a8865ab0e741287052868edf111eeacf98a1f623cb0d431250cc12e42570a0bbe11a94038be92e522711f15154d6b2ca553f19220b66b02fea26cde03ede5a5dabe8f12bd12f2c9306c4835e48e78982a4d47de94fbd466eed1cb7ea1830e7d88e85aadff00017b63a8d7d60ad02c66f8cf7657428e5177014501bb15fa824b06a0c029abe220b2a540aa15ea6c52f55a25b02b77d4bdd99326ddb7bfbb8c4a2dd5c2488c0064e846ba1bf69760d758d691d352d082fac6d10c6eb6636c14354d0f66082c769659fc214748af2b35f2c7057b29e8273f75ee2325fea11659516de2e63ab883486609bbc4da1cc5b5ba0f6ebeac636b44a68d95d56fee60dd0e74c5aa59b146eaaf682ef9ebab1735d5c2d4ad8035559dc52e06c9616345b468fa02b40aba0978b89ab4cfc430b0e8899bc4569b00079f4a542e9b8af80f1292d92397e8b03190bc95f3a809509a465fbe5285b26576dcb0a0ebde0ad697d4f2cb46f5de30eaf11b080d6e2817a04b6ed1390c86ea5db0b6dd7cc3875d06407111054259d9fbfb8f5965df810d40c15500d8eaa0a350436cdb79f698324a9d0666598163bf5f5ef405bee43acd86bdfeedbd9451e2efb18d3528b28ad945161d997bc4eafb425ad62e2253a27c95ea70f3fa964012ed66fb4484c56083bcc255f44bab45358d4a258e5d4be60e28b3565e9e9e8abd15f168eaf48bb2b479d7f7b4ca4675dd2ec57a00bcc42a6c75d23f7c8cd312ebff008102c51f788bab997c5e0a62e3bb12759553bbb6e115588d8c6a94ee8ccbd3b7a0b660ab0bbe669fbb51a9c8f1c7f7bca198c40b7fb8baa2c666df6976d1ef3ae879088c59495b7a71f8f5417870a0b686ff003129555777f7451b14801081d97b80ba16580d01c876d460dd655b05758a4e93640633897a76868d41da6e00508b58520dca04cb3efd6095d5c293f1ff006210adda8d6ff984027330fee250e355d58eb99a424b94725d56f1f129519564e1e21a6ca139100d38bbae20a6b6c07f6a32b4af55b8fdfc48155e6f9fc44915bbb5e3c464526f2fa53853887cd8add4508e4ecb8c980e00a82596d1c3510ad5a0eb5a1d387de10daf071cfdc3d69cbbf31216015b881c1a8d9a7e65ebbbfc4b458d5f58cb92a26314316abd494e70ff00df518be0957bc4b099969d1e3ee54f781542d9412e805f5fed4ba0d85317d1edff0065670b80b7d10cb9c408a9078b83220deef3e9918618bd5cc8caac9755dee649ab43c3ff0022708da9cbf1c4a748a1ab63b78953ce31ddf10b256d52acc365b6c2d9716b1bdb60b343bfb7e21fe12ba09cdb588544a111e1894325b4737bc7b41400ec15e8be994d4350bc41222d6a5d11e602086f20438f115da73796f32cd71f9ba95f6c17df9536ff007f98ef36456e98197686a9d7998970d17b25c94b8357d56354367a399808160e8707d83b04e006b317f714179d40cfb5c1f7e408247041aa4d3bbb6fa76fe6062525a8c78be2ff00e458c259003f1e79ff00e402e865b71cf581560e8ac4c999685f60412aab37b9411a6a2cb3a5bc1d5e204dba997e3a4e05f81570290525a33506014e569e0eb1954c2a33550cc817918f170c10ca1d5c402ea736b738b85e13c59e097f190b435e7fc3da7166f8ae6158d8c50e6f985965269c57bc3a0072b080a1eb7823e1ba2557e63e72af5852f9682d28e032b196df67f50fbb837535dbde5d2a86078dc41a4d7245327e26422d6afd21de5c46ade2aee27529f45c502cb87202cd31474fac2f539707303de5ef620b22b2960aa6bf98b9a0985cb13a0aecb0fe219094ef41d7b5530e6535d08af764e902343576d4bebea002f0adebad74805000e022db2dabb6eaa3bcc0689cfcc026cd6877f98ee2181cb104da1d5d9d259136104d7b8c496f78605c1fd207a8b2f15758636562363d7bc404c23d662aa54ed7c5c56b8bbc57e88b005e832fd54c5386f87fc020edb6673d58639841ea3e8f8103c1ff00a4c04021a0aaf56a1a56f680e286cf38a7efa2d20bdc273e10384a94747e62e18b7f10b29599c56ff1718a0306eaed78ed2f787a3f5883ae0738a022ba02b5f49b0156b596a008ae98a1eeff009ef1f9c8526355fdef2c9f01eaa0ec3ae70e0b3bdfea5eeb865970d037898ee01680bc5109911dece1f310d2d8aec0dac282c2f94b0ae98437bd4bd06c2bd6ac2345329e655e3356dcb83ea28d8a2698d21584ee186c27cb1e97352c2c2ca69d933fe03082d5a0880633bb8df2a95e2bd1a922ef37897e65ddf5ada4c3716b3f12a947674fbfb3cff642db318c123eba25e584036bbac07580a436755f29f9ef2c02114b6b7bdcab1e13034e9b133f898314e75c707a62096475fd6110e8c1a1edbfc4ead580bb5b5fa97d090b1c255e6d1ec4c08bb3510333358b0833ab71ef1f975c6c699ec9d215d5c97595bfe7f11d5e25eea5d352c60956deeb7d9ee4f42644aa53e25fb6aa05780abe7ffbde58117518b8d3ef2cac30a7f13541b34b9b82bda5a84d2b47f98c982ef2a9d436864711428d1afc3d4cb0d991fb817313b8905631516416ea28528dd7f3fcfdd680285ee3b341b3d1cab913f9fe21bd38850df49ace1f30469405adcaa2d22b9ef2c02a86a1320a0568ef2eb904530508ce8c696279d0471052e95ed6347dd7b4447a16a15b9b7476205dc6587012892686b2dee050a4d052f7da2d93541ce0159b68c04a082a8144418af609612e40cb625250e772f98c11480254236f095a8a1bb6a8ebf12987a44fec965be3fea64e9641b8bd930bfcad3de6152c721c97f2557fa5b9d2c187a3fdb8aa8bd6711646c0fe3d4c6cb98fd04bb14570044c94d551c1f7363d51083d80735896e050375f882c526c89795b10148a72c5742e2158aba9f883dbb914f2e83bb0b64a5da7d46a83f2ed0713d466fb14fcac34126a7ca2e3a55d705e095a659c3e257503a2c99997a027584767c0805b54481cd22665eaf9b4361b0e2b16ed6233506a6db9aa6d54562e39415175c46188baecf3a1a679944048f1ecd322ead4beb085b5b889d6d2ef921003531866a6273c828601dcb18e0ff48081578238ddd2d071e7fbd228db6648b6dc4f4b99c59c8c62851c3362ea5e920a65c44650b120a6451fb84c05e9567ac2e28504c54e51b5c9fd4c4b953e220fa85f882258d8e921784053777fb352950b6b47c7fa65b408ab5276e53ca3b3080bb031ef4621b91755be2084b3586529737bb8695178b9c40b82a5cd1d200d8822074398d0300dd6e5012a14c42af68090f664181d5903b10d357a60d6bbade4cea621a6351603174005050cc6ba06c0c2c71cf88d99000dd6d56c986dc5ca58d06f93697c27b938181cc0d8044331d2944bb5abf15b3cbd666aaf1fe8c050a5df495980c745f58915b94ac30152c293b4bcd0f3157133de731b23b499c14566459b6edbfb95887f3f086746fb33154d598e63b172d214a4672665e945b7aabfc438e8005a2d51c6e02ae33dd35bdc2b4d3003a3a4c64a3da0bd5b1a758ed1b39c35b944f46e75d58a882e6977da5da472f1116055b5ed032ee35d61d0e1c98a9706778c6611a56beaa8bcad998952b681650de0de5032519292155539390a43967428877ef19f2404106523fa41bc65154577afdc971ac0197b28c28cae465065c0f0234fb2d9de531026174efdff0055fe885f21e602d05e340fccbc21ac53e7f9885b5855f3709c056c37d48f212f840ad57a8ee7364c6b03939cff007f7f74b76c86e26051599562b5ef36d0ec660a85f83887eb8b66dcfcd45a0d6c2b9ecd72c35a369c3a94136a9c317002c8e10b2bf88908af0f9960bacebb4174de33773285aef19967ab2bc0d21f136f08d4d85139db101ac56e123f287c201a298016844a561a5df78ad51a8898c5dd7b1d054e009d9af9df34c1c092a130f5a3a8f82fda16cd84659c3ae6a028ee24fc02d38e4be629cbd84209f3fa9688d75b89622bc2d834f70ed005df7b3fd10268d26304adc2756ea35a94b29a8a591025dbb1fccad3060276f42a8476667fb28eac75554adbfb673315298097ef1c8075566260c992e88799657bc510d8399903359cac341be2982f3e60dc2dc90455daddb3e41216d6ec756045d86e22e52fbc3b0a8a890748695b1e661ac9c4ce2f3b96e8ec3e65875c03f10ae7c99b6d07a8b5555c44502391448176a62b9b1d4eabe14130ee158e390a4107723b5eb19723060ded1474a271603ce0c47d777791806c1ab39011fcb91e5398756afe14bdbb9e475ec5bc5913954f11c3fbad40b46a338368f7b1ee436a9293bfdd29c01295848a5286f96f7fe19b5586173120d3bdc6b2ac2f67a44b0a9299dd4b904aa131d1cca7d0b823845eb9a8fdb1884dae842ae473ca34c89b1118c198324c088705c520006cc5d7a80e8e5cf7e20c97766d61a581343ba9b00894f261a3286d88be532b2c4810b0060d2cb30a0dbd65c2ee8a3a44a200405c1bb98092aefa93765d02dd473451976fe331c53ea90c55bc98097c36625598c61414705c013c5c1ccd8562e056383a439a4ab0151a10ae06edc972d7c822ae09ac179eabb8049cd05a4de511db52c19fb716197c34fb454241a34d55efc43a91768bd0084a516ec06c9db38164b000d4000ae00e89f057da013816a1a3d01742ff008b6d65c183b4b5f1ae87a22c16de9e8d5dcb2252e9d1082e5a8e71e8200a60a83f6f161ef0382231a84f10a4f999c15ebbf98f165501dfb4cd9a38c1e43a406779e78826031589944975bac4a175b4523734e2815d97a90539575a8920ad6d88c2ac71141956e2a4acd2f230997dd99ee08255bbb0820583867b3160552dd99be545df0ed285d1d0f98e7a98bbd4066849b3e2c2dd046a0c0051740d9759b8555460ba3ec3fc21e8edc0d7b78f2fb4bbda82de87108da679e5185bed858acc019572afcca60d5b8bb78498ddb896116346f19abcd7da5510e16939b398e0448e05d527b4bde1a6bad7f8dcfa66114e4bab3a4db10d61683a89b22dd56f0e2263337d6174d936a6821e7e04dd96e8f600fbb8f8894a08f73ec5d0740eacb34e75184e65e15b321e9da3346b7ccf2cb5cb257bd24b5817ab6a6bc8155623085752afcb9740e2e1252861ec074aa8ca86cf1855dece52ef56f0936080ba83b9d254e43bca8285d8a308f48dcb3ca5727b471b17561c90f29c597795cfb4c88ac52f30dda5c99c4bea545e030430082821f6f8978c1564080082da1a3d35163a1b1c83a272437ac940eb7b85c8ad6679ce981285a170783edd51ca82d8e83fc948d8a26a221c3a82b55ed1012d6a6509e22b45eaa11fbc50a2d016a8368b7ed33c6336abd70cbf32cc082e0be19fc46d53e6a3ec64f723d30d89492bd499b4a201834cab6f31701c412a838148e05a8941d630e6270bea8dbd48ce2bcae89c88805eb8fd72c2af36e140cbf3f81c4cda82e51a9add4ed84c2df0dcdcab0b184ea0c8356982a8633d666c96fcf686c6536c212761894945d747a4694588b39ac43c70e8ecc4581bc1d4b98b6d673509c002a82ae23b0bfd32b5d0e2b3e8079ac4c5405005af1098e8221f054d803a4571fb40aa0d86deafe23e85fea1889a799b9cc0983a622fda78dcaa5d67287877f1119282e5df3922970348fa08caead85759725d1e48e443d2580aa6650da50ffd8c63b56e9d35055db4b960c9b2295215890f84f2f61d11015955deba78834726374eb0409562ca109c642aac0e1880abb2e12eaba32a8b9745c3d881b57719f6b77d6067b7388e460062dd733d39e4fc92fe46556695a4859a0437cf59895d9cb47494d03201caccbf743821cffa82e6f18d74828d8d26980bcb456e65f03a478c7fcfcc28b97297b20b81330120e3e5f1138de2100414f313215e262db0e60c358e92860309b848285cf0c68d60abef6c746f635c1c43100c80f689b42898c9c731eaf38e91d1672b784b225da20082dc0ea238c0a41647e41e0c6813f78d0a3cbe2a3fba9c0f04797a2d6ea241554b5b8f62331982a5bba7408ab131d12cadeb8b512e6e7acb0aee398416b71770d7cb7f11e10cda1bdcbad029466f533f490765bf31af459a7305c4720efa5c4f69747078ff004bcc7e9d938f42b82952bad46c78af44cd601451d8d4bd619988bca0134442e48fa10aab24eb057c41bb081c8e0bb85ac4bc2d213b2d85db7533a6f88aaa0188a2b2666102d42a195dd90ad31e08a45168ae6b714aaaabb62896d6266f038edbf694470a1c34c05416edc8a56ac82e7d3bc518f4536975e0cf6202c34a15c4ca0c9bac5cab2a383acc538303577ff2104885fcb092c03cadc31101906af97e262dd541a0730980a1c1d0ca994e345d62704308d3e229556de597fe78d74f73ea018acab8fd7d2422463c0a68f88f24e022f9ff00e40a20d6c8974e30897d22dc7c505b2171add7552dd11dee7882a0adb1812a7df84bd01a6e24eaaf88da5621edcbe65ad801be94404455bdcdc2fba2b438304f5070687a3324954e6ec3a40d197da0951b44551a15de8f79582e334a30ed1543182729617339d5b5af111578a7c9a3dae575f507e52d1673bbd1fc4ad16ab02bb1080da60dd5ac50211b15971ca97d8c7fae28b28384c0f198448d963b299d1c8aaae4d40b46768a0540a0562fc7a5de50b74da25c5ec7c3e65fa04ffa323ebb438867d56c0c5ac691cd310b52ad56ec6f31b95aeb8f401cd17994a7581422b464a3f11adb3777a8d7213438eec74a4dafaee57a0d20859ea7b20fb430eefb4aab3de3baf668599e3794f73300d2421a91a51e683cdcc61975e79eb12b416518892682d4349ef1669b25e18a2e4e19fdc64a4ed5b7fd5f1f4541605a5e5e910ba0b1a5f0454e9a2b4b833ff512b7882bca263403b1203ab2ea869ed0e5872af816717ac540cad8a157aa61ec3e6320dcb4bb2559ee1129d941ec7a4a2a6ca477d22a949a0c0d55da888e44f681554b39cdd758bd0096a624b85cdb3e1d08aadadafd8c3a1a1f0f494cf30324cd1a1b4888f7c2503010e8a0511b18b9139e9e20d062e5394b322c3cdc50a2af2ffa43d06730fa516a21b6aea02a0f4faea546ad4c0b5e373205a1a4e084a4d164bfef689e4a5012fd628d07b41682a9bf10940634c5457dc8badb51285dcb783c90dc3b45fca2c5145638262a10c6d2cc8524b409ef2c8aae06b853370810e81dbb768fd9b970fa3bdf609ee2c2ba90d415767411aaaa5157e57c4634b71c8ae92a6bfd195346daeb0628d4cacf65f49cb40c1fdfa1b369d566dbf89cfd206b48c6711a0d508abbb3f899fc7add1437cdbbf4c2a054b7528992afccd9311502199c5a731aa290c1b84aca5221c81c748c12945c42111470c0381e580727ba091776759430c5ae87796faca0d2b4df53ef5d65376dbe27917c8ffa50aec98c51cce6b635ef1e36eb183de602951d4fee0ac958b183593b4028107fddc1061c1290fed88d0655aa87aa1622723301500a54baada5164acfc730f420a3634928519714ad2f105982163c86e5bc17cca96c476f0c6d62b38dca1ce4c1ea4bc1550556b117f2213ff1898e9988c15d89515a3a4be0499cd799ad0b1687a763f70ff5c7d60085a764097d2bcafb77dce8ce65962d9adc298ece9364b8db4eacfcc04c0da9323c2424005bc31114444d8faa07bc7baa3cd01793bfd4baa459131f887aa3029d1f210b40a8ac3ccd0afe25841f300ae69a8416aa2ee35f7197684d4971a6b398e3437677dcd611579201a121e03f33fdaa4d35326ac3bc5dc82b9a75f105884cb43896a6e61e8f697f8cdd37091a156f397ff3d188b798f5430f633115722e0db14ac3776daab98e4e86cf424594b95bf1015582fc40bd916cfbfa12dc5577acfcc34165838067f98952b6bb7d284694651a892ba711811e529b072a111840587784349af88514b5c469ed75788bea714393dbfdb11886d688a4a50cae53b4b2cb7b2f5fb838dc8d067025b32406a104d9c5579805c2b40dfa00ad0393ac2193612bf6799663a97842f48bb951e85637926645b3ac8dc3a0e00335f8f5e202a32fe50f17e8c94f4ddaa00e427329088abae7884a61cb31d0b06acf303ac3008593f1108e32c8fc1fee09e31aaebe86a009784b5b0252bc31e1d3fb95658089c32f2de46b9aea7582ce0c95a8c4948d93500fc317af80d1e8d16ea91baba9a6ace467b7d0610b88e734e7f7f3e9fc7fcc0016d73d20f09b58d8e7b54707539995f20f7dc497b99a815b4dbf28f75a5aff00b84a121b6f5e9540f597a8573922b4c8fbe62002d34a5d778cf2746b6448d8a269258ab6779dfd4222366eb35b95eaa359a146755fccabce6aca98e06eaee5a09ad74265431d1804640e25df71b2bf70e76d3c05c3f6fc469a57fa9041501ba8e3b7fb8398d32289a7d3f4315d16779c87bcb045470b16d97807a77a89c3dfafd6934d40ae0bc43619b376567d2901ab9e034cbd182c54178ac869fa96ad4da88ed692db4af99ad5f7885b7a2f52d8cfd243dc72f9201e028ee85d5776b37fee4e62155586e3b053569fd31cb68715d6183c1880a180d54488edb6008fea2150d7cae393a4d9f5fb044749d19b05983de246c5134900e0283d5aafef78e851b3ac0cdb1d08117683b8bcb959ce639252db5c67092e241a208650a2ab0c5ffb148d962727d07d5bde443b34c6b2f26c5533da130514615a8b88c3da174ddeb3d370cda0a2681c074e6319860115143b75fb0c3453affbe60a363491e5b4c6747ff206a602d61925f6277a115808a4a407bcec7e15ac24c2ba9dba78ff006c7d486335c3cca130e8318803944c6e0e6aba50c4a86d6291fc769b2c5e525bd586f98511788bf88c85853ffd8c98db4bf59876489ce75fa8b6a980cf99565075fb8f852258ec5e158ba138178579e9700b9e2c1abede652c0f83dfbffbeb0b0b29a76406ae397fea350671b8fa979a63ece154ca8a79ae23b6cba8d44c52eae63ca00e87e798a4d06cfad6f172a6bf9aa0b4e0f115a0cf116b163c9731cbd87f31da614aa9f8895ab6ab5ff7c13a49ec8a40b315b7895a520a22289a8550ba0a348a1335918603bf8d7689d81c2448d8a2692565c8c0bbf9fa8958b06533c07b15f98c44d90fd9eb5feb082182aaff008c12b97bfc0ca5e619ad42a2f984775eb7248d234ce812d6df3c7ee122bc8d8f1c9ef2c897796438e64a6bcb05d779859c597dd9affbf4235e17bcf9a0f78828a834e00a8703042013123e3fd51e06af2f4968d448617fc526b92201ae9de047ac10e9d38655b5098bba8dc732cc0650873687a0b15295b076edda34b106fbb541f882217aad09cfb16bd50369784fc407d54aeaddbf144c491c61186a72a897fea74f036f4854ae19797bc05bcff17f8c344aeb2e3dfb4751639bddfea0e026a2fa0d9962d430862de3710b1425564885c11f9768e0bf27211d005016cc036db8f4af420652eaf09fa637dea3605523c7a3977506d988b553e5a15ebc57881d58f07243a272c3a80ad4aff51d32edfe08001170732928d54bdbb5f68756405b881c0b7b157f6d8ced092c1ea960d057f5f4479766a3a2a00e324105f288773ac568868c33c5bcc5162b90a401c88b298a05a6c62d47f1707905557b4212136d4b11af560ee4ed1a9cc0f6a23918b2bc27596e54e7a448062c103a0ca38892d370a870ca733e3fd3de0a838c6dff0020050501404a42c04116b89512c55f9fb4fd362ab7166cb6edbcbf64f4c5cf2cd6e171417c9f31137aebda08b7b55a42b4dd8df4845abdc5dc7db6fbc6a0e8af5b998f917412bff6580abd38c1f8b99312a83ab11ff219ae636a76e60260786230bf32cd547ed09416f6861e0def2cae0e8b1dff00a67542002d38a5f13b845d5e0421a8b52d0417308077e7e7fc50b2064ee96aba407a6029e6889bb30978c7a0302e19d42a80e831e0a1eb999325f822c0ebc10493935bb15fdbf30c463de28daac01c15daee5a708486b100992f8830b219a8cd36df32f6ea9351ea2a7fffd9, 'image/jpeg', '', '2026-06-29', '28huynhducthong@gmail.com', '107664704264935131673', 'admin', 1, '2026-05-06 10:49:37', NULL, 'Medium', 'Đậm vị (Bold/Rich)', 'Bò', '', 'Cá', 40, 17662000.00, 'Có cồn (Alcoholic)');
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `avatar`, `avatar_blob`, `avatar_mime`, `phone`, `birthday`, `email`, `google_id`, `role`, `is_active`, `created_at`, `employee_id`, `doneness`, `flavor_profile`, `fav_ingredients`, `disliked_ingredients`, `allergies`, `visit_count`, `total_spent`, `drink_preferences`) VALUES
(5, 'Huỳnh Dương', '', 'Huỳnh Dương', NULL, 0x89504e470d0a1a0a0000000d494844520000014d000000920806000000c1ef988a000000017352474200aece1ce90000000467414d410000b18f0bfc6105000000097048597300000ec300000ec301c76fa8640000e8ec49444154785ec4fd57935d4996e78bfddcb73a2a14b45609208104525696ecaeee6add5d3de2f6b4f5dc3b4672488e19ed3e9146bef0798cc66fc0571a6924ed3e9097b499b9d33355d5a565566a2033a1b58c40e8a3b774e783bbefb3cf8908e8acfac31c71b672b17cade5cb972bf1fffa37d7b510021710a095466b0d80d61aa5355a1780460850aa406b4d9167c4e98061b64a2a6f72e044c157bf7d80936fec444719a9c828d21caf08200eb9f8e91d7efeab8bb4873e7ff8d77fcee9b7cf907412fef17ff80f3cfae80aefee39ce1b478fb3736a1a1f811412290580c9dbd85f009347002daa57230834028d2edf9680446a8144a3916804a080022dcc17427ba0059ed0145ad117019f5cbfcd7ff8e92fe82429df79fd34ffed9f7e1b9d0dd1ba20f03cd020854009c1fd34e17ff8def7b879f326dffdd33fe13b6fbec98c2a905adbf49e04930ff30ba4068da4a725ffe3f77fc8f9cb17397efc18ffe66fbfcb364f22b50274850edad24adb180cb456264e0d5a0bb48db7280471ae586877b970e70ee72e5fa53d1cb06fc71cdf7ee72dcebe7298862f50698a27059eb039d4d5d8ab10e3e9da3a2a2f0081306f6989d69a5408be5858e2fffbab5f73636d8999833bf877fffdbfa53f5866796989470b8f585d59a3df1f92c443d22c27cb33f22c436b8150a3544dad0b1482a2306506505aa3282874811748840429057ee01144217ee033d56ab263e776e6e666d9b66d9a6ddb67999b9ba156abb173ff6e1a8d1a8d2822f002eedf5ee4fffa7ff9bf73f937e77975db7efee8f5b738b9771f3351442825be747500585e1ea79846a3d0daf0e808022d259d61c6d5bb0ff8f0d2156e2e2e932885f43c0221980e23b6b59a6c9f9a62ef8e9decdab68d5dd34d9a1e784221854208d05292499fa561ccffed3ffd671eb43b7ce7f5d7f9975fff3a91d404bea0280a84900821d05a2385404861f26465aeccd9c4b58328cb3776d7046d7489405068c1f59536ffef1ffc98f67a9b3ffbc36ff1476fbd4e940df08529b7e153c3c34e17397ede22f9c7c2c531c997ee992e3459a1e9179aeb4b8ff8e5d5cf9967c0bffedfffcff8ea5fbe8b57074f8094e0fdabd7ff77ff5e0881947214d784f6714288a59fcb802a0a0a9591e63d0ab9c6ce0301078fcd31bba386f20b0a14aad0e85c10f71437ae3ce0e6ed45a677eee5adafbfc3d46c8bf5a555cefde663d4ea8013bb0eb2776e078d3042a0c129f34a458d2bcd0a26af2d3692c8dc71025b15336c9ae6b904045218b6ce84c7fcea3a976fdf21290a8eecdec9d9a38711450e5ae30989b0cca684e0cef20abffdec0b0229f9d65b6fb36baa85af5525cdc7c3097d997f61f296e271e1da0d1e2d2fb363e7765e3f71829afd4223c032d988b1374b4b8030e5334c09a0087d8fe9a9263bb6cdd16c34504ab1bab2cccada2a852a68359ad4a20061cb214c1bbb690a9b3ea95cba1a304a1b322179b0d6e697172e7061e1214990f1b77ff7e7fcf57ff31d0e1edaced1130738feea514e9e3ac6c993473872ec00870fef61cfde1d6cdb36c5ecdc14b3dba699996b3135d3a4d1aa516b84d41b01b57a406baac6cc4c83d9b9263b76ceb27bef360e1edac3e9d78e73e6f5939c79f3145ffbfadb7ce35b5fe16bdffa0aef7eeb6dde78e70ca75f3fc98993473878781f7bf6efa4355b27acfb485f237ccdf4b6596666a6b974e9324b2b2b740603922ca3596fd08c227c29cb626f5436f6bad2f018ba9a7a51083ebb78990f3efd8c859555eaf51afb77eee4c4bebdbc76e800af1d3ec8e923873979683f07776e6747ab4643824f8e140ae97b48cf33f4d5d04b53ce5fbd46378939bc6b17a70e1dc0c7f2ac52a5d2a492077b6173e72e27cb61b0f97da7780d8702e41aeeaeac73feea753c016fbc7a92033be7f08bd48a9fd8a084aa716f9acc53438c33a2855626771982f5e1807babcb7454cae1d78e72f0d543f891044b2befefcefe6fffbd1020a4acc4508dce5c9a8c8e6bfe421564c59061ba86d7e870f8648b83c7e668cdf828afa0d00ab4843ca0bf9671e5e23d1e2e77397cf21467df7e1d2f8087b71f70e1fdcf886238b1fb00bbe7b611f81e68a324e4732bcd8ac299b80f8691c7df18294d73dfd0c390ca29cd764569eee2ecd14350e4084b4c632d0894945cb87387cfae5e65f7dc2cdf7ae3755a814f50a6f934300ad0e550d81ce6c2e7c2b59b2c2e2fb173c7365e3b7e9cc8665d97568df9bb15139765ae3c16028430aa3a0a02b6cf4cb1736e964614d2e97679f4f021be14ecddb5031f851456b036a531e3e9546f594804420b1492c2f379d41bf0dec54b7c72e706eb22e19d3f789dbfff377fc3ccae3a7e98536bfacc6c9f62e7ee39f6ecdbc1fe833b3974743fc74e1ce6f889c39c38759413a78e71fcd4315e79f528c74e1ee6f8c9c3bc7aea18a7cf1ce7f5374ff1c65ba779fdadd3bcf5ce6b6578f39d339c7ef334af9e3ec62b278f70e8d80176efdfc9dc8e295ad335a2ba8f170af0345abade480152233c89f060c7be5d4801b7eede65716d9da5b536455630d36a51afd5f13dcbcf30265ce6973626b86d8040d84b419ee7dcba7b0f8de0d081fd9c3e7694d3870e7172df5e8eeedac9beb91976341b4c871e35a9f1758e2734d293e049b4303cac012524715170eeda35da49ccfeeddb78edc8217c2b635a8f946659af2f41695ad634c5b465cd145c5f58e4c2cddb4cd71bbcfdea49764e37914556519ad6329d907bf3bbfcf91c707c391e8956ca284d0ded24e1eeea32ebf980bd270ff2cad96384750f8d32bd5f8d31839d90553337326929137105d108b4d0e42a21577d5a3392b99d118de900ed69d3e9501a8f8022f3585aeaf168b943d89ce2c0b123d4a6ea24e99095c545b2414cb356a7d168203dcfa466f3a16cb7c5e565f2efe670c2fc34d475d56ae213da0634428fee6f0e672f8d2a32579aa4502c3c5a24cf72f6ecd8c15ca381675e7f6c6c8f8306e33a10e6cae54f54ba7596bd36658aada0290b8c160a2d1442e7b402c9d19db37cfdcc29bef3ee57387df8307ea1515986d8c8164f44b5bedc270a4d023cec7679efca153eb8718df9e11adffeab6ff1dffdf77fc79e633b40c6683f477b195aa4682f45d615f5d980e99d4d761fdece91d30779f5ade39c7df755defac66b7ce50fcef2f53f7e9b6ffdd957f983bffc3a7ff857dfe09b7ff62e5ffde3b7f9ca1fbec19bdf3cc399afbcca89b3c7d87774373bf64c33bdbd41d894e830a7900905094a276891a165819605781a3c109e407802a40299133424dffe677fc43ffcbb7f60ffe9232cc47d3eb87e8d9f7df61917eedf673dcdc88544290dca34162658331be30ed3da7094b45c2585e4ecab27f9ce37bec6b7df7a83b78f1de6d8ce6dec6ad698093c9a9ea02e359e52f85ae10bf0a5c0f73c3ccf430881aa746d3d296d8f5290e5f94896b54b7f7325f5a230c684c9871290158a76a74b56144cb55a4cd5ebc6b5627b3f8f97ed9781711d37ae90059ee711f83e599ed3ebf58d7b47185d546885ac8ab1abb82a269594b6be3000a50b72dd47fb3db6ed0e99dd111036a09046f8500a9d0b069d8c7b779758eba4ecde7f88bd870f203c41afd7656d7919912ba69a2d1af5ba494329a38ab4b6bf477971c1e5a50af79ef363967dbf0dd09b842a9cd29df43e56dfb56f68f38e043c29d152d21ec43c5a5a46027b77eca0e649fce7e1c18dd92af32084066d7d6113e179a0302dadf95e21554648c6b69acfc9bdbbf9faeb673973ec28356984b16c90364d6e636185708ac23c2fd074b384db2b4bfce6f225debf76993b9d55c494cf3fff87bfe2c4ebaf20c31c2d33b430c2a68446494d210b0a4f517805b9c850410161818c0afc86266c09ea531e8d199ffab4476d4a1236c0af2bfc9a4286050405f8266899a1bcc20491a36481120a2d414b6d1b132bf8c2d17fa4fa511933db9a7cfb9ffd21dffdfbbfa53637c3529ef2c9fdbbfcf8fc397e73e532b79657e8159a427a28d765adf0aae1a6327ab0fcb4736e8e3d73336caf87b4a4a086c247e1098d2f478a5048612c4c69140f0884b0cf5cefd0fadc0550140558b7402937257f6d5a852f0461e55523488b824eaf874633dd6a520f03241acaf1852719452f8a11915d3d3ade97083ce9110601455130e8f7c9d2bc5425852e905a6b9456e5e08fae08a28bcc2534124c85d605593624c9d7a84d25ec3ed060fbee267e4da084fd5e6bd244b1bcd4e6defd25f06b1c7ce518d3db67502806bd1efd76174f09a6eb4da220308ad2b57a15463203171aa59c53bf7c6aae2a0c6058d259509b11df116dc4ba8f43498f4de33270f9454856d6d7596fb789029f1ddb662d2b4ca6f9788c97cedd016bc35798aa524f9646eedfd3c2e4dda5e62c6c85a040ea82502866ea1173534d0269bdbd230375021b736eee8efe6904ed38e6f2c307fcf6f2453eb87289855e875828a6b64db173ef365039e8dcf4838430832852a084a1b112a0241418fe2d7441a173348509c2fc85027481100a2194b92f14b8dfba40399aa26cb98d5ab37d1cebba308516c20c78987bce37ac411784b58053675fa3b96d863e8aae2fb8bcb2c42fae5ce2a75f7ccec7376ff2b0db2541a03d0f5de90a6fe411ab98b5322e20adf0046610ce2a42cc5bc6aa94c677a9ecc0ada8c40db6d1b2fe556195e7a48c81f36f7f193029290d499ed3edf7f1846476aa45e859abfb4b44490f414506473c3a7a2ef0a547e0f94804c3fe8022cfcd9b56ff48ad154a99aeb4b6c1a1549ca5056aad3fad288a84246b23fc2ebb0f861c383ac7f4f6a6e9ca38e5a204dd4e8f7bf7165869f7d8b16f3f875e3946bd51436b45dc8b19b67bf80a5ad6f7232a6ac054eec8cfa2b526b7dd8acd04134da54bedc20823369b7c67b2c246f7464f2a8c68f3e414b8b6ff29a5d142b0d26ed31f0c986a36d83e378756ca34b3b6e1795a544be714b7d67684dc0ede19e1b1715b4db6d1427e0284b10085d6480d42297b6d14a4d41aa10aa456a52d50a5fe6614740a5256985121c8102c76db7c74ed0abfba7c810f6f5ce6d1a043e683f6c00f3de33354858947c8b2db36aa0389905e6949216c87b6626569f3b1b916c2ba364c301acfe4d329462c5f8ccaa6adf21c957554165b48692d3b298d52d782244b48f21ce549c299698aa90677fb1d3ebe7f9b1f7f7e9e9f9c3bc7c7d7af737b79854e5e105b9a18ffa3ad4b6bc40870e6195a88d291e66e2b51e1646d54be29b6c9a0ae284fecbb52cab2b173bc58be53c1e3f8f471cf9e845c693a8301dd7e9f3008d83e374be449549197f5cb449e26f3362e1923b86f36be3f89519d5396c77c271184d2a316d6f0f0187407a4c314cff2845665f77ce4d71845521504f70ef66f4196f7c88a55ead329fb8e34d8b9b789f04c8bef147191152c2daef1f0e112da0b3876ea5576eedd45107a08a5d1a922ee0ca9099f6658436aa3942752b524324258148555eca3428f610b7a5599dfbd6262188fc7c89355ac1396aaefdbaea95302c5c8e22b6923056bdd0e7196323535c574ab85d005525877c15363544633fdc208528136033e9ec00b03d34b70d6a5762e89adc398f2b5f991ce4a57e6b7747e35db9d165a21b5559a1316a6c64e25b2fe5a574221acda1412293d903efd3ce3eafc437e7df902bfbe72812f1ede65391be24dd52094e0415664e47966ca3b914f2a756714ba89bb6c58ab997056f76382f1979b97cb46c206e7779c4cdf24ee4c4f4a2e0501be60757595381ee2873e61a34663761a6faa415f0aee74dbbc7ff31a3f3cff293ff8f4537e7df92a971e3e62b11f934a1fed05682b924203a51163f35c32b0b5bc2b70b98091fc9a3a347169344a68909e65056d5c0bb6c69ca271dfba224fca8cc3069ad87bd5b01972a5586d77190e636ab51a33d353789e4429a334abf2b6194c3e37cbd16332bb2946742defd8fa0e3c9f7a58239021699233e80d118067678d49ed945c297c36ba9278ba9439b419652b544c92ad40d066fb1e8f9d7b6b442d41ae32d355ca33f22463d0895978b8c24abbcfd4ce1d1c7ef538b556cd748dd29ca41d937687b4a20653b506be30dd09c317c2ced17414106479c1dada3a599e6d59294f82a0e49352c04792e6686e95a636c2a46d8bedfb3e9e27ad4205e5e63c3a01129238cf595a5f430153ad296a6168ad35cb0f6395f4a43294b561aeac35a1a4b14c82203071688dd4da74e59402a5b70e938c6d83d066c6aac4c435529e56690ad38592683cfb7e69998db268722b8c15841f904b8fbe56dc6baff2e1f52bfce48b4ff9cdcdabdcecac12871ed1ec145e3d44db01962c4bc9b3ccd684e92a8f847bc40f259fd8748569ed36088e121a2554194a61316a6493ba9f08a582a96264a99a4b7b21616d75953c4b0d6d0478bea4d96a529f6de1cdb458178aebed553ebd7f8f5f5ebdcc4f2f5ee467172ef1fed51b5c5b58662dc929fc1ada0fc99540292c958d1f5b588bd7d48bab036d0cdd8ab23256beb5c06d5ba2ec6086e9aa583a386b74d33996065b3f9940955d2748666829c80acd7a774092e5b41a0deab59af5671a27c993e12cf297814addbb465edbeeb9f4f1f1a0802233fe5fe3ef14c842c7289da2ed5080d2ca8c12a9919fd3c5adb5a6502949d625d5cbcceeca397462861dfb9ae017860595264d52e26ecad2fc3a77ef2e32cc05875e39c1f6bdbb10818794502439dda5366a9031536b9aeeb99066be63a98446ecabb466bdd3617975d5d6f9d3107833544d2513b7b31c37bc667f0aeb1af0fd00cf4e1f11a5d2b10ad1767fbac3214bed36d2f7d931374b28251ec228214da9b49e072e1f0a537e4ffaf8422295c6d320ac6254aa40abc24ca350138345ce7a51ba7cdf294f81360ad20aa1b0d351dc40434977175cbe9c8a1166f66f21254334f3fd0ee7efdee2e75f9ce7fbe73ee2a7973fe7b3470f98cf86a8469d68aa4550ab191fa1ede6a6594e92a4d65758c5e30565525eb70c02ab301d1c8f6df2ae7bc336e6e5eb937096a782f9f9798a22b7f31ecda0a6e709fc28a036dda0b17d9670db0c493de041bfcb8547f3bc7feb263ffae202fff593737cefe3737c78e32eb7573baca739a990282128341485f1a9a93c47e7b9716128055a5114b9e93a4ae340290a655c594a2385534b46690a8c9c9a926bbbdc63b382550c824df0243e763ce77e8320c972d67a3df24231db6ad10c4350a6517e31b97e7e68ebd3171aa416f8c2239401be10a00a6b0583c4436a0f99eb3eb91aa0748ad219852a6c5028ab381d3db55064aa4f5c2c236b6d0ebc52e7e02bb34ccd46685120a440159a2256f4da29f7efaeb2b0d8637afb5e0e9f3849d08810be200c428a24a7bfda456682e97a839a6f32e94b69a75c8cf8536948b29485a54506718c90dea8757f2e8c8b86c6c9bc151e5bd9d53c00f89e67e6dcb9aea3b5dc8cc281422bd67b3dd6077dc23064f7b66d78da31251b94cdf34057f22711f8dac357e015200bab380b058536b3179c3fb50ca3eb31656a83d266a4d80c8b68949dac9fc158288440490fedfb28cf27939221b09ea5dc5a5ee2c31b57f9d1679ff083f31ff1d34b5ff0e1bd9bdc1e74e9873ea2d5249c6ae10581199c9012cff71052308c63daeb1d90c68b64b0755d9779df50ab23a537f67ea9328d103f094298066f53e5e1a291764028d5cc3f7c84768da8364aa90c4213d67da2a91ad14c1d7fb64e1a4956488df5397f8f5f5dbfce0fce9de7fb1f7fcc4fcf9de7c3abd7b9f27081f94e9f7656d0579a586b52adc88b026dfd80ae3194c234ea455e50e439456104becc7ee9dac2e4aa24d266d41a29b1e75266d54fb4a1426f98b0daeda1b5666e6a8a5ae0832af0ec3cf1a74be5e9de7a1698f21939f690049e8727042a4bc9d2d8b4385692a50c86146248a606642a21571985ca513a47e9cc4cb7d0a62b53a88461be4211acb06dbfe4c8a96decd8d32008a55966a915bad0a4c382e5c51ef7eeb751b2c1b1d3afb16bff1e6428f07ca310bbed1eddf51ebe124c4575224fda2ea66182d21f03144ad18f63563b6d52a54c2ff32918fef17016a7b66db091a1117354464dedb514c60203a7fbac0562dd1b48c17aaf4b6f38a4518bd831358d28cc80c6885547d854109f0aa39804c6ca2c7d71761ea07b4f089359615584e9ae995f1a3ba82264390754594bd1855c0a3229c93d9fd4f749a4472c243da559ec0fb9bfd6e6dae223cedfb9cdfb57aff0b3cf3fe3079f7ecc0fcf7fca6f6f5de3eafa320b794c1c0678532dc2568bb0de300ad3f3cc9239ab34a59464694aa7dd2e093f49b3df359eba868464d01db2bcbc4a9e296b7dda79af368019ad471478a1206a0644d321b5d9087f3a20ab09d648b8bebec4a7f7eff0cb2b97f8c1a79ff25f3efa941f9cff8c5f5cbdca47b7ef706d6999478321bd42910a49217d84f491c2f843b5365d77944215a6c761e6f402ce31a1cda065e9d5dc8217ab3cfa5c8ad3c56107e13a8301ebbd3e5248b6cfcc107a1e5a1b0b991748e34561b26807f984595a2b05e4794696a5584f9cf1d1b7b669fc28266740a686e42aa6502985ca28746ea7736464aacf305b26150bccee49397e763bfb8e4c13d5059e67d6656a55a052cdea729f1b371679b49a70e0d8495e7dfd0c53732d824013f81e795ab0fc68995ebb4bcd0f986a34f08534c2ad4d05cbca6a060d0c9284f6a04f6ead1f6d4b57e9383d075cece32aadaa385d9741b8cab7964d51a8d2a2104253a8825c6bd6bb5dd22c63bad9645bab0545612c0113016ce60a78024c6eaadf187785b3040b29c88102492e4cc88420d1106b63990c94a2af14bd42d1cb15ddbca09d67ac26092b49c24a1cf3683064a1dfe741b7cbddf536b7d756b9b6b8c4d5c5252ececf73eece5d3ebc79930f6edce097172ef2fd0f3ee07f7aef3dfef36fdfe3bf7efc113ffafc337e7ee522e7e7ef732feed10d247aba85373345343345add1200c43e3e21066d0cc0893299e101aad0afafd7ea5ac4fc2c6fa7b325c13f21220305a4a0ad657bbacaf75c97365ca28adbd2eac3f526214a85408512044811f688208ead321aded0d5a3ba708b6d5481b1e9d50703f8b39bfb8c0af6edee48717bee0fbe73ee51f3ffa901f7cfa09bfbc74898f6edee2cac212f3bd019d5c136b498687923ec2f3d1605c050a3cd3ae9b9e84d0765299314a1ccb1b7ede9a472795dae4f524ca78a45953bedeebd1e9f5a845113be766f1a5340330931ffece3132a24c23a791525028459e17683df2accbed7b04cd59850c8720630a6272120a720a721419991a12e7cb0cf37b34e7ba1c3bdde0e8abd3d4a7408bd42402a84cd3ef66dcbbb3c2ed7b2b88709ad7de7a873d07f712d424522a3c4f92e79af66a876c98d0ac37996ab490c62432d9d7c6f7e2ba5d0acd30cfe8c631b96d210d36afd82f0bb25c72a6370c4615aa20c933dadd0e058a9999695af55ae9fb545414a785b6cf1e07d7b93366b06926949d6ea27c8f440a0652d256052b49cca37e9f07bd1eb756d7b8bcf0880bf30b7cf6f021e7efdfe7e33bb7f9f0e60ddebf7e8df7ae5de5d7572ef1b30b9ff393cfcff3e32f3ee347e7cff3c3f39f99eee1a79ff2fd4f3ee59fce9fe79f3e3fcff7ce7dc2f7ce7dcc3f9dff949f5efa829f5fbec8fbb7aef3c9833b9c9f7fc8f5f61af3594cd797e8e929a26d738433d3048d0641bd860c028427cd04ec4a0779548576468252c4c3788c064fc6e369f8a5a16443b36fc3da6a9b7890a29424aad54dedb9de8c19b9713d3c33d719375f54213c851f496aad80da4c44345727dade22d8d142cfd4e9788afbfd2ed73bab5cefadf1c9c23d7e74f973be77ee53be7fee3c3ffcec737e7df53a5fcc3fe26ea7cf4a569078014a06282df194c457025f986d6accf09addd0c4aebb2e8b55e9613d0d8f6e05a730b5f50aa4b999d49ee6393be6e6d83e3585af8d55a7ac6f7d2b65fde5c13668024311db2b302e7689569adcad08b2f490bb0f7a6cdbab696d4b089a3d64d445c9750ad9a6101d32d648d423627597686699e3679a1c3f3dc3f43681160908b3fb519e69e2a16661a1cbad3b2b0c729f83274e71f8e42b84cd00cfb7c3f540912ae2418cca0a1a518d288cccf491ca32aa421566b40f4da135fd24a61bc7683b37b15a21bf0b0821f0ec08bad69a344d51950d380a6090a6ac77bb0821989d9ea61604086156bf38c61356e9badf4fc724d63fe6a6c120283cc9d2a0cb27b7aff3abeb97f8d995cff9c9c573fcf8e2397efc85093fb9709e9f5c38cf8fbe38c78f2f9ee727973ee7a7973fe7e7d73ee717d7bfe0d7372ef2fedd2b7c70ef1a1f3fbcc5f9c57b7cb1fc804babf35ced2e7163b0ce9dacc7fdbccf4335603dd2b0678e07e980bb7197259532ac07886d33843be7f0b7cd106d9b21986ae2d522bcc0b74ad20aa21d0dab0aa5299db99652a294a2d7eb19dfeb53c1a9df494eb00dcd58780970c9b82485701e6b161e3c228973c0a3d1689af79cd27482e9a61a94dd41cb03023466823e42e18702af067ec3a3b1ad4173db147920e8899c68cf1c5ff9ebef30fdea413a2dc9d5fe32bfbd77839f5dbbc00f3efb847ffafc3cbfb87c998fefdee7f2d2320bbd21432dd1784869bac36eae2f9599b74f4ba549253a795dc246e666530cf39c76cff833b7cdccd00a43e39213a2f4cd3e1d9ef6bd6780ad173313c1b8e13c21edda02535fda4e53f3fe4fff87fff5bff7438df072bca0408629055df006487f80966db4bfc6f48e3eafbc56e7ec57f6b07db7871714082b005a09067dc5e2e2908b171f70f74187bd474ef2f637bfc9fea3fbf043909ec6033c7cba2b7dae7e7881b53b8fd8dd98e5f0ce3dd4bc005f1a4bae4a132d2449a1b8333fcf9d870fd9bf67370777ef311b0d389e2d3959d8c23f3b4c858dbafb6eb919480aa551c2a39f175cbd7b9fa576871dcd266f1c3f464d083c3419b0321cf0f1e52bf4938cb75f3dc5f13dbb11455eaeb232f1d9749e8141b4ad53ad2589f0f8e2e62d1eaeafd05609b797e7b9b1789f5bab0bdcefadf268d861351fd213194920484250cd00d10af1a6ebd4774c31776027b3fb77b0fdd02ef61c3bc8fe13873870f230874e1de5f06b4739fee6495e7de7354e7fe50ca7de798d536f9fe6b5af9ce12bdffa2ab5e926e72e5c6090a5d4a65b4ccdcd124d35f16b2132f0f002df5857761313a314ec5889c0b8374c89a022704a69f23c214ebb1c3ab2976f7cf31d8434031d4f1612ebfc1ec3861b25aab47f523db8e7425a7eb07c629f96830364829f7defd75cfcfc0645eeb16bd70e8a222969e15e77f4a836982e464b91f27da5cc341cdf9744514092260c922185d4fcdd7ffb777ce3cfdee6e8c957d87d6817f5b926b219b29ec42cb4d778d869737775857b4bcb2c76baacf6072c773adc5a7c447bd863d7ec1c675f39412430d69eab9fa76cc8377ba77acff5aab4b5d00ae9b3d21df0e9a5abacf5fa9c3e7c98d307f6e1e9c258788f9bf2b4697d6d94f3b13c6d7cfc78688d369361c881d5e190db2b4b0cbc94636f9ce0f8e923961715defff9fff8bff8f7614d13d4155143536b6604b521f5564a6b26a73e9d30b74b71fc7493574e4fb37d4f6814a65080401590e582a5e584abd797b87af3117e631b6f7ff35b1c3b759c462bc4f30b33bf4978784ab2fa70950bef9d63b8d0e6e0dc2ef66fdb4964972d8d319290282119a40957efdc66b9bdceb18307d9bb6d3b9e556f1b683d79fd9418539a956e829092426b0ae111178a6bf7eeb3b8b2cacea929de3a719c9a307317532159eaf73977f90a85167cedcc19f64d4f2394117cd3a09af8cd0a97a785f5f7288dd28254785cba7d975b2b8f481b92e97ddb68ee9e66f6d00e76bfb28f03a78e70ecec714ebd7d86d3ef9ce1d5b74f71f6ddb3bcfed5b39c7df70cafbf7b86b35f3dc399af9ce6f4dba739fdf6295e7deb3427df3cc989b74e70fccde31c7fe338af9c39c6d1d34738fcea610ebf7a90c3a70eb37fef7ebeff4fffc48d5bb7f1c3889dbb76e14721520adb43750ac1d0cf757b46c1b818b455725a8f94a6d690e729693660e7ee59befdc7df4478ce8b54159449a11929a4d10fb185d4d83ade54083747a9dc5c3037dd53fbd7235ecff8fefff453eede9a27f0eb6cdf3e4791c7768f00a708abdfb9bc8cd270e9183e716d811dd1956651499c260cd3983dfb77f037fffc4fd97f6817874e1fe4f86b473971e6387b8fec6766cf1cc174833e392bf180a55e87bbcb8bdc7e344f3b1990a99cddd333bcfeca716a52e06b37c5ccd2e309347198a4ddd8b5fda931864f263dee2faf73eef25572a579f3f8718eedda815039dafa0fb7c2787d8d3d1ac358fa9b55bf4549ebca37a3b11141ae612d8eb9bdf488ae4c38fac62b9c3c73ac9cd72bbd28a13997b1fba0e4e0718f23af069c7cbdcec9376a9c7ca3c6a937ea9c79679a574e4f33b7cb477866441dccc4db2456743b1977eeae72fde622a90e397ef6758e9c3c46ad151a0b53b809b912956986ed3efd75b3e6bc596b94eb3c27e12a31c973babd1e9eef518b6ad60a746ba4ddcbb61bf4386a3d2b6cb7016bb67b42e0db1573d24df37073bc84204e12d234a516844c359b76bea351924258f67f5cad6f02235a26a8a200ad0982808282dd07f7f2afff57ffc0bff9dffc03fff0effe8ebffbb7ff9c7ff16ffe9abffe577fca1f7ff79b7ceb2fdee11b7ff60eeffed11bbcf5ad33bcf18dd39cfeca715e39738023afeee5e0f1ddec3dba83dd07e7d87570961dfb67d9b6779ae99d0d1adb6ad46643a29980682a249c0bb87cf50a1f7f7a0ee9f9cccece12469169102a9b59383b12a31f37944154b6fbc38dee5bcbdef7033ce9d1edf448d3d46e57e6ba8da31900236cac6b271026381e1abfff34187bbffac9e4f75a8090ac2d75595d6e93678a300c51aa308d49f9e256e98e51a73221dd8cde4a214017289d134601b55a4410047cf2c947cc3f98279c0e98dd5de7e0c99d9cfaea31bef9d7eff0dd7ffd27fcab7ffb5dfee1dffd4bfed9fffcbb7cf56fbfc5ce3387103b1a0c7d45e1baa26ece66998f1743e9ff14950e9f35160a2159edf7e9c509f5a8c6dcd434be5da66beaa41a9e849723e78e174c1d1b39c5aa4f4f4a2be38a2c331b76387e90c2cff0a29ce68c626e27ecdc2b3870b4c6c163350e1e0b39702c62f78190e96d123f749b1c08b4f6c833e8f70a1e3e6873f3d602dd58b3f7c8314ebd7196d96dd30481c0f75cc59b09b5455a3068f748fb43422169861181e76fe855bb022934499e91a429a1ef1306019ed83897d3e0c50949b54a4a6133b2e24be3d7945aa30bb36bb8f940a1b5a2d3ed92c5098d30a2194566a583d63891373afe6998621cee0bb39a037c4f82d0d49a1167de3cc91bef9ce0d53387387a622f070eef60e7be196676d668cc05d4a725610bbc06c84821c31c11e410e6a39d7e821cbcdc6c83466676fa11ee6f01521077327efab35f321824349b2d5aad29ebb8c74ac648796aab4b1c1dab01816d82460a530833b9ddf37ca40c18f462fadd01e8511d5b1b7d4494f158cb785cec8f0d1565ba1944a9a85de28f7bd90c02ad2eb7e9ae0f290a4d540fc9f3d4edf33ca6809f1428cb6146b99d60286546e41b8d065208d657d779ef37bfb5b4cfc1cf204c68cc0ab6ef6b70ecd42edef9c609befd17eff0d77fff27fcddfff25ff0d7fffa6f98da3943ae0a53244b0b6deb0e215e8a0469d38528c7bd94d2c479c152a74b9ce7b49a4da65bad9137b5246d49757355a1c908e3755e8553da5bfa589f02c2caac6957cc628252ce6dee8c0b511648afc00f1551031a53303de73135e7d39af5a94f49bcd0f8750cd74b9492f4bb190b0b1dae5d5f60ad9db26bdf41cebef516fb0eed23aa0704be40baae1a025d68f224a5b3ba4e3e486804218d30c2db84691ca90aade90f87a4694abd56a71604660309d7ad2b8bf3725015760761fda79e14849e8fd09a2ccbc9333371582bb3d6bed3ee90a719d38d068da866fd243626eb76abb0c4b3052bb866fa8a89b03fec822c9021485f21bc022d7313448e26b37f7334a9bd2ecc3e909e4004121108842fed5efe6e89a673d5e9d2d7f4decfdfe7b3735f1085355aad29b390c12d232d1b303712699493a364a948adda2aebda2a1407293d3ccfa7d3e9b1bcbc62e68f562b428f54e758b0f18d1a5297927b67fcfec61d850c4ae5554d72ac37537d52de04098f1696e9f7134010463e7991966bdbab296d15cafa1dbbef4a81116434cd7a8d2808180e627ef2e35f70f7d63d08021405b9b6f5ed1925ead70a5adb7d761d9ee3d5b78ff1dadbafd298a9a325846168a741997aa0d233d80c8e960eba429baaa2128cac4c77bfc8733ac321f75797c88562c7f66db41a0dbba7c246b89ed933638ca04f09eb771d71a831ee94322b23cd5a11b36d2298f2485568335b5f9b083c4f203d81f4253290f8a1871ff825bb15b9244d61d0d73c5aec73f3d622f71fae3335b793575f3bcd2bc78fd26cd60843cf9e8f62f3666b258f537a6b1d48329a4144cdf78d12ac9456541497528afe70409aa634a31ab530da60957e5998ac384f4842df8e8817b9d99390510b3d18c650689a4148e879e5124531d625adc63b12ebc7063b91d12830d3e59702b3740e6d14a067045ed9292c8aca7941029b4f37826b072f840d762dbbb63b070969269a4be921238f85fb0bfce8073fa6dbee51af3508a290422b5475ed76e9b71b05e114666981baeb92a41518bf96277d86c384e5a515b3dd59d9383a7a8c53c7dc706ad1580146ff542db85227950d8ea34715d538470ab49ad9ca6f615b413bd2f1e0de3c5992e3490f3ff0ca39ce653d6c12aa99aadeab0ab1ad415b851a4f0aa69a0df24c73f7ce02bffdcdc7200204215a4b14760728cfec362fa442f8395ea8915e4ea18c051c8441a930b530d557a6b885a536461ffbb7fa6ea93c2b169f5b80364c53963b6d3c29d9bd7d3bb5c037abcf6cf91c8c6c5813fd7701a76b2afc2bdcafb22e5ce1cd73190f73b432fe46339d45a2b4f1579a157866f2a952c22a4c41bfa7595c1a72e7fe3a0f17bbc8da14c74f9de69513c7989a6ee27b56f956a60709dbad1df6860cda3d44a169861191e799d962954103076d774a1ec6314551508b2202bb23f5a4427be9106602bd7602044804816ffcafbab0ab802c3db5d6c4498a270475dfc7b73e10e9d69d4b69ab42d84d9c9f21ffa51230f516f81e9e5dd36e96b91a1b4b63069ccc6a0f8c22b44475cb15cbadcc4a1118cf8b28eb4c223c0f0ac1af7ffe6beedfb98fd6827abd6eebcad499511e56816c12a7c0c9fd8829c10d1489cab55d85e179a469c1d2d22a4833dce7c46a7365fb38b80faa82f994918c156582311d8c644102776edd234d33a4279112cb1b133d8d31453eb2f0a82a742b98eeef9852b2b48aa2104f78e84272eee30bc46b19c2abe1890029ccea202d7cb4307b0698e95b1a5514e4796657e054f251b1cb05d5a5951b3146962dc2f807022d3dbafd01496c0c9f5d33d3f8ae9c1b20b6b8ef14ea9705936699865596a52c5572257bed946450a0725309ba1028252994441512ad24452ec832413cd474da05f30b3d6edc5ae2fe420f599be6f41b6f72eaec6b6cdb394710787876b0c46cd66a2a425845d35e5da7bdbc468064b6d5220a425365b65b6718c82800a535699e33180e0123b0bee797057b76217a3a3886d68c7381144669625751b877358242419a6608fb8eb40a4e5aa63416d0f367586bd3959242500b427ce1516405499c01a6611338611c5748a306c0aea42ab9db293b2bd0f642d83d2af124373ebfcefbbff988649813863582301c6b95ab61c33f4bc39130551a464179d790c5589a527a2805f3f38fcca6226ee35c9c9fc912e4b118a5389e47776ff2ef13509a618696a50215668392ce52ccc3070ba46946ad16a2b5f119eaca20a1a1ef286765187b66f2e9e831a2d7887704e0498f7a5843a58a9b57ef72fe834be079481122f04d101e42f8207dd3480a4951288adc6cc1263d3b2f5a6397dd5669f6786c28c3a65f9b8ad24292159ae5e555b23465b6d160cfec0c7eb97fbda5edc4771b319adffc65c2a531d201d6623637d10264b79dd16b670cfa39590aaaf028724191415108f20c9258d36d672c2fc73c78d8e6cedd1516967a78b5295e3dfb0667de7a839dbb7752ab47f88169694ddac2d867b652b234a7bdba4eafd325903ead5ac3588e55c61286381ad35a674a314c52a494d4a308dff7c7071d5e2221abccb9e1be5580be6f9476a10aebb3321e328520578ac2ee3423a4b44add945d6b8110d54d289e0156b7499707e9e15be5926785519a56311a85e9ac4527e363de31cb00e5af12c2f08581f418acf5f9c58f7fcdc37b4ba025cd66d37e53e996dbb0d5bfc92eb0c366b416c21c3520f078787f9e3c4ec135b99606cf41bd97045b562b4c46610af0e0e1ed47b457dba0729acd08446ef4545521da8c57f9dc3c1b35569b63d4084adbf3416beaf5069ef0e87786fcfc47bf24ef164649ba79a3765634c2330da086613c24cb32d3f08691d924c36e3a33e28d11bfead267f0ec1056012921e8a7198b2b2b1479c66cabc9742dc2d37a34c9fea97b5e4ff3cef3439b6e9ae1b5923f6d6fcebc61aa7d7d3d677d2d637d2da1d7cd89879a740869224886d0eb15acad253c7ad4e3defd75eedd5f63696540d49ce5ec5b6ff1ea99d36cdbb98da8111246017e604613b5f56998621a932389333aed2ec93025f0cc48b823d7a8b573d933a2394c12e234437a1e61109a9e654570f584e0bf18c62b65a4908d221708a230b24a539166c6b2945292e7055966b6c7cbb53989d399f64edc9f6d7e6605025366bb6f66e4fb04be4f512862bb8d9a535f38c174a90a61bf1d8f72a4f026e0a43bd79cffe073bef8ec0ac3414eadd6c4f33cb3e144f9ed48614edec3594ca522dfc8f02e7f659056e123595e5ea5df1b1a0550d6c3c6947ea7982c82dd64e2c19d8764b1591de72c4d2947168a6644870df9b7d68ca3cf480ac665c1d150d8787cdfa75eaf536405d72edfe4e227576d7eccc16965701082781093673912411486151e19bd665f36c6f50b11d846ea7974e398e5f63a995288d0235119b950647a3495c7bdbf951134599c2f13a6811a9dad640286a701b9de29585e89595e1ab0b4d86771a1cbfa6a42b79db3ba32647ebec3dd7b2bdcbcb5c8bd076bf4068a6dbbf671e6cd3779edf5b36cdfb5837a3322a805c8c04c721e098d2ba511da2cc9e877fae469462daa11866650852d88a5b4a6d7eb318c87d46a351af53a4250aeb0c116f0cb834da37255afd7f17d1fa50ae224466b8d2f2583c1807e1c832769f7ba0c9218258c33d9284f6d7c8c63f13f0d2a2266cf8b89029fd00f48d384d5d55563f158388559fe9e204f2978e662fc2136294f72e7ea1d7ef193dff0e8e10a8117518b1a9bd65125775bc2182ce3169643397a6b79c0b9315657d6585c5c369692796afe89dfb3e2848a361130d0dcbd75d79c2323218cfc09fe34af8d2c6fcb50eebe35a1f5042f9774aa285a9ba27dae6934ea6805ebab5d7efcc39f316c27e6e85e5d35269c9f09d224b193220ccfa28c21b0d5c0cb8bc89629bfa0c0cccf5cedf719aa9c9b8b0bfcf2dc273ceab4519ee7f6177b4a0dfde5d678596755fa632de28abe911935da7dcde272ccfc4287070fd679f8b0cd8307ebdcb96b94e5ad3b4b2caef4d15e9d7d475ee1ec3bef70ece4099a334d6a8d88200a11fe88f94d5d9b04ed1d74a18907437add1e2acb69d46a84d66a63135208db051ec431719a52abd5cc2ecf2edee7afcf2de15a95aa606bd314822d571485844188529a24cdccd9ee4292a419c334434b8fb56e977e129b1366849926a461cbaeea93619845db9dd923e911781e799e331c0e27945fc59a19bf3d712d2c7b946fdb07926250f0d31ffe9ceb976f930e73a65a5313f569f0aca531d91c77a938d70c96d6beef23805eb7cfc307f3c627e794a5a3e3e8e3df294cbead092604e0315c1b32ffe021799ee187be51569af2c456b0f5be55c0fc154eb719e1319fd939a0a5f2728d8ea1089e27a9d71b74bb3d3efde8732e9ebb0ae1a86a473c60becfe20c7265668178be5195daa6330197ce73c3c69b168ac54e874e3c2468d5594efafcf2d2e7fceac27912cfecc76ab8e049a8f2e89705137f55fe718341f63e809cdeb917af31433f93ac753296d6621e3c5ae7f68315ee3c5861717540aa42b6ef3dc4a937dee2d45b6fb2e7f001c2a93ac2f7f0c30019586bc0329510e6a88a325d0daa500c7a7d069d2e225734a288d0f3d8585d06da9e27324c13525510d66a8441505a22266a2780964b5e18b65246fadf329ef565a0898280d0f7cd92c642916b2824a44a9163563e7487316bedae31e69d7f5153aec0786ed8f9a9a1e713783e796e368b466b33c863077a8ca763dc42710239623b9b13212aca5c8027b9fec5353efff422cb0bab8441441445137eccd1af327eab94ab6112a6511afdde006de66a0a212972c5c3fb0b767a948129d6b8c55a65eeeaef2f052eef68ccf9be82d5e575d6d7bba4594ebd51232b3233cd6893f24de6af5a86926ed5baaa284c8db1d64bc529cc6069abd9c4131eedb52ebffcd96f21c158e7766180c987f169667186ca8cd28c82d02e5b2e631fcfdbd8d5b3a12ca710f4b394fb4bcb74e23e474f1ce5c86bc75988d779ffea452edcbd43ee393fffc6fa74714ddefb32a14b979a49db19526500e4be2347d87de808b3bbf7519bdd41d09a25f7eb64844453dbd87be438afbef136afbffb355e39f31a3bf6ef2168d5096a21a1ddc9666cb1bdd12ed6b532e2029d6b7a9d3e834e0f5fc374cdecd6fe387264594e3f1e926b4dbd5e27b087681954466237a9f41785e179539923cfac26f43ca2204029cd302b48b526d38a38cfc895d9d3b2374c585c5e0361a7476913e1c8a1fc7c10b6b1083c8f407a14696eb65153aa54d08e289b29ad92012b4c5d8eb060067f16ef2ef0d31ffd92470f56d0b9a0516f8e2c2c4be3ad4a31fe561536013db2e63783b656be9b0d70ffee03746206834ac1722f0b7bafc2e05f2a5cdedc5f654e077e70ff119dce00a5a1d56a9656c9640927f33779ed60eac77e6f1bb9b17785ad677bdff73d5aad0628c1a71f7ecee54f6e4050195c72da5819f7982e34be30ee1dd76b332c61de2f79e47961ebd8fcd4b4fb7d1eac2c91e88c53678ef32ffefebbec3bbe9f8e8af9d5e79fb2b0be6edc585bf0c408231e1831eccb87330d4a19b17c891e297539b76b07bb0f1e60ffb1a3ec7be515f61c3dc6dec347397cf2554ebff51667de7987e3675f63d7a1fd44530d64e813d443a27a8417d849b4aebcb6c26d5236318d2e343ad70c3a03869d019e9634a33a81f447bb8c6fa08320cb73e23841694dad16e1dbdddd8dc6b76955bb392f05b6c26d2f4c6b27aa665bb6c0f7a947211acd304dc8ec087f9ca6e4b902e19315b0b2b66ef6e163fc08e2e7851326e1d624033acb890766b92118ffe0e32861d2af309da3b916e0f9a4bd21bffdd9075cfafc3addf6809999598220a89c353fa9189d65eb1a96ad99fa71ca725c508d135ee2f1e0fe02716f68477f37c659c55671bf34d8fc9974ece8740ab76edea3df4ff0fc905abd5e118551992695d0e4b583ab63f75b54e271df685b5d423805ae89c2005528d657dafce227bf81d4e45757c7150a880731bad0045e600661e5469a6d5e7bcf0e6d47ce3b71cc4aaf477dbac1a1a37b79ebebaff1cfff9bbf249aad717b699e2b776f9169657cde8f49dbf00f8fe5b11781a36bc94786f886afb54bcff87e65ad59676a769aed7b76b3fbd001f61c3ec8c1e3af70f4d4490ebe728ced7b77d1989dc6af85085fe2051e6118207de36f199350578e510e4c6195264f73ba6b5dd2414a2443ea61bd62356e84d290158a24c9105a100521413941dca2fcb9b1f25f0425c3daff8de234048dc2807abd86169a619290e6194873909552caec57283c56d6dbf4e284c276c9ddf72f22dc4678c0f3048127114a910e13b042558ed63f0d3f957525ec608bc7179f5ee0dcc75fb038bf4a18d669341a7640c1e47963cecdb3d13bd530fe5ef95685064ea8056ee4dcb6e4d2c3933e4b4bab3c9a5f021998dad842d96069f3783ce9f953a0ec3809103e692fe5eeed070c0709b5ba7157b9b26f95da93f339aae7d18dca6f77cb1e532284407ad26c6483e0b3735f70ebd21d7033115c440afabd015a6942ab349fc48f5565fdb4d05a5114054a487224abbd3efd3c6776e736f61cd8497dcae70fffe46b9c3c7b9cf56cc085db3778b8ba42210dff3e1e8eb947c1e5b11a9e0f76c6408547479c6cea43d8f464580fa9b71a3467a698ddbe8db99d3bd9be770f73bb77d2989b216c3608eb11612d24aad708c200295d025507fd440558ff9b391151d3ef0c587db48a8a73a66a4d6a61041553dec1b9d794806196d11f0cf03d8f6654c3f75f8428cf0e2bcee6b70050845148add14003496694a202f23c2ff3a6856469adcd6aa763cf33c292dfaea2d9a0601e1f465bad99151eb528346b77734da7dd353995ded828fa93e1e23756e6c2ed797ef6c35f73fbfa03b258313bbdcdf848ddca9652351a6635bdbead14a67ddfbde346bc2bd9d39896a8549c1586f77db37147af3be4e2c52b20ed2c8b4d04e4c50465846789430bb374757161998585472469426baa45a18a4d159cc3641a5be57fac61d942b16901daf62ca494b49a4d34f0e8e122fff89fbe4fdc4dc1b38b143450683add1e284de8fb447e688d9a512fa20a21ac3096feeea783b207321652d28e636e2f2c3004f61c3ec0cebd3b809c999d33fcf9dffe39ad9d33dc599ee7dcf56b245a9b53192608385e7ec32ba36beb6a322c390a4f812add4b256997936bdb4dcfb55973ae4bc3c7a42b3ddfac2d8f6a11b57a9dc65483c674935ab341588b8cef320a08229f3034e77e8fc3566e79392aa0d676ed69a1e977fa74563ac84c335d6b10d9953562c26fa36d6b5e684d6f38244953ea4148a31699258955e5fc94047a1698a8474b289dc96ef2a7093c8f66ad8e108238cdec51a9e61c11610faa52023afd3eabdd3eca93f64467ed34afa5d6b3045356292552401404d4a39a698cba7d50233a6a57eb158c5f3a9f8330273e8a80bc97f1c12f3ee6eac55bac2eb6996a4e13851194cad228bd516c266ca5305d8ad5378d103ac5679f96a3c6e67db78433088c6559e49acb97af9993352b538fc6ebfd494c3049f3c9fb56802a4f36c0d59bf56b197706dcbe7187e5e5650a9d536fd6c995dd0be0a9e1a47c3c75931f2b0fcecf56be32a2aaa19d793f08433ccf2789733e7aff1c973fbf06a1fd44627a7add2e42691a41483d0cf126695055deae8e9e11da19115ab33a18f0706d0d429fa3afbec2f46c0b6d77d03af3ee594ebef92a6be980ab0feed3cb73b4f04af5b1594331821ed1ed096e9b2db181ece686b6bbb397f568afcda5792eb5d068a9cdc2010f3c4fe27912e9993997e57265c168a9e318039a9437cbba495ca072b3e63ce90f0991ec6c4d5393663b38f79db6e6301821ca8a9cfe7048a10a6a51383619f7cb40d9a295be4c6b299566ba696d7c2168842142c3304ec8f202a5146992a00bf35b6bcd304d59ebb4c994b2d6a851541b98f31960f267368c0dfc004f780c7a038a343736b1b6cab3c270654db974adf237122700c9d5f3d7f8f4a32f585a58c7f7221a8d264a15655d57d9b7dab3a842336af046a80aa11ddf97b6d28d161d732708bb50c0acba32a389f7eecdb3bebc6e26f0db3a793e4cf2ed088f8db2ac2b93d1b2f791c28dabb718f406484fe0871eaa6c5c4d59aa7f9f16e5fbd66f590d23fabac11b4746d36036ea66dee6da6a97efff977f8278b430284b1286fd01148a4614520b033cb744d5e5d5fe7ed63c3b38e3420841aa342bdd3eed419fa9b9298ebd72083f301b88280aea334dde7cf74d542859e8adb1d85e47f8c168de6655261def7f89706576d22e85394f4968205715bd603c9b1be0f8d885678111501394d6680d79a6e8aef788bb43ea32607b6b9a508ca61b096d2a7e94962049337ac3215a6ba6a75ad4c2107fec2cec4df9ff3961e374ca52997b4661da44acacfbc054bd86272583e1903849514a915782d29a5c2916969688d3d49e216efc76cf2ff48e891442983d35a320a4d3e930ec0f0cc34e30fda4d0192933ab34d01284c7d2dd457ef6c35f73ebfa3df25c3337bbcd5648398eb8119b08b48b7ffcdea4d2b0bd8f49c62ab7bb3359949e240c438a42b3b2b2c6cd5b77eccc8027094ff59953905bdd7347da3a06b47f27bba423cd647f7b08ed91aca6dcb9711f65f7cfd4d8234d9c1fd195ef31f5ed94c264994afad9bd06645977155a3bb78659af89d28aa85623f043fabd988f3e38c7c58faf18a50924839841a70f79413db4bb70d9cc9559b5f976cab9a49eebc56c12aa301d18d3f00dd29407cb2bacf73becdab38d63c70e203cb30397399b4871eab513ccee9ea39bc5dc7a709fc236f8428c5bfe55fa8cca0f387e9a08cf0397aea1abdd67427a0825c8d31c7b42395aebcd95e6cb80461806d28234c969afb5490709ad5a9386755a8b0a11cc1e99865fb58634cbe90fcc461dad469328302d23ae72cdd78fe7caa782e392513c8e80556bca548659693e5daf1305214996334812b4f0109e8f92122da43962584a1eadac90e605d599469acd2cb2a787b0ebcf433f20f03c92614c9aa4e6d035cb6ca5d0956bd02b828feb96fb90987d322f7c7e95f5d52e3333db08a2d06cd9f594642dcb534aded893c99b361f9312379e9810822008c90b45bf1f73e5ca35fb60a45c37623351de8ccae69d11359c22af7e5f09b6f7015817810ff82c3e5866e1fe1245aea9d76b6645d098527b1a6c966783aa62a0eaf7b57e6b57d7933b89b55ad30825e9b607fce8fb3f43f78114ba9d01fdce009d2b9a51846fb7dd73e9b8bfc64c70ff365063d3e0e0f8404b492f4eb8bfbccc208b39faca0176ed99b3e7bebbee6fc1f65db3ecdebf9bdc83072b8bf4e22185d66617b12d1a928de26eeb67b30c3d234a5ad8c62a9012096459865620ec49b05f9ad20410c2ccb71b0e62da2b6d54a668355a045ec5b15f0ee4bbd6c50cf3674a314c538410c6ff224df70caabec10d147c4e4cc633529755e63575a6a94721f55a8d242f180c13eb69350a534b49a1400949bbd7a3db1fd87987a3da7cde7a758c53ee74e4f9644936a6f095a9ddb27b0bceb782b5a2ec1c4324573fbfc6c71f7cc6f2629b28a8d16c36edb1b286ce4f95cf0ae95c122398781c099da097b557512eee9983e7f986ae4a70fdda2d06fde1534846f5d9e3de1dbf6facc4c90111fb8e56a3933135d69f29b87ffb21bdee10952b6ab5c8cc9cd0868f9d3aae96ef7961ea9cca60e0a841b40c396a87b4260c4366a6672832f8f883cfb8fce94dc8a0bdd6633818223434a29aa5b5444a73363a6376c366341b47f50d6dd3d6762c22558a95c180a56e1bbf1170eacc7182c82bf717052854861f0976eddd810824bde18024331b3753e1a352597ec9707476f5268530475e6828b2dcccb1b679fb7295a69d8ad36bf7585b59835cd3ac9949eae36418319a767b6826298338464a8f4614e14b811015337cecfb17455580265966b42a400881d09a5a18d16836190c53da9d1e68415e985d8f0a65ba1f51b349bb37e0fec379a3506dd7df39469e950db4a5a710024f481a7533cf351924f47a03b3835225d2924e5a8f4c5be9d94d1d3ce66fcdf3e37ffa39b76edca3c834d3d333f6b0bcc9d1f2c7a3daa31d57980e2616c3f85b977af2534f4a6a614491296eddb8c7dddbf7119e393e79b35a1ac1a5b1755a0693cfb78aaf0247cb1c6e5ebf459e16f87e801f04e5fa64133669109e20f85585e8bac3b69f68ab6f74f859796f2436a5152e05cc4ccf107a218fe697f90fffe37f21e96a56973b0c07433ccf636a6acab0839448cf2bd9c3789d9f0eae34ae2614e6d8ed242fe86739f36babacf47becdcb79363c70f1a5fa655fc9ee7010ae96b66e65a0809799ed91e997305540bf7f251cac6049c8c7b56ced09a3ccbd14a21dc40d0e4472f0b4298e1fb22cb19f68676523bd403b325d5a87a4cab2984dd624d080aade8c74386494a1486d4223347d3ad6078f27cae1740c5a753c22a394f984d85eb4148bdd624539a6e6f80ca152a2fc855419a67b466a63879e634439573777e9eb4305d74ad475b4f3d0fb4b07e2e29a94775422f208b33569756411aa7757599a9913f63c797cca73df241c66f7ffe01173ebbc6da4a87a9568b300c374c2fda0c4ef8ab422e6ccb3c79cfbd3b529ca338cc336bbdbb5e97cda6b07eb15aad4e9a14ac2e77b874f19a9daf398a6f736c75ff71780ae12c95b564b03ae0ee9dfb64794ebdd1182939b0fc6ce21ba7c19360bed18cb6f8d338c5e894a971ac57e9ab314ad4f50ed01acff368d69b24838cdffee643bef8f41ac920a5480b6a618dd9d9d9728eb4abe9eaffcf036730149ecf10c1fd9555ba45cade43fbd8bd773bc237a70b6015b594e0fb827a3d4468852a14ba706e319793a7a19dabbb6a780a6cc142c23ef384c493c6219c6779b9c043608ebef952a0b59dd49e140cbb03f26142dd8fcc193f8fd11a0a48f382fe6088d28a7aad462d084a0b8d6723cd33a3cae8d5df5a6ba490f8402df0a9d56a682919c431699682805c652445ccecce598e9f3e8ef205f3abcb74ece61dc2ae077e11688c62ac0521910c50594167ad6be5451b1f97369bf7961a0901be598f8c27b974fe0a1f7f709e8507cb447e6476cbd14519ffd6b5b31193ecba75f98c295aea10ab00cad1781b4639d0446184c4a7c825572fdf40e77a6c1bbc67c9e70b436068a9e1e1ad79969756c85541add5b09bb65415a4fd3ba130abca6e326c80bb6f0ba9edbdcdca6cb2268cbd670ff96b365af822607da5cb4f7ef833fadd21455a500f42669bd3c6a75914a8c2b9255e8caa4a69a3f083909538e6417b9d2c94ec3db29fd674dd1cbde18d06b7841466aa631421005d28b4528fe7a03163a9fafbc9dcf7ac30c7bd981e629e6528651b78f925294dd745d185268b533a6b1dd241c254bd413d0c47b23cf6beb1e6b486c40d0269989e9aa216ba096786398cc559de79a9c4c2c5a675e96735d746594921887c9f7a2da2d0d01f0c889304e109f2222349071c3cb29783af1c249c69f0a8b3caa3f61a85b0b155fc8fcf8a9235acd26c86112acde9b9b99a98b98eee080c4368e7c714207c56eeadf0931ffe923bb71e90a78ab939335a6efe6d2e9493a896605ccd6d2e7223c5609eba6b674599efccffa66a8dc5e1073e61580325b975e31e2b0b6b0811586b6cb3f49e971746032263a8de13b6ee32b87af13aedf51e4a806f77b27703344eb02795a289ff71a19294fb5b51a8c2d2a97acfddaffe35b324210843a6a767d00a3e78ef632e7f7e195f496a5ec454ad4180e97a6ead889e0d02b3c022931ef7d73b2c0d7b4473531c3e7104190990ca4e6134c22f8444783e516095a632d3f54485869bc13d733ac35c6f4ec7e741495b6d2d38a5c9f28c22374685106670e8a5c299fbce68c8d38c5ebb4b66cf0789c2d00efc54bf31d7da8eaca5856210c778c263a63545e8fb667f7461d725db11c3f2e3d2b9f3e218f9e7ec68beab0e21409bd1f3c097341a7510308863922c434a8952055a671c39ba9ffd87f630b7678e763ae0fef212a9dd42ce75f59f35b7630288a61e064cd56aa82ca7dbe9963b703b4bcc66da04294049f440f1e12f3ee1cac59b74d607cc4ccf1284a1f9740b2ba60a93f6e8efd36052c027af4770bfed5fdb0d0d7c1f95172ccdaf70f7e643845f1fa39e539c1b7d604f2acde6cfcb3cbabc393a224104249d94eb57eed0eb0df1027b381966577d01a60ec630cad7581d6e123643f5d924ed44a53e9c7ba48ae9e9696ab5068f1e2d73e1b38b8432a4e607d47cd37333715836a950f059a1b5c6f33d8494b48743ee2eafd05739dbf7eee4e0e17df6c80163a581132ad355f77cd3052eec4a22a350cd2b55c5582a52e7e67ae9b09c64092010f8761eeb701093d99367a5f8122d4d55988d3a9261c2b0db87aca01945447e50eed22e2a82ea985f594b338e53c220a459af13d8cc8363e02a9eb7aa37479549ab95e52a11eb426a36eb78bea03f1cd28b53a46f369f0d02d8b1639ab91d53ec3fb28f849c7b4b8fe8a7e9489e9ea7d2b53db6d8fe8d7c8f66ad86509af5957563696a8c6fb3da3517663e26dae3fc8717f8e0bd4f597eb44ee847d41b35d3c88d758b3762449371417d9a202bd3c4849586f2f926f14ca216864824699c73f9e20dd06e65d008a35c6ffc7e73389e7182b8c577250d859921ee4916ef2ff1e0ee02c930a3dea88f09b08b75b3e6a72cefe3c2167418bfae7e31ceafe6aff9adb5c2f77d9acd299412acb7bb0824b520c217d29c9a08a69751e1f16745996f29500256bb3d1eaeaea203c92b270fb367ef76d0b9d93104ac62d2e64b2d09fc10cf330b03b2c22c431dc9492521776bc32c072a2f6efc6092bfb6a6afbdaf017ba657203d3c24799a93a55999af97ae34b53d603dcf73f2b4a0b7d6a5bfde26c46c0717796693d9ca17a3914704b9d274fb7d86714cbd56a7d56c9981235bd8cd5b99cdee3d2f468c07a3eddcb430d3293486c9a61a75eaa1cf304d586cb7116184e741ade6d36c85345b354e9f398d5f8b78b8b4c45abb6327fb9b385dcbeeb055094a76d01aa5b43d225411f81e33ad1681f459595c815c238200844078fe6870404af03c16ee2df2ab9fbdcf9d9bf364a9626666ce3a1ff44418c788c1c6ff1a87b8713908ab582619d37deb9e6bf7ae8b4954dc1ff6bfc96ffdc01c8b22f1f9e2b32bac2fae968dd9645a2f1246f5ee1a1a3b5dcbfdb60327973ebfc2ead23abac04cd15285a302d87a129541af6a399f849216ee9b095a55ff4ec22d9ca8d6a090825aad86e7f9287b4ae9f4d414a1efa155515140a3afaa94303eb6c92efcf87b0e45a148f38276bfcffcca32f5568daf7df50da6e7ea4669e6b9d586562bdae17fad407a01424a7255183eb1f9aa1a2d0e93d74fc444d54e665c6881d466201aec2628d833c1b4c0c34c704f93bc5ceef1d29526d845fb79411e67f4d63b0cdb7d023c1a614468a71b1946725e34ebc04690e439ed5e9f384969d46b34ecea9b2731cd9707534942885261a214ad7a8d5614911505cbbd1ef83e9e67cebc96017891e4d88963cceed8ce4abbcde2ea6a79fc85908661aaf5b755a9aaf7b5d6e5d2d6c0f769359ad4fc88f6729b616f6856fa80613caba8903ec550f1ab9fbec7d54bb7585fe9d06a4e1186c168a395c72a4e97cb91bfb17a77ab7c576168e7bc289509f795e7e5bd8a25ea10040142782c3c5ce6c6b59be6f035a788b6cc443587d5609561e59ec06c3d67d277cf2ad6ba94c6c24de0fa959b0cfb0941101004bef5c3556606603685ae6669d3ec3d011bbe29e951ad075706f37c9c6ea69e023fc0b7dd71ad153333d3048199f2e38c954954a9f538b877b4d614c0b02878b0b2cc4a779dddfbb673fcf83e3c911be7a055c0607decc2f8d9936162a82ded71c354f8c5a6b3591e5f2e4ca3a7b5fda58d41e0098944a2736dbae78e1d263f7f5108dc06ad9a22c91874faa4838448fad4bca0f4fd181f2565e53bb2a44541a7dfa7280aa61a0d6a41806747dcb6c6e39e3d1b4a2bc63185f36d8e4de7d14cd5eb4cd522f22267b9d7a51020a50742a0241008b6efdcc6be83fb898b9c859515d2429923309cd0564efc73b6ca96c1a66d46f5cc26c4d38d268da04e7bb5cdfaea9a2d8032fe6bcc047b105c3a77954fde3fcfd2c20a811f9835cada6d2332529993a82a2e4b8c8df79f80aa422cbfab7c6feeb960ef61ac118199ac2fa5d9f93f1e265cf8fc32aa104869367cd93ce74c526f224c3eaf7ee37eda67c2fa84a560b0d8e7ee6d73be79bd51378a4738fb63444b93fd8d56d28ba09a4b6de73b56e1d272fa086bbc0004d2c7d3020fc174b3690763dc8cdccdf1a4bcbbf269bbd7422e04abc301b7171fd257435e397588b9b908a133fb4525bfcee25482b81fa39599f5e179be911fd7a3ab58ce4fcacf8bc1c4adb5e347335346626896e7a67b8e2dc54b579a80d1da4a932719fdf52e799c51f38d135a0a430997b8a8568030a74fae777b080153cd26a127915a19e565b9615c08b712f9e745253eadcbbc0aac296fd36f442133cd26058ae5ee3ab9d0f8be519ab9368eef99b9298ebf7a8cb011b1b0b2c46aaf67b6bf2af78b13a06c77dd0d40b9b4aaa154980221249e94f89ec774d32c49edad77597cb408454191e776572501da67fece237ef4fd9f72e7e63d8a5c31353585f4b0a3ac4fa29ba56f3955c8d16664093c29060757968d70969f81a858191acc5a7b69dc32599af3d9b92f585a5c47c8b074453aba6dc4064a6ef2dcfeaa2a74615d1bee9e96e0c1f50b3779f46899ac28684c35c98acc364f5641ea278ffe6e85b2bc9359ac400831363dcbe8f5f1b40c8fd8d8b43923ddf77c3c2da9fb0133cda659b26c7b4e4f5f83238c14a671c569ad49b5e0f6f20a0fd65708a703cebe75927a4302ae5b5e7e6d69660630e261822a400a1fdf330b340c1dc63dc3938dc49701817169083bb02601292479969324c997e7d3c4115509923837eb5dd3824668e768ba7726decfb5262d0a33a93d8ef13d8f56bd86d956405b461829901225e33c7be53f09ce13359ea6e96684becfeccc0c9ee7d11d0cc88b023ff0414292a6a015f5a91ac74f1ea139d360697d95c5b5356b695ac1727eb9322d1746183d1fcd1d939ec4936679693d0c897b431ede9f47e7cacc97d3e6fcf02ccef9f54f7fcb85731719f4137392a6dd76edf1a8e4a3b45c46f4ad50a2fada8bc1466f048691dd26c0f33c3cdf67384cb877e721d72fdf062f3416bab5d247caeed982ab5b2170053510c216caf40448e1c285cb0ce2844280f024852aec27a6ab6bc8346a009f158f5398585e707ace5862d6b265f4b1b056a4299b34bbb4fb3e526bea41c054a3599e55e5a6273d2b4a79b0b359b4d2f4b3823b4bcbb4e33efb0fefe6f889c378d28e4e0a931a36cfa5d2cc35499c8212f89e4f68770f2bd319fd7cae86e859205cd096e7194d312c32652c4d9b852f4569021485261ea6f4bb033ca055ab137a763b384bf411194ce5272aa7371c92e619b528a21e0648d4c6aeb9b00c64bf7d990ab3cc9bed2238e67002a5b551e0be27996935f17d8f384d18263151188086c16088d639d2d71c38b89bddfb76d0cb863c585926b5bb20b96e8d74cbb55cb56d5114535e2b18c24c608f82804618a1b29ce5c565c0436246fc842fb87cfe0aeffff27dd6973bd4a31ab57a1d36d9d66d232c4dcbc668fcfdf2eed6d9dd12a39a1cef294cd4b089db0a9cf03ca22822cf0bd2b8e08bf357a070cefbc7d3eda93099f818bf09d092fe529f9b37ef90e639413d20d3cea2776f59b78e06e33f1ccfd048396f1d9e098e3e3607862fc777d272dd4d4f7a08ad99aa37cce6d54aa155f5fcfa4db0c56d6c59a45da1a78dee63a9dbe3c17a9b2280d7df78953d7bb69b35fb76f604b8fa744a5440a148d3025d683c2989ec6ef2945532ee867016ee46b8779e918655d8de4a99be954b4f78a85c110fd3f2d597aa34cb4269bbe969a74767bd8d2f3c5a8d26be75e04f964d08e3334af282ce604056e4341b75b3f1f056ccf43c8cf654300e61a3142a9b11dba74691da69478d1a7ee033481206839866ad4991e62c2d2ea1550e45c2f69d33bcf6fa49bc66c09da505567a5df2429934ecb9d363f34e9f5024f79e9b76b463768e40fadcbf739f649020a51990cad6337ef45f7eccbd1bf7a1d0349b2d3cdf2b2db88d8ad332b289dcea8dcd854af3e47c3e1d5c24ce0530a148a455025a138411411050e4f0c5f94b3cbabb6026ba6f2a44cf01d730ba50de93a0e0da856b2c2dad9aae79cb6e385c36de36bc485636a1e7e3cab651818cbacc9643104290e519691a23a560fbdc1c8d5a0d65279253e1278752af8ddd1d87ab1f2d20579a7e96737b7191f96e9bd6dc14dff8c63b8435d3ebc24d7f73df6185470854aec8930c9426f2cdd845750dff56fc374285675f025cb9b0b14a39529a699296a9bd34a559164d1b7f669666f43a5d86fd21a1f4a987d168c301f7aaad74ad0df1e32ca73b1c902b45a369560f3d36835f82e21c5596bdb64d90910df34c0810ba60fbcc14d3532dd22c231ec4d4c3062ad5ac3c5a4615399012d625274fbfc2f4ce191eac2ff3607991cc4ee275cc612ca6a7c1282f4240cdf7999b9aa6ee87ccdf9b6775a98d28cc72c90b1f5ce2f38fbf60d04da8450dc230b2316cc58823261d2f6b85e16dc2cec27c1e96758d4e350d53aef1984cbaa391762905b55a9d3c532c2d2c73fee3cf40042837fd6fd3323d014e385ddac2f0d42883982df452cdb52b3718f413341044a1ad87b220b61ead32194be4e5614c49565d009bc894abb72ccb48d304df13ec989b230c3c33e23fa6f0c7b1f1ce08d53c68201382d561cc9da54774d221bb0feee2d4e9a3208a91c274823f46574996e6e4696a37c1b1c7735bea6dcda75554f8e7a9de7f325c1d3af6949811b33cc9cb249e565a9f08adaded6227b6e7694eafd3a74873223f22f4835117664c40cc1e755951304813fac3211a68d46a444130e6e3d81c8fabe2e7c138d3b97b4e793a6a0aad986b4db167fb0e8482413f260aeaa004ebab6bc4c9103c8596057b0fec66df9103b4d33ef71617186499edb654a6616c487333380bd1edde2e9969b4687811ab0bab2c3f5a030dfda5213ffec1cf68aff608bc907abd81a9fbc70dfe381363abe7c274a29f98c7274333dafd7d34126cfd5dcee2b4ff8c1fd730721806482445a639f7c9170c3a43a430fb429a88b5aba0a70c2e3f55aab85fd69789246b0f7970779e7e3f218aeac677fde26418c3b8d5b839aaef6c2c85c1a4d22cf21c0a452024d3ad06bee53b61fd91cf0bad35b9d20c8a82a5419ff9b525083467df38c9dc8e16e8cc58e9d26c1e8d9d4e34529c9234cd489304a10a2bebaefe6c1ab6319ac4c8b0d92cbc2804529b9d8e4229f08540284d91156623e297a934ad48a1b536e66c9cd2eff4d19932cb27fdc00cea4ccce857e5d2c982ee70c0603830e7f0d41ba63b3ff676152f935093a8b2e4281d27e4a0914ad10a03f6efdc45283cbaeb5d7c2fc4c3a7bdda66d01f802710be6676e70cafbefe2aa21e706f6981c5f69a194cb07b84ba806dfd370b86d10d9db19b210752b2636696e9b0417fadcfedab77a180dffeec233efcedc7a85c3135358de77c455b2a4487f106adfa5b33f2616e26ac06930cbcb18e9cafd229cdea97c2c56f05da284fb76cd61cc512862179aeb979fd0ed72fdf40cad1be0426a2cacc84c70683b1866a0379ccbb77afdfe7e1bd470c872953d3d356394fbc69e319d5d738aaf5bc59984c5b3b23e4491827efe8a7cd429ea4c84231db6cb177e74e532261767c1fe7af716c76cf416b4da134858661a1b8b7bcc2daa0cbf65dd37ce39befe047c20cf6188605e91a447badcdef244948e204a1158d5aade46da7145d0eccf56670859f0c4f46b5dceeb7b669096136458f7c9fc8f7115a93a52979f692952660e7340af2bc60d81d3268f709b4642aaa5193e6788b6a5568bb7a48294596e774fb7d9224a51e45346b353335a224c2a880eedb6721d2f3c3088e0bb65a11284201bb66e768453586bd3e4a43e847b4573a74d7bae079484f506b459c387d9ced7bb6b3d2eff060d5ad45179649b6b6003763189713a935d38d0673ad69bc42f0f0f63c9d8521dfff2fffc4f2e20a5158a35e3787c06d8c651c5b090f2e679b3faa609271370baefe8c7614c2581d23dfa0cd87fd5b2a4e61061d841046696605eb6b5d3efbf402450ea272205749c74de83606f7fcb1e59290c1b54bb7585beda211d4ea8dd21f385937a63cb67e1e43cf4d615fd5365e13f3a8675196cbb162e5b3895b963becdeb45986a7353b66a6d936356564d00ee43c53fe2a504a51140a2d3c0659c1bda52506d99053af1de5c4ab47cc3423a18d75a95d6fca5a9b659a9a344dc8d2142924b5c86c342ed0f67098111dbf148c116de3d885000229093d3388361c0cc872e3d678794a535376a954a6cc416abd21a196b4821a91e71bb377ac7add5c2f4d9c66747a7db224a5558b98aa4578a5655581f5058e31d24b4735fec9e0462d155268665a0d665b2dd224214d53a2a84e6fbdcfd2c23228e3d311be60ef813d1c3d7194419170677181d5bed925073799b7747e8fac92323795fb8291a35eab82562d62eff61d442260757e958f7ef9295f9cbb80c467666adaaefd36799f14720727ec1beeb9125b86da289c9398a4d5c6b0691c4ed94ca4e9148f1076504808fcc02cb9cb92828b9f5f656961d54c3f2a63adc4ee0476b3e05e19bd6dbed57ab453bb1014ed842b97ae311c24046164d6492b73d01698084ac1767f9e47d06d7cc2fe369e0a33a56a44793bc5caad5e72f72a699abf462116454191a50442b06b6e8e561421b5abc932b50d7045dbbc1c7699b4d624c0fcfa3af3ed3594af78e7ab67a94dd5ccc8bca80c8c8d11d9e6dbe62fcf537ccfa316457852b88378c678a59a8f319ed8a4617adcb3ad61d233a4b696261a4f9a83148582344eca6df45e9ad22c2b5183ca0b06dd3e717740e40534a3687cd30d0ba71cf242d1e9f759595b05343b666669d46a9beedc623edca8585e0cae7a2809580d5a1b2e2e95b5e53b2904cd7a8db9e969f22ca7dbed130535d238e3f6ad7be4c38c4299f3a99b7353bcf1ce59ea732d6e2dce73e3fe5dd2a2a0000a778aa56d401c362b5fc9d05a235441330c3976f000d36183c57b8ff8de7ffe3ebd769fd9e9396af586f9668b2ee0668c55bde72830b20ec6df79d65089b14c632c2d276793df0901d6daf4a4471499cd89efde9ee7c3f73f01e9a3957ea27159a28cd3e5cbe6c39553bbaea4e4e1ed7bdcbc76933ccd69346a14caecd08f9b5e5446395e9ee781f1fc3865e10c0ce35bd5154569bdbd15ba8e38d888bb303ec32c436519d38d3a87f6ee23945ea5c1b013b86d18a7f588161be337bb0c6652d2558aab0f1fb012f7d977741f6fbe751ac81025cf39056850f2a130472017ba204d5302df37f2ee3c4813df61ab6c5c2eb74035a326eb4fc028ce513fd22c8df585f1697a4290a529aac82d655f1684ed121490c61983ce807c98d2f043ea41581ea4866d99b5365666a1313ba00f0674fa7d7cdfa7556fe00bcf30873bddaf82cd14c08b61b222aa95a3ede465f3b77c431b3f4d18064cb75a20a0d3eb9933570ac1ed1bf719f63310015a086420397eea18874e1c663d1d70fde13dd6863d72ad504501b6486e85c56361ad324f0a7c29d8b76307db9a53ac2fac72fde2357c19d26c34adb0d90f26386944d349c566e04a2e4429ba9bc0e5d3fd9d4c67f334c7d31bdd73e919bfe7f86f17bd9482288a280a8887191f7ef00971a78f10fe96b9dc0a2e5e21aca5edfc6e9e6f76355292cf3efd8c7eb74f51e404a16f0656acf03bda6c55be67414929db8b30a1a2389da21c7b32eef0aabeadb5264962508a1dd3d3ecd9beddbc6d1b7ef39eac6cbb329991325a73bb5a675290f93e4bc321371717483dc5a9374eb163ff0e50b96973ac7bc0346693fc2c416296276619811f10056ecb482b08954f46dfbb07132f3c059cc29e0cf669194cbd99f1035f9aad09a510a4b17125e897e9d374992894261d660c7b43549251f743d335b78477cb938406ad050a4854417f1893a6b9d960c0f3919e576eee602a709c481b2be279e1fa426ce4942ac6d2339e17d084bec7ecf4149e14c496a81e3ef3f71768af75f182ba3d044bb17dd72cafbdf51a41abcedda5056e3f9a275505c233e57434541b1bda1265f50a738e8e508a9946835d33b3e86146da1fd2a835f0bcd058b913dd3b6c3d8c15d52aaf497634ef6d4593eadbd5af9e1e552553feb5ca525885e9ee5743e07b78d2234d72eedebacf950bd71041cd94614c101e073d5a6e58d6bf363bdcfb01783ef9ea804b5f5c6318a7083b8a6f964a0a638c4dc428cab24c3e797a5429394955ad2b8343956c1bbe016c032811a8a2208b534260f7cc2cb38d0628b31cd9b816c71860d33cbb774a05a33520515a9228b8313fcf426795fa5c83b36f9fc50b3db3324ac84ac6b7ae873ccdd15941cd375312c787061f8f67967f47ccc9b0095c79a594f8be31dee238264b33947ac94a53d9edf307fd219db5364592530f4cd75c6abb17a9180d0268b3af0783c44c35524a11040161e0db5d4fcc965cd50a76c41aaff417c513a8b80123533e087ca65a4d7cdf373e9a4ce3cb88c5874bdcbd751f2143e397129ab055e7f5b75fe7c0b1832c76d6b87ce736abbd2ed2b39b4f948c602cc449a5e2a0cb8a8522cf88a4e4e09ebd34fc10951584616436b428cdb451988cab140c577aa7289d921a7bdba0142274b9f67a2b4c32f764999c92a9e6abfa4bbb9c0b611b1f6305341a35f22c637dadc32f7efa1bf2b828cb6ad2dc2ce79ba152f7e5276633891b176eb0f070996192d168b5caf74c97b62a3aa68c63b415eefed6b4791a544b3119d3a81e46d78e06459691a729cd2064ffb6edd48300e176641acbd788fe2e54216c636e5c4876085478ac0d865cb9738744e4bc7af638a7cf9e006df6c3c40eac6c0d0d050c7be648615f9865c1e3f91ac1e5a11aa8947f030d5e106e3b48cff7cd26224012c7a499594af95294a6d67640c79c5dcba01fd3ebf410da6c22eb8bcac8b92bb09d6e946bc52089e9d9f999a11f98f978a62ecb4a28d9e72510657338870a262d816dcab70836479e10d4c208cfaec1cdb3020f9f7e37e6daa5eb90db410321200cd877f800afbe7e8a2290dc5e5ce0e1ca32856574e196bc3d96e10c1c7be9a24068cd9e1d3b69d46a600d021be398523498887b32a98ac27c32dc3b4ff3ee4638a55c559c26d94a6e9d72ad4c90164210852102488619d7aedce6e19d8708bb1e7d2c3f65958ebe2fafabf71d2c0fd32bb8f0f915fabd182d248d46036cf76d3298fbe6738d9bfcef6ae8e93129f055aa6abdd9ae3f46010a61f67fd4ca9d298ed93437cb98ae35d8bd6d1b617930e1b8e1312ac3781d96fc65af95f5bbe7681234f79616b9b3b4407da6c1d7bff936dbb74f83366bf19f586a614ef4ec778dd2f4dcec884a7d5715e3784e364749bbca78c7664af5c9d020ec1267bbd7832704599691a529e2658e9e1bc5693623edf7060cfb311e925a5833dbc657f22d8459356446e114bd3866100f01f03d89e799a9074230628a0a6357af9f8d204f8253884f0ec21e6a2f10d6a5e0a19540151ab48756be391661b50b7e6818454230d7e0f5afbcc1f6fdbb79d45de7f6a379baf190b4c811a5256e7c4e4f034323c5b69919661a0da41d58d2e5742d9bdfb1fc3f1e4f9bb6c193df9eaca36a5d8ef2461997a8e83727489a919f136196b88561481ae7f4db311ffcf613501e5a9bc3b06cd4e37fab301157aeade250e6e1eac315ae5fb9cd6090d06898edd44a9eb45b15963eb84a022e7fe3fcf26468abecca6b179ce06fa2301da4dd60589b8d838c8b2c4e08b460d7ec2cbb6667096c8324cad9272e95ade1667598320994d064c0da70c895fb775989bb1c3e7988b7bf7a162fa0f4f9bb7c53f9bb010a86fd983ccff17d0fdf33c682192e36f6b0c1b3d1d0fcd8243c356c0368dd5fbee721a5244b12d224819763693ae21822274946a7d3231ec4f8c2a711d5ecb417032300660e97f43c72a558eb7619c471b93b8fef5a403b423936ba574634faf97251559c8f8714025f7a849e876f47709512a03d840eb875eb2137aedf85c0b71bd92a882447df3cc157fef0ebe481e0eadddbccaf2e9116b93933dd6ea6e08a3ad958b8bf2e78d26cdeb16daac5b17dfba97b01148a22cf8d7ba302cb0bbf27b8d4c7736004d22a2357c7634a67f4ad796edc35524a1a4d33d835e8c5fcf267bfe1e6d5db88b05e76afb68470ff8d0fa498df665fad0be72ef1e0ee3cc37ecc54ab553e355df3ea94c3d18090a99f8df97e1a6cf68553a4d5d2683d3e656a741f9436ca264f73b241422b8c38b87b37334db3b391d6a60b6296ed3e9d46714ad3f3cca87cac35d71e3ce0dac37b043375befde77fc08efddb41e6c64273f195bd9d8ae27779d01a0a4dbf3720cf0aa22024b027ce4e2ad9eaf566fcefeebd3846f428e39556be8034cd8863b38cf625284d0b6d74421a67f4da3d8a34a7e60766fdf8642b67e9a0040cedfccc342f10c26c11e5db112bd7bd74ac3d46f80a115f3e1ecf480e4ec4a2c03782243d3c2fa05050e492e5476d3e3f7f997c5898737a84060f6a730dce7ef52df6be7288f9ce0a57eedfa61df7c9550e5a19819c4cabc230580b17dc767182baef73e2c04176b45a14c39878301c132e41c5fbf052e0227b964847a52a19b3b41e9dc55911865299ba30529c0808c38066b3c5fa5a87a5c5357ef1d35f23f047835f4fe40f27d6d5e0410c9f9dbb40b7dd4760bae6e5d110f6703d67618a52885c9a23e17b1e945febf11d4f4db4e3f196f22000e14e048074104392b1add664dff69dd43ccfaec433318ee4663caf23fa8ed34d6b8d46a284c77a92f1d99ddbac241d5e79ed08ef7cfd75d0315a9b83c70c6f6ecd6f65af2257f47a3db452d4a28830086c4e9cabc35e3dc9627d89703c667e1bbfb5effb78764fcd38369b76bcb0d22c2b150105a4c39461770899a2ee0744818f679566b5e0859d9f394c53fac31865e7a105814f148466bbb48a82ad62b2527f7fd008a1097c0f81c60f24db776cb327eb99adf13e3f7f81c5078be8a2e233f5e0d0f1c3bcf1f5af205a752edebdcd839525d222430be34bd91aa6ec8e2c5a9b6db50204bba6a7d9333347a8211b0c4993646b66abdc2eebd05a4c4f8b6a23b655a8befb385495a4537855e11676bdbda3a113eea9a929f2429167f0d16fcff3f0d63c9e5f07cc2ee065b0d19a3059caca73e9b3787b811bd76f3318c4349a4d0aad4a81765dd032aaf18836fa549f03a3542af7b47109951471cac40621ed217685221d0cf1b282dd5333ec9c9a363d37cc6a968d18354c468a47743775222834a45a90ca80db2babdc5859c29f89f8d677de61fbce165a67e5de9c4f5d7aa58863d3dd0dfd00afcaf39586473f6651c6cb42357e61141512cca4fb30c2171e2a532403bb61f2d8d72f022dd0b926eec524dd015e01ada846287da4705d0a4b08ad2994222d14fd61cc303566af1023ffa070566945fbb389f0fdfe14a8298b909ac017f8be407a70f2f471a6b74fa3b44622b975fd361fbdff31ddf5aec9bb32abfe6776ccf0ee1f7c95a3afbdcabdd525aedeb9cd4a7b8db4282a4a73bc6c5b955560562eec989ae6f881834c0735e26e8f2c1ded01e824c3291b1746b5b275fc9bc3bd2b2a316cc4d32a4e139b793eca87696cab42e4f2edde0b8280e9e96986bd84e5c5363ff9c1cf11ca33f680f06c3fba4c663c97428f2b500d14828fdefb98f5d536499e536f3649b2ccee5369ad3a1ba1fbd294cb0a77b9086214af6b401e17cab8b6a493bbe734dcc8ef57beaf34e930a68813a6838823bbf6b2bdd5c217f6ed7280cac653d2ba5247e57ff62d215008523c16fb43beb873879ec878fb0fdee20ffee86da49f233cd7d855be1bbf2ca3d436ff3a2f488709429935de52b8c52fa3ba9eac6fc678c360735a3d1b5c9ca61e46ff3c2909fd005fda3d35073159a65e8ed2d498a90e2ad324bd21c52021c2a319d6f185b4bb6b5bc6c234c6b9526445416f386498a696a86e571bbb9185bdb7192689f7bb87495fba7983be2457197b0eece1c46b2708ea016118d159eff2db5f7fc89ddbf7c9338dca73d0053284fd87f7f2c657df44d443ae3fb8c7ed8505fa714c6e775fd71bdc57e3cc532a0f4cebd808234e1e3ac2aed694e9a20fe3b1f7cafd41ed4ca4c74f0b7912c49675338e8d1667358c6393f24d28f971ffb6e19766b389d2826e2fe6d34f3ee3c1dd79b3a59bdb9a4c8c14a7b00d85d520e3fe6b258857bb5c387f893cd384518496e61c79c5c649d7239838953016e986323f4530b198303e24340e6d077bdc31f72619c32b45ae8807434451b0add9e2e0ce9db4c2c89ec735a299c3488646f734942bbf0d5f99dfb1d25c7df0801b0b77d97170077ff1d77fccaedd33089123ccb974a6cc8ede8f85244b3386fd219e16c6d214660680a8d4bd10a39ecf28af069bf3cfcb80b08b06cc009b87c017129d1524c384222b5e5c696a6d9849dbed93869d3ed9303187d27b01be5bd8250c3b084b5ca521c90bfac3a199ff647d5a6e1bb02a4b55893349bcdf1f6c9eecd48420f0498b94fa54c8bbdf78833d0776e2f992222bb87af1069f9fbb4432cc5185a628323419f556c0dbdf788b136f9ca29dc5dc5878c852a7cb304ded523d36e9ee9974c7a8204c552ba5d8bb6307c7f6eca72e7de26e9f3ccbc718d950d4582963025bd2f55918d1e5cd85519d8dc2cba8af716b5154369c10d659df6ab5e8f5fa2c3d5ae5bddf7c84ce0548b3404253116647074d45b8b5f53907dcbd769787771f91c429cd668b4215484fda3d28abbc39c268b8a64ad167a163e5eb272802233b66a19f0ba68115647941364c88b460f7cc0cbba6a60905661550a9644c30a4180d848d1a32439ba2300b557205b90c78d4eff1e9ed6b74559f3ffccebb9c3a7d04416e160854ad4197ff0939755702530745ae48e2044f78e66cfbb2d8d6ceb3730e7f1ff26e94b5f927edf9e72a2f180ecc5cf217569a02105aa07345324ce8acad930e62ea4140230890638e73c3600a28d00c93946edf6c3a6c1812023bddc86dc7fffb20da5343986953d2f3086b1185ca5022e5dd6fbcc157bff12653b30dd22c6565698d5ffdf437dcbbf100a18c002a51e0d73df69e3cc81f7ff74f993bbc871b8bf35cba738bc5f63a5991db06c9299e49611d17542d3485ca6944354ebf72827ddb761277cdcef9b902ca5d6daa96da085674c6ee3d1d5cde1e07b79cf3d9e1f259cdafcb7ff94c4aeab5882808585f6ff39b5fbdcfa50b57410676050b46882bbb886ba147473108693634ee67bcff9b0f595b5d272f147e185ae19dd4b9a3c258af9bddc4e5f9512a2d6b244c860d28159c4029415668e2618c8a13b645758eedd9cbb66603a915c6561e1d82e6e2abfe353ba69be785d21485426941267d96e298f72e7dc1fdde0a5ffb93aff3677ff50734a7426ba58bf2ccf06aa334cae7884304ce252d288a9c3ccff07d8f7a541b7f5fd8e59d15a53ec9af5f169c8cb87c87be4f148628a518f407a8e20595a621ba1108952bd26142dc1fa2f382ba1752f33db3535169288cac9b342fe8c7713908046661bc3967c754b0cbb9b6695505e5b10cf53b812949d99512a054c120e9b2fde014dff9d36ff2ce575fa7d1aca173cdcdab77f8f98f7ec3f2fc3a12b3530f9ec66f789c7cfb1467bff9164928f8fcf6756e3cb84f6768e6ad6aedd6efba46bc5a7e05f60859ad150a8d071cdebd87570f1ea6ae3dfa6b5d923831df59be735dff8dcad3b1f6b3624c2cb608e375b65918c17149f5de285f66cee4b8f254aa60c78e6d0c7a431ecdaff0f39ffc9a6250a09540fabe89c96d880b08e9fc9dc274e53d8fa5074b5cbe789d24ce69d45b366e2ac33262a4246dcd9b2ad98c079f8e8eee2d634c54e9502dff78fcee1d61ad4ca505795630ec0f099466dff42c47f6eca11906e624d7ca512286570a942a4a2569749d6d54b55da48287f623d6b28c8f6fdee0d2c21d4e7fed0cdffdfbbf64f7fe39903942c8f208ea6a8d4dd69ab063a0864cc6321d0ecd4065e96e71ef5ba555fd3bcea35f2e4ada5ad6088380c8f74119a3304bf317539a06c24e6a2fc892942c4ef194a01604849e193997c67b5e42979b0e0f19a4a9b143b51985ae85a1598e65a7786c5611e302f67b42c9ed6654574ae3d31a267d4408878fefe3cffef2db7ce5dd376836eb64c38c8fdffb94f77ef111bdf5d82a4e013a6376f734ef7efbeb1c79ed048b830e571fdee5fed22299d92cb2a254dcc4e491b236cfeca1bd1ab316bd56e7f4a1a3ec9b9a450d62faedae110437f0b309fdcc44e609423f139c72dc2c3c19a364ab35be5986c615aceba67b9e240a43ea519dde5a9f8b9f5fe1ca6757115e68367ba6e2041466135ef3db5a49083effe4222b4b6b282568b55a68db15d64e986db293397b5ea12e1545e5de646927a1b5758795c19c7c906719459c3013461cd9bd9b1dad5679922bd6ede55c5fbab28fade11d87d15e0f3a08e82bc567b7eff0dee58b1c387d947ff10f7fc391e37b294466397054bf4eb995779cd6d902c920261da67842127866f6899b66084689bf08cafc3c260f0e4eb6aa962d023ccf6c8ae349734e549a64a449fa3294a649d4ecd69e900d627c058d2022f27dccdeebae5a0c032aa149f39cce60c020cd9041600e0493766580a579959d5ce1470ae4f70cdb1068adf17db3f38ee74bfac31e684dd4f03875f618fff25ffd0d5ffdc65bcccc4eb1f868899ffef0177cf8de2724fdcc741f031fbfe673f8f431bef5177fc4ecfe5ddc5b5fe1d2dd5b3c5a5f23b3db5109eb37c65af62509b4b151dce8a80f84c0d1dd7b387de030d3d2a7b7bace7030307413b63eca2dc86cfd55069f7846a61bc1c5580dcf064b5563d395753d5ee7d5c3b7ca77ac32989d9e251ec4ac2eacf2a3effd94fe5a0f94b1b4dc09a0664f033b9a223c103ef94accc7ef9f63d04f088310df37f306abab62cc6ef34f0ba756ab618b3736f0f353d0ce5a620849512892de8020571cdcbe93e37bf7311d45667311fb9ac6987ac2aea6728d8db233595ca3a935e45ab33618f0f1d56bfceaf3cfb8b638cf9977dfe095d78e10d4cce633a67f633bfeb61353e6d82ade2d8b2004f130264b527ce9130661a9701d3693f3eabd27f26685051ffbde1846ef393a79c20c040965dc1f49fc1294a6b694ce12b31ddcb0d327c4a36137eaa81aeea5cf446be222a71bc7a48522aa37f07cb349876733fab8823eeed9ef1ec6851045017ee0334c06201422f2a84f859c7ee3047ff9dd3fe6cd77ce207dc1bdfbf7f8d98f7fc1d58bd7517101a107814f38d3e0cdafbdc59b7ff02e694d727de101d71edea71b0f516e7fc1525b8eca5f9d0223d176145233576f71e6d051f6cfee400f337aed2e99ddd64cda77467e2ce353b6a5f9bd63243eba9c90ed7266ee68b43093b5cda473d7cd14f87e40abdea2b3d2e5da85eb9cffed397bdcaf6794a6b3d2ad3206738ecd95f357b975fd2ec37e4a54af977e7697a69bba3429ac1b79714cf4c7f25e91e3d1536147e64b54df706f55619ebb6dddb45dad92f687ccf821c7f7ee67ffb6ed44d24c68af708a553866d59dd945cc58834e86b552a445c1fa60c0e53bb779efb3f3dc7ef488763ce4d6fd7b681445916f5470251f5a4c92c4c1a6075669a639611052ab4508366b3c468a72b3674cd07f635d586c717b33686c3eedcc185f0822cf4768413248c8d297307aee04398d53baeb1de2ee90ba17d0b02749baaea3616e8dd28a4229fa4942bb3f40494958338e60895996e84b0fb7b9c024b624ccef09c26eda11da6560699a9ab20602250a82bae0d4ebaff097dffd0e6fbd7b86a8e673e3c60d7ef3ebdf327f7f110a019e07bea0b9778eafffc93778f59d3374bd9ccfefdee4da837b7406030cbb3b8562429591dc78b814868e35e97174f75ece1c3acaf6b041bcda25ee0dd1ca2a4d6b79b9dda847d89c397f5718a9995139dd13575ef78e81e12bc7169e944c4f4d932619ed9536bffed97bb41fb54146630a4f2b7b26b71690c1c71f9ea3d31e9017e087114aeb721048daa3652b8eb9515c15453ac99b26bfa39c8eca365234d56b83c9728fc3a461c711ec02912449f10bcdae468b233b773353af13089095e5a9e012a9e4c73ef37d1f2124499eb3d2ed70f5ee5dce5dbfc6bdd51506aaa0d668f18b5ffe860fdeff18e905665c59189e73cad2956703ec14b791f529cd39f2bd983c558441442d34756330cadf24b6a2f366f79e07ae3e1d5d84065f78d4828000493a4c49e3e4c595a6d6c6fd98a719717f884a73ea41483d0890c2284beb714369615602694d6f10b3d6ebe1471148336f2bf03cc2c04c6eaf6ebd354e909743a01786cd82007ce931d53407c1f57a3d86716c0f55032f80996d2dcebe79927ff9f7dfe5cffef28f684d3738f7e979deffed47f4963a36220d0dc9c1b3c7f98b7ff1979c78eb35eeae2ff3de179f71e5ee5d06694a618f111809996330f357602a5a0a08046c6fb578fdd8094eef3b841fe77497db647186d066ab11b7698ab3d246d89a71bf2c94a997ae43a31cca46a22291a5788ddc8ce0060aa5c00b3ca66766585bed70e5e2757efdd3f7acb51998010f29299cd20c3cee5db9c3c52faed0b79b7394733dca338a84797f0c5bf1e038ed4c09ccbb4eb198b8c75eaba0a2d4265c10ce22744169cd703024eef7990e234eec3dc081eddb883c6114a66b7a9caeb7d3fd726dbae30881e719cf675a142cb5d7b974fb36e7af5fe3e6c202abfd3e49ae989d996565719dffe7ffe3ffc3837b8f109e5f96c8b9515ccecb6a72c5104c284e0959c1fa6a873cd144418d5a58438ad1218336a64a98d4015f162afd62db38f952520b4202e1930e12ba6b9de7539a26625b18ccde832a5364c3144f41cdf3095d37db1255d950283372de190e1864297e14e2f91e4a15762fcd00e1ba0c93e93e8b4fe37705adf1a564ba39452825c3e190de70607a7da18f570ff16a3eaded2d4e9f7d85bffc9b3fe52ffeea4ff002c9c71f7fca8d2b37ac6f4d8107612be2e89957f8ea9f7c93e69eeddc6daf72eee6756e3d5aa0331890e7b965560cf5edf2d35130ab83249a9aef7370db76de397a9c035373e4ed3ebd953645ae2a93a32715a65552bf2738c1d34e502cf396a664e53dc4e85445f7be3be5b2d96a91178af67a8f5ffdfc7d166f2f8017d9eeb88d534b50828f7ef311f30f1fa194a639d542a902ecca1e4f4aebefd5557d56626b1e14c6616237d1d08cce8b2fcb37466977252a1a6723b432f9505a936719c34e173f571cd9be8bb3878fb2add9349df6f2686417abe1152756c2ee57ab806192b2d85ee7cafdbb7c7eeb2677d756493d8f14c8b29cd00bf188b87ef92edffbc71f23308d8f1def1fcb5f498f09b2946f0909b9a2df1da0724de885f8d67a35ef19ed3272cb6c4ee3cdeebd384c7a46f11b1afa9e473d8888fc802c4ee8ac3f87d274452aaf35a8c274cfb34182549a9a1f1008b3f1b02b9a36ba1525347196d11d0e489542863e7ee021a4200c3c7ccfb43866cad166c4b1dda4c710f4770a3b0fb5118504be471c0f89f3a494493c61acce40123523f6ecdfc977bef387fccd77ff9c7adde7f6ad9b863f8404a5c08760a6ce6befbcce57fff85bd477cd716be5119fddbece7c7b9d6e3c84c2f8aa8c0008a496987f76cb3209429811d3995a8dd3070e72f6e051e66448daee31e80fc895a6d002553650ae56c7adbadf274a6533c17355888ae2c48d0c0b8d1704345a2de261cebd5bf3fcfc47bf221fe4207cb452667359e9d35fe8f0f9a75fd06df768349b485f9a2367ad4bc94d6cdfca5df42494799ff87cb3d8aa7642d56870c682d62e5f665ad0b03740f56376d79abc7ee81887b6ed2012beb590dd48b009ae8a85f5fb4a3fa02814edfe8007ab2b5cb8739bcf6edde461b74d1e8534b6cd124411599642ae9869cc5024921ffde0975cbb741bc27a19b74bcb355e2ee79bd69b9dd33d1c0c4141184466d9f053f09c310aec7b4f7efd19e1726bb9ce76d13d298902b3935991e674d7bbcfae341d5ca59a9540caac39efc7c85cd30842027764af6da05d811530c812bafd3e5a40b3d5324c8fb1d8042321de9a4f3754c5ef1c2e07c20ebed483807a1030180ee80cfae01b8b460b3b1f490a087d827ac88e7dbbf8a3bff863fef69fff15070fed375694343e462410495a7be7f8833ff923f61c3dc47232e0b33bb7f9e8ea656ecd3fa4dd35833a663588b522ec4e84d206638509a22060cfec1cef9e38c5eb078ed15492eed22a717fb4490a15c1148e6d7e4f24765dca110b3b5a572ca731cbdad057fa66d9a4e3358da635358d9421fd4ecacf7ef42b3efcf5c75078806f8fc7907cfc9b8fb97df33e59a6989e9d318362529846479874ab03651be114d3244cce9d92d39537ab6f8f94e3e4535b6a2767a5d234166b1ea7e4dd217322e09d83c778f3d051e66a0dbbb24662b69c30f1b939980281141e42786459ce6ab7cb8d870ff9f4fa55ceddb8ceddce1a6914114c4f11d4eb845164cefc8e63b64fcfe1eb80a587ebfca7ffdff719ae2708692c4e3765ab5ab249f611b65103e3ca1bf4fa6603efa88627cc8c99892f266f7ce918190f26181ed0444140e407a842d1eff59f4f696ad7ddb603092a2b488666de95d0829a6f762912da76452cd368bb12689026f493182f0c8cd21466a4ca73e7b09482311299c7616b867e318c09e604aa4a466aa80721353f2019c6b4db1df0cd323e213d3badc54cac1681870c04d3332dcebcf53aa7de3c6bcaa8ac33120d9e44d425ebdd0ef71f3d2295b09ea79cbf73938faf5ce2f6c202eb3d632d9ab5e415a615e688101060f3570f228eedd9cbbb274ef2ca8e3dd04d89d7ba885ce109732c86b647218c847452809fae2e5e0c2e7ee77ed8085717665fcb51fd78e58aa7d13b7e60f82b2f140fef2df2a3effd82c5bb2b8830347ef4b5845ffde27d56573bd4ea2dfc304457f6ca941254912337751755afabb4da88c92e6c15a37837bee3e4c6cd15d5d69d92e705c36e1f2f4e393435cbdb878eb1b3d92a57d399b844d9849a932c3d3c3f40783ec32c63b9d3e1d6fc029fdfbac1e507f77814f7518d3aa25937672409332d09ad4986437ce933d5984625828f7ffb05bffac97bf62817b3b784ebf56c46075347d84120733650324cf084a4168626dfa2eaa0de1c93f5fb386cacafa7c1e81bc781c686f1083d1fa178f129474a298a2c278d63fa9d1ee93026101eb5201c9b36e42a5ea1498b9c411293a9825ab341bdd120cf0b02dfa7d968e0490f4ac254ba258f21c2e39e3d2f262ba67aadb5359fadfb41a269d4229a610d9566acafad5bfabb56d886726189f16186759fc654a39c33584ee895d059e8f08ffff97bdcbdf700afd6c06b36594e13beb87f8f8f6f5ce7dafc4396daeb0cb20c25dc12b64d5a7cbba9f15454e3d5fd07f8da89d31c9dde8eec0ce92fafa192bc647a73c6765571da5076939fccac4f0b27da63616c9c63eb7adf5037a5608e829b5253afd7a9d5eae489e6eae7d7f9c50f7e0d99d9c6f0d2c757b87af10679ae694c4da1a5f1f5b9f642db65b2c6f21c4bb20247ef71ba4f62b3728c60be75792fefba6b6127b0179a2c53a4fd18d11f70a0dee29dc3af70646e1b7529adb21fa703b6de84f42884a49765dc5b59e6c2ddbb7c76e73637969758531945bd866cd6c1f750020aada8d76bf89e479aa6e469caf69939ea618bf6628f7ffc8f3fe0c1ad0708bf66e8bd69c352ad2b2b2c42906739799ae10b41e0997c3b37f364dd3a8cddb73fb77a174c728fe3a12ab68e47e021a8499f46584328e8765ea07b0ea23c35311e0ee9acb7498631a1e7510f42cce65c660f40ad35b92ac854419c6574067d9410ccccce526f34ccd9c741c0dcec1cc212df557815d5c23f2d419e075b13b1a2305d1eacc8d7c28856bd8ece15ed957574e6f255550b956bab38a1b027ce19864248da8b1dfeeb7ffc011fbcf7115204b4a667c81074d3824771cc85f907fcea8bcff8e8ca45aedfbfc7d2fa1a719a98e86dde4bfad8695e9e809dd333bc73fc24df7af534fbea53c44babac3f5a46d9738dd07660a9cca9b3916c5dd8e5721bcbf4249877ab75e6829b3c6e824da34c676b6c540ed6faf43c84e7213d632949693611ae8711f1fa901ffec7ef73ff83db305ff0e3effd84f5b536515423887c94f5435b3d65f2e059378b4b78cb8c397e1df1ced8ab637c3aa2c7a4c22ccb5309420872ad49b29c741893acb7d9e6857cf5e871de3d719cd95a0dbfeae7b7f588954fa535832461b9d3e6dafc033eb87a890f6f5ee5fafa2a5d0ff25a0d6a11848129af1088ff7f7bfff964c97124fa82bf88cc3cba446bad816e7443138a04014a80c3992187e47038f33873dfb5bb1ff7c3dabe5ddbf76dd7eefe0bcfd6d6de87bb76f7bddd3b3b33140041022009426b2d1ae86e74a3bb81d6b2baf411991911fbc123f2e43955d5026286f75d7a5954d5c9931919c2ddc3c3ddc35d43a351f7e9922d9d4e875ab5ca9a156b48e70d07deff88877ef608ae978b0e3544262bf57390d185edb9a2dbedd1eba592c3be128333389787af2f0bc3cc7378dccaf870cd685a82324e35aa355ab53a319ab9cfc2345dc9ea9aa53969b7874d0db538a11a45039673bc549a5b4b27cf98edf5c894a3353a4a9cc4e4469c5c2b71526c2f42e7cb93f06f010393407f0286db25c7f8aa601d9df93679ea2db0b8be25302076f001510e229fa6134fa539bcfaec9b3cf9db67c87b8ed1d165546b4dac8a98ed7499c90c7371c4c7939778fbf061def8f000074e1ce7f4e42473bdaebcaf4c705aa122d1f1454ab1bcdee0d62ddbf8f2f5bbd9d25a417a719ad90b5398d480d3b2b67a97a4407c450916d86b963c459275befb61e80670ba500778268230efaba0230ffece52dfb5d628ad482a156a951a2eb34c9cbcc8b30f3fcd8157f7f3eeebef82819191110947187e4a277fc2dc2ba5cacd5b02fa370cf4cd431f67fc7d5e1fddff5f06476e13fdbf530aeb34269358b59d0b938c6470d7b6ebb9ebba9dac1e19114d90efb61c4514c777eb14b983b934e3938be779f7e3c3bcfed187ec3d738293dd39e612c8eb555c25c125a24a529146258a28965c5d511c91653d31080123cd1623cd31f28ee3f9a75ee19dd7f7429248fbcb745286323774d0e9f4e8743a4491a692c4a2b4f37edc61c4cae33e50eff02bca7372c5f9b906f00c594e04299a952a159d9077b36b679aaae8909712ac234b33b24e8ab6508d12629ff92e6c37652814c642c71866d21e462946c64771387293912492e2c253908cb387610635fcf98b85a567a1dc8e388a2564bf754c5e9a14ff476c18b0f0445fc2d46229172daf4f3f5b8523fb4ef2fbdf3cc3c4f9699a2363d49b2d74921025558cd3d828216a8de29a4d2ef67aec3d719c573edccfdb870f73e4f46926e6e6e899cc6b048478ad132764e58315af1b5fc11dd7ddc0bd3b6f64437d8cecd22c13a72fd099eb82d344c468ab889cc40de8b3b061a21f963ccb50bae6a5470a462bcf9619327e3ccba5bc5f5f7c06fa532338293817acb94e01deb526ae54a8d46ad814de7af95d9e78f449262f4e53af36a8d56ae08d47f8dc6a0b8cb957fa5c80ef5fd1b0fe3705be0ce80021d0522884fe2831e858079dd936f3672769742db7addbc25776ee61e3f215547ce83be5a52215c5a828c20299734cf57a1c9fbcc83b9f1ce1e58f0ef0c1b9535c7286b49a602a312e8e5049824e126fb0144953479a288988134596a7e47986cd4495b3627c3989aa337da1c3a30f3fc1dcf939503e5575090f061609df1fac23eda5f43a5d62ef972d6b9c185eca4c724926fcaf08caef98ab7142454764ed4fa9d3d4c816c01a4b9e597a9d8c5ebb273e9a71e2a330f791270c65ee1ced5eca5c9aa22a31e3cbc7c96c469665244942520ca0a7f0a560983ebf4018de72f4e961d09a1b47118d7a9d48455c3c7f914ed71fa7d45ef20b8a1be5208ea4135a0855e90822b8f8c92c3fffc7473871e434cdda2823a363441e997514a3e284b85225aad4a8b44671cd16f35a73f8e205de3c7288d70f1e64fff1631c3d7d8ab3972e31d36ed3cd33718af77d8895a651adb179f92aeebff136be71d31d6c6eadc04d77b874e622f397e6311d83ce35b18d886c44e4a242d2a42c812e903cfbacb2b8e6bf1f64267d46ec65da024b44c6f3a5cc38cb635f2ee17a51af485984ad9a160690542b345a23444995e39f9ce2cdd7de26520963a3e3682d0ede972d5e75305094e0c160196ae89004ddef52595a575e180b0cc38f5bea48e77ab427a6c92fceb056d7b867db2ebe76c3ad6c5db68aba4e6451f312ab7260f28c4eafcbd9a94bec3f718cd70eeee3850f3f60ef99139ceacdd3ae44e8910651bd46544950b1972ebd844a11b44324f56ab50258b2ac27fa5d67a92409e3ad65685363df3b1ff1d82f7e8fed5a948a4afd1d06e5f5fa72863beda69294304988221f0be08f0c143ec5887354a288aa8ec5ad72f8c6ab8182895809d4d1ebf448db3d1234d53811a5b0dfe600c5ca9f5b43274de9a41949adc6e8f828c664586ba855139268e9e3939f078455ec4a6510841896966ebd6b8256d4aa55e228667a6a9ad9d9192f55063b905fc5bd77001ec1545c813822ef3a7ef79ba778f3f5b7c0281af526d54ac5d35a90e800145a47e84a854aab456dd938aad960d2647c78ee346f1cfa90370f7ec8dec387397eee1c172627999d93d417caab54b4d654e30aeb96ade49e5d37f1959d37b17d7c35d15cc6ccd90926cf4dd0996e17cc535b8df656d2b2d41898685f2c1b621e25021a1ee3e122e3b8c41c0c4fc9a230fc7cffbd5aeb22e669a55663bed3656a6a8e5aa54eadd24039998bc0d0fad2efc2760e9785b004f32c9866699151816982d2115a2728156372476fbec7ecc569664f5dc09e9f6163dce0abd7ddc05777ddc8d6d56b69c415f1cdd531918e710e7a59cee4cc2c27ce9fe3c393c779eda3fdbc7af800fbcf9f665a59a29116c948135dada213914875295c5e00190787c252af5549624d168e07e3c0199af526b5a4c5dc548f3ffcfe395e7bf14da826281f5b695150b293ecb43b646926b9ad92c42f9c65c97410c2f5c5c77b71b8967b1703c9c7e4691ba8c631551d61af35345c59ea72a5adf9fccc3c5927a5aa636a899c39c74b0d4a2922bf7a65d6d0c9325263181d1b65746c9434cb500a129f5c497944f56f2c21e06783cb0da2278fa19f3eaa0f4ca66f52a84f299138348e248a487444b7dd637e76de1fd90b5b93e052a425e95794804a24ba388a975f7c8d679e7e9eb497d168d4a9541219c1c2601274a4e270adb426ae55a9341b34c6c6484647e855228e4d4fb2f7c4715e3f7c88d70e7ec8db870e72f0f8714e9e3fc785a929a6e7e668b7dbf4b20c631d6b96ade4cb37dec67d37ddc9e6d155545345676296a9339798bd30436fb68beb3ab489d03641d9d8331959088b32247d16ff97ee2a4399bd3a19c88199003f6ebeee2b96a137148ccdd7a57df8b8b8929063c9ac158689f4a568f750ab6581d09eb9892486976407fabf4859f427a80eca4c5a45443a1149cf40369f31777692ee99495a7386dd23abb97ffb6eeeddb1931d2bd7305eaba3b538e783a293665c989de3e30b17d977e2046f1c39c4eb470f71e8e2392eba8cac5e81465da4cb38e9b750f908e9c1295df567c6598356106bb120e34c1120453947a434ad7a0be56226cecdf0f02f1e65eefc3caa5a3ee3af06e644ea75743a3d8c31445144259623994bf0cb02029d5d8e8e07e02a6fbb3c489c8638d654e3987a52815c98e855419f71c85feb2c799ed3edf468cfb4b169463596809de23ee0b0bee11a91b43a59cef4fc1c0658b9660d8d66935eda93d5466bd9f623bf9c0b5bb5cf170a622a31bd816b05014a294b1e7de943a2e60c3f5baf568994a6dbe971f6cc39ef33e9b77161b2555889fd775a71e09dfdfcfae1df70feec059af516ad660bade5240a7eac7bbd9e1cef4318a7fc3840115562a26a95a8d1c0d66bccc59a33dd367b4f1ee795830778f6bd7778f6dd7779e19d7778f5fdf779e7d0213ef8f828074f9ce4e4f90ba49961d3eaf5ecdeb89d75ad65d48d269b69333331c5d4f94b4c5e98a433ddc1741dda456827c622e7bc0f60f96701b30c1b7706c6756191d9ef3317cf88aff227f86d16058742241b994747d64d49d31e5639eacd3acd56b35800153e97957fe7305777368489f3fd18c293e132881bfd33ec1a8d5612204318a7c21a4567aec7ccc43413a72f3279f23cd9f91936444deeddb28b0776dfc657aebb811dabd7d18a12746ee9ce77b83031c9d1d36778efe8515ef9f000cfefff80e70f1de0ed53c738d69ea5534b885a4de27a035d49208afc41927e4b437750a5fc51dee302a588a258fca795f2cf84b89710c731cdc608dd76ce87fb8ef0c82f1e87bcbfa084270af02799e6e6e6bc2b57f00555108eaa2e024b8d65289f2ff84543895acd29d03ec15a23aea2ac2831ae090ac9c7077be8cc77989f9ec3f994bd15add1bef316990ce5fdccda59c67caf475cabb06cc50a1c90a6a9e89ce2b83f004e9eee6bc986e01ac729d42bc421f98c95f7c1d2c136e318b21aebc27157159281fc1ff4665a4960118d58d8c6475ad4920a693be5dcd98be4069cd27e027c5f02a61ae113f3e767f9d93ffe8283fb3f225115469a23a2e329f5db5947afd7f5a75516f65f6b2fdb6b8daa5650cd06a659a7538b99528ee3f3337c78fe0c7b4f1ee3d5c307796edf073cf5de3b3cf9d61bfceeb557f8c3cb2ff3fabbef323d358d3650d309559da052473adfa33335cfd4f929a6ce4d3077718674b603a923f6c6a2c06c82e4d467364e184e90a8ca3f7e0e06fef7461c85570704c228bd63a9d27f5fdf70a402be5a87b2d09d6f3335354514c78c8d8f49e2348510b8377a898e70b1ba81b274e89f59b4f8f66ba23e8e288f2f72958a4a8849303dc3fc749ba973134c9dbc48f7fc0c2379c24d6bb7f2f53db771d775bbd9be722da3b526dd6e97894b973871e62c073e3ecaabfbdfe7d9f7dfe3b90fdee7a58f0ef2fef9d39ce8cd315b8d70230da25683a85a15372c25bb1de7c30c063a965d8c180a9d1774e47f9140c5d650c1e40613420b168b14d4ab75222a74e7739e7dea25de7dfd3dd941b9b053a08ffbc6a05198cc608cd7f507ad76c10017a17784910b6ec96238884b0b19c2520cf8eac0624bc199955254e3847a52415d8ba4499916fcb6d1fa3ce7ddf92eca422da912eba8f0cd04c15c0764ced031193d6b49ea7546978f93db9c2c4f8593572afd55c48ff3d03a75cd505e89e4afd42d6e915eeded251381b27fa84ca823c22147cf42e913a748a182448e6a12d1a854c4c1fdd234269c29f775cb68fbbf11d8e99cc77ef95bde7b6b1f155567c5e872621d7bce3308c6189447e2388e88a288388a89a2884847128f348e50518c8e13e26a8d6a6b84faf271aa2bc671a30de62a9a8b36e374779e63b3531c9ebcc087e74f73e0ec29f69f3c26278d666725e39e5128ab70a9c57452bad373cc5d9864facc0566cf4dd0bd34836b67e80c12a77dd68dc064ca0c278c7b418d5eb290fbcbff0f321e91ca08e37b85d2b7232c1c3b3c1175da6d3a9d0ea3e3638c8e8de200e3715579cfaf40df03256415f169209d317212ceb8450bc6499e7beb70c69f9af3d76d66703d43de4ee94ecd33777186a9b397983f3f8d9be932ee2a6c6ead60cbf82a96579bf4e63b9c3c7f8e83c73ee6cdfdfb78f9fdbd3cf7dedb3cbf6f2faf1e39c407e74e71a233cbac06dbac138d8e10375bc4f51a3a8e0b67fd42d07116e3e340f4b393aa222e045ae1fcf15fa714519250a956c9b2ccbb1df9f1528e484bd2b1b1e60891aa72feec240ffdcbaf993a3b094955703fe8f071125b0111029c75c43a167e71193a0ff42b295f2422d7c2095a0496b87c2570ceef6f7d60148fb962088ae3826f5c3514cccc299c019b3b7aed1ebdb90e095a9cda0393f04c0f2f7176f39c4e96912bcbd8f27196af584e968be53c8e249ba3e83403cb0acd5d6438af7140fa8c30b04df9942bc554afc3b9f9592eb4e799e8b4b9d4ed30d9eb32d9eb31d54b99cd73e673c37c6e681b4bd73a7aced17390a1c824271f198ed4e51867c13866266748db067250b9eb33e2d0f679cb6ffee5317efbc81fc8e760b43a463d1107da7ecffb23a0fdb1b638f6aa8c52f4ed486b2adefba052a950ad56a956ab24d50a71bd4e6d6c8478ac856dd5c91b554cb38a6954c91b09b659256f5430f504d7a8908c34698e8ed21a1ba5d96a51abc9a9908ad6440e4833d2b90eed4bd3cc9cbf44676a96ac9d627a86bc9b93f572b29ea405487ba9b8a3f532f2544e8184d320572eb9942c27cf2f57cc22d7068bb196f9769ba99969a238a2353a02514437cfe876bb747b3d7abd1e594fda2a251d2869b747d6ed9176bba4bd1ebd6e579e1d2abd5ecfff4de976e5ff6eb72bf777bacccfce333d71894b67ce73f1e439a6cf4fd29e9ac3b43374cfa2534b36d7e6cca9d3bcb5f73d9e7ffb759e79e7759eddfb362f1fd8c71b473fe2fd3327f9786e8a4bda91d66be8d111e2b111a24683b8267acb484b50ef4270f0c24881870585f571ac288554eac4f5288eb0588c31a23253962892c0306069361a8c8f2e23eb58f6beb59f5ffffc71d179fbe3983844aa545a547aed1e79961347317192f43d0842abcadb6fdf2610ba77946596523f1691363f2d146d00d9edfa4866d53896c0ead6daab6741a510599d76caecc43c47de3eccbe67de25be30cf2dab36b3be3942338a0a9b87b28accc1e9f61cfbce9fe6d0c50bacdfbd936f7cfb01ce9c3cc5938f3d4ed24eb973f30e76ad5cc348ac49627f10a350520f4b8c2140ecd24d2f0f62f017554a1179bd94d18a199bf1dadebd5c6ab78993848a96c4f05a6ba228f6929ca6e25798288aa9c411712c5bae249280c9449a8eb37cf0c9517ef7da8b5cca3bdcf6cdbbf83ffd5fff07c6563564862305d68093e37d1fbcb497ffe7fff49f98b8304d356932da1aa712556535353ee45690381d9c3c718a8b172758b56a159bb76c01bf2d37594a12cbd1d3fee91a59916d909c15c25c7a195996176a0790051087978e6cc1b2f33c273599301d1f1ddd5a837546b62ecaa122455cab105565c2bc5019d493b22351d22e9c12d72794b7c2165d1b008597f686ae5d094248384fa1a5bf8a4aa54a776e9ee98909ea953acb97af40c7311647eea3b9e389b1a8afd446f98cafd307de2dad7f0350c253ad35ce09a3715624629b5ad276179b5a6c26191fadb1244e5347d35031751d53550a4c4ee63254222e53329e9aa456c7c60979a4d14942a55e475784b1392f2eab227050a021df76645b2c9e707e647d5f25679596be5a8bb69230f1f8b1e34c4e4eb279f366c6978d17439be5b964edac5620d69c9b38cb5c6792b51b96f13ffedffe8fdcf2b55bc058b0993fc8afe99d6ff34fffe917fce19f9ee0faf12d7cffabf771fdaa155494413923bcd5cf9fb4d90fb637aa06fa97500d0187e557d8014b97169d9d2521a82a40a4bc82f49c25339699dcf2e199b3bc78e4c0d5334dd9b688588d856e27e7e2e94b7cf4fa410ebdf43ead999c5bd66c6155bd49436ba2b013b3d0b5707c768a0fce9ee474779e9bbf723777dcfd650ebcbf8fe77fff075a06eedeb6931de3cb69469a2492a44665cbde20d3f4135c10c94218bcbfbf72683f409d58f3e1f9b3fce20f4f30996754922a15af7f525ef91b69890a13f99301911697aa281247fc4a1c932409511c3397a71c397d9263e74ed355968dd76fe2c73ffd1163cb9a8021528ab4d7c1640e93399e7ffa15de7df70095a449add2208e4432b0d6627249df1b265101e7cf5fe0d2c424cb962d63fdba75e27fa8c515046bfcf64bac9bc6198cb598527a119b5b8cb138e3ebf5de23a27793ff1510698916648c213319d63939c78d27c672247e052ad6282f8d04c5b9d35e07e2d1de7a24ce4d3e30371008bbcfe208b8760de09c3fc51372070d7c2be7ae5d9663d2947aa5461c0b03a28c41fd2601b01859c87cf8f7f8eded521008dc39eb7313c958dbcc60d39cc845442ac2a130b9415b458c22718ab8c05383c312552291f69c84c14b920a2aa9629542a9884ab5429cc4a0658bad22d9668b555ef004e4c088f32a29c21469bc6b4d6048c2a0acb1e2568ce2f8b1e35c9ab8c4fa0d1b58be6c19ce1bd98c31e4d6122509713561ae3dc78589b3e8c470db5d37f27ff81ffff7acdeb65a30408b1a263fd3e33fff3ffe579efbd5b36c68ac62cf864d7c69e7f56c5dbb9a8a06658ca09a678222f549101ad1cd8aaacafab42db2c8e391b99fb9351c12b85a2816498fb0ae38e26b31ce3197398e5cb8c84b473ffc144cd3586c0edd76cea9a367f8f0d57d9c7ee708ab6d95dd2b37b03ca95253de4d41416a2df379ce27d353bc7fe63879abc61df77f956d3baee3bdb7dfe3b5a79e655cc5dc7bfd6e363547a9478a38a298ec20082d609a0161bd12aa4c8865085266b12a014e69a6353cb9f71d7ef3f28ba4d52a956a95a8906865e0099366fd3bc211f13051257d68d7e4cca55dba698fdc19927ac2d8b251e25879770d87cd0d2eb7580b33d31db25c838abd733582d441c7a6c4e2ebd1984ebb4367be4ba552a55eaf15696c9db3603d61fa2d9555322ec6ef0cc44a198951ca88825ba3899448d4caa7c0d091e84695f727359ed054c8e6a8249d842a72f2f8c54b51f421a829cb4cb3601e25d92140609a03e0c7feaaa030ce84450291b84a8f3b27593a232581b1075be099db15df58924402cb5d04e7640e8b3b7045aa5c844389e798443f751a678de419cf2d58d9066a07c6e458638487f91218825c883025a394042811d734ad7d8c51ed5dad94f28a59dfc6d056e4b864c8521920f20bb87c1f33796982a9c96956ad5a45abd5228e6239ed2683005a79a66e999a9bc2aa1ca28cfbbffe157ef20f3f66d3b68df25203e9852effe97ffa7ff1f2132fb1a23ac6489c305a49f8d2ee3d5cbf791323d52ab1d2c45af053f03ce09fb451bc4abc410a25974bc7300347bb16c6596080f37a69e50312e539e888760e9f5c9ce0b56347ae91697a4578de33b4e7328e1f3ace87afec6362ff29b63557b0637425234a53f3fa2feb1c5d6b98c9323e9ebec407674fd258b7927bbe761fe3cb57f2ee9b6ff3cef3afb02aa9f2d55d37b2bedea2ea53e6687f9223346e98590666d8679aa1a5fe7271a12f65fa4f18adb9e00c0fbdfc02cf7db0174647a8d6eb445a54015e5ded99920367c502eb3c05fac90844e47c46bf2c487c2617a9cc7f67adc15aeb939ee18d43920551ee12284f84d2a20bf2ca093126e4925d502bed253ed947c86a2ce3e0948648a1c3a923100356609acea294268e2262ed0f22f86db3f2c4a6238dd3da3b5fcb7bac73a044fa2ebb1029e5b776fe8a439868d82e491f64fb4ce124d587a590afcfa02e0f4ac9314315f45d05b1c8b8e0eb5288f12f481465fc00c9cab82803f7a07cb7fc27795309af1640c08d804dce13b3a5b0a853a8531cc638d90984dd9c5f68acb1608dc4c1f46f36d688ab92953aad91789f827bb24087772a24ca5590c2f14736097df2d2a46434957e59476047a0a1d7eb92a719ad668b24ae102751d16eadc53320a9c4e84833db9e83089ccba8d6136ebbed1676ecd84ebd51a5556ba032c50b4fbfc8c1770fb0ac364a2d8ee9cccdb2b235cace2d5b58bf7c25cb5a238c365bd42ad5c2ce11e650796368a425f4a4c2892f69a4b126032bf745daeb88ae1294f7d691b930e099b3cd0da888d46a4e4d4df3c6894fae91693a87cb8d30cde994631f1ee7e0abfb993c74969d63abd9de5a4e03454581f6dba48eb54ce5291f5e3ccf8717ceb07ac766eefff6d74145bcf6e2ab1c7ae35d3635c7f8cace3dac4e6a54b4f87b473ebacc30b35cf0ff806e332043402ff97f78e872a5399df5f8e7e79ee6ed631fa3465b541b0dd91efbed247e8bd447404f2adeb9172f010b82cbfd81d055204c4f8c8180a52d621d764acee2977a32c040cb6d0e7d70c6f9ad946f907f42fa2b9ff1d2b90a473495f67b30a9dff9ba8324017e5ee94b7daab0aa868608d31477a03e23c0b7ba3ffe027eb928dd8397bc4229f76e2918ac73690875158d2d984288cd6a3d2129ff5de867e135e1d415d3f31678b848eb2fa74e28c6aa501b286f7f0db578bdaf91cda8b52529d69598859f2d99d5b08594faac77030a5e2bcef5172ce70cd6f4036284ef8b770f08079e6958e30553d1f9e626072ba93f14fe6cbfbf5f85a47328508e5eaf87f2ea35eb0c4a2b469a4daad50af54a150ccc4dcdd19d6e538f2a3e9d488e3696469c30526f32de1ca151a9d2a8d668d61b24de5ba412cb91cb6a5295e3975aa4e84a1c5149623486aa8e5931364aa352f501992f0f61a10878ecbc9a0be730b90841a888dc6acececef1f689639f82696616931a6627da1c7dff630ebefa01bd9333ec1a5fc3d6e6387507895228bf5d6ae3984853de3f738aa373935c7ffb6eeefddabd4c4fcef2d2d32f72f6c3235cbf7c0d776ebd8e157185448798bd22f617dbea25996630f23040947d37a285902bcd279d79fecb334fb1ffec29e2b111aa8d3a5a87f546a05c433148ce0d6c75082bb5476e8fab0504fc94555d988ef2ba29eba49dbe87fd377a3edcf714f3df38440e0ec21cb2170c4cd3216e4941a521e4256ede0e112b6474fae09c15fd9d06941c6375ceeb08fd4bfb23124092b24983c2982f84a08c97858d25effb6c10c67da164a19448cffd854d184c795c03389650157818609a614e17bc71210466d5effbe053fd6ffc1c856396bebd0144aa9227842106d72c81e03ad7678a4ac6c6df50e8ecfcc21196e83e3ef499a7b59e2102c6bb298177a5f21b4e2515a11095803572d8c55a2bfa482786c3803fc1ffd6e686084d8404f675d6ca6937eb50c68aafac8e889dc44948e29848476274f5b6049c238ec5db2644218a9425c2b26664946fdff31596d79bc441d0297abe105498cf121e3be7c4ae60ac247c4391a3b938dfe69de3c7afc5e548fcac9c77b7ca33cbccf42cedb936098aaa8e7c94b3b026ca33d641cf18e6b3145d4d688e8da2e3886eaf4bd6ed123909f251e85d4ac8d54799cf171cd0cd327ac6e0c2a904cf6802f20cca0225287da9b45f7595304107588df8b9f962b5c36a304af2f11814562b89db182bd9ca14f77ba13092ddbb8e545154a4e4fef08c2f3656d8086ce4b091c24aae3029c5fb1716e38b8d142e5638ad8b7a89645b16e663f0c75f515e877ab91f25451c855dc1203ecfd29f94c521306e1716211f3eadbf58f5eb5157516090b90eb7e7f2a590678a9fe21b255a1b15818e9c041b2a9710fcdfeb90d1e0b4c56a8b53e184987cb691c3450ea79d9f4f24fd8a2f3a89e4ec792c06261547e2d319c7e82421ae5488ab55a26a954abd4ab55ea352af531969501d69521f69526d35a98e8c501d69511f1da1313e4a637c94d6b2315ae3e3b446c718191b677c7c19a3a3e38c8c8d33323ecee88a15b4962da3b17c9cc6aa15b4d6aca4b96a05add52b68ad5d4d6dcd0ae29563e855cb702bc6c846ea741a15a612cd84769cb529e7c83995773991b539d69de7e3ce2c8767273970e12c472f9ca39de782cf9f119c93981222f020ae7791a4f1b97a90d34e6499a5dbcb989fef607a39551d538d6222277955c22a060a839c04ea9a9c6aa3c1d8b2718814b3333374e6db45d49dd82bae1d7d89ecea21a0fde541790ba153d04953ba59ee83d686b3c57efb3cfc20f4515f96a5e12f0594d451261481e0fae33d028aef846cd09e4985324ca9a552aedb2a8b550ea35ce1b01cfe5aef336e95f3f7598c27acf0d7228c4fdeeb84ad045de9401978eb22d7162f65a6b0f0dbcfab78b5875e58961ab7feac0cc1c2ca173eb4080cdfba7829e3c5e00f886426df96c777e8af97e014a26707eb03f85a5006abfccc2a9965271ef903f3d9ff3fd0a80cd1405bbd314f8a1277a1a0e7d60a172961c089a44056b184964baa55925a9da456a7d26850a9d7a9361bd45a2d61ba8d06956693b8d9805a959e829e02aa1574a30ef52a7aa4413cd6448d3650230d186dc2481dd7aae3461ae4ad1af948956ca442daaa908e54495b35d2668d590d93ddae08434bd1e862b0c4adceabb1e47b4792445493e41a98a6473ceb1c696ee8f532d15f1847a352a51ac57e2202e1814393594bc7e4a4ce30b64c9cda4d6eb8347189f9b9392a51cc68bd4112c5384adba0c05cfcfeb6af87f1cd09c4a1c42521480f725c6d89510828e82c596e44f51dc545d00c5cd8ca0a625b9f1fda152e265e43e5c58201442bae0fbf3b50aedc15083c483af2d76f61c29bbd84b67809c81c484ecea00772708124829457224c513e0c165cbf88b1c0f872758c71b08f836578240689f1732a1e27c3582c5540243219b7853db85608efbeaa32dce642675c2e5e55e20d46b6980f29828bbe0fce227b165f9404d3c095e6550df63d143173f6196728651c5caa0cf6aa5c0732e77ed78567b43a8e218a892a92fdd2e9484a1c89a1320217298c56180526925d58ee4f6a49a45987d57d26ad2b3136d1b8248244e312854b342e89499da397e7923b6b68beae05020fd24ae194c67a4c8e234da356bb7aa61986d67a9d479aa6f4da3db08e7a2c4c5307492e301c05198ef9b487518ec648835abd8eb5865ea7834d332a5ec12b2accc00487c9edd343a8afa8d31b443223be8cce13b8c34b26436811c079276875596291df5aab42ac1ff8df5b90658cc2e40cd770f91f69ae474ca9d44b8af2bff37f2d228986edf160094eea81412e56842002d14afbca10da7c39288ded1708163076b88f0b4b80c0bcaedcfecb4351cf95ca55417f3c075b153e959745296ac1e220ccb308e8ab5d38ff79ed450d15afedef97c0b8cbdff599b252a022399e6900e70f3f58c46753459a38898913716f7245c0ecfe4251a6872065a3bc2a4395f4e95ec92cbb44f131bdea615f02c2916eff098048295af5fad5334d107f386b2552497baec3dc6c1b651cb5282656fe985d61bd932d62ea2cf3790fa3a131d2a452ad60ad254d5394814447440a5104bbd0f1cf071663be01ddbabd1ebd2cc51648e9bccc161057f494c28c82cf9b37e42c520691598ae75fa18a62c2871961f967a96f8beb4af27acb165c16a845ef2b644cd97e2b2d44a4b44569891c5ffcf5cc7cb80c4a9bc370751ca118952bdffaa9c0f913473648274325c85ece1b49a43581312cd5b7cf09aeb9fa2b0d521fc7648691795666a00ccc21e17a792e7d19668c434c7290210f3d5bd41f24f885b863ad1883729b63ac9c24037f080307813129e70f009470ceefa4844306740b46cfd288782146fb534e588bcd332f197f7a083b42d9c5086807b53884bebc0a0887ecadb1a4dd94ce5c9bac934af6c92822b2c2dd4397ac73e4383ac6d0ce334862464647a9542b9253a8dd43594745cbb9e620042b1d50e2d3c2959e94884b9d5e4f4e334421ba521921a423c52755de7a94b6202a7ce7272d640b5844bf268f088284446ac2fca494b7e0b27a0c9670bdd059156510f9c37da199a117c33ffd5116b791a54b5f555286d2482d0a61e194f11bfef68b8282baa0bced5df4fdbe758b7ef739419920ae08e51b2f3bb2bedd012f6426fb33e206985798e702758bb210c706f04dfb1336feef028935e05d714d12049617e720f93a27bb9db0382b9f13a860925674b322a596f5bba12ffdbe29ed696db8687183caf38c34edf911bc9ac15ffc1ed9f54a9dca392159a5a9c7f19599a6f30cd079bf2c8cc56639a69ba1734b5d4754b5465b59d3c38a610083a2ed035e44d50aa3e3a3c471243942da5d22a7a9463189f67abd2085a982dcae1ac22a26b0f4f3320d921d13ef40af55b0be7802d3b29defab1be45a4180dafbf5a93e034407917e61bc3f29615515e9d0d37551caf41e4ab8af5c8419faedd7a292eb5292c1126332a49b1d220dbff1ea6f4545e726c45afcbf5829bfcdf5add88bc1700b97be7371080cbe0c0efc580fd61bda6c1d7e3f30ac6f5cbc2c0dc36fe897beb47fe57e959ff22d5d505f0125fae89b2e4323177bbe5ff700969416ede1127ec253282779adb4fc0db8179870c0d7f28fe0b5a7a5405345a019e7e3237a2c0b7480d0d1605f4a42c5c058a8e2fb60c748b39c4e2f15b7a8125d2f0ee1bb45eef12712b50a91b4240762f56aace7aa9823390d64734bde4dc9e63b4499a5ae13619ad8e219b9d5915a4b3b4dc994a33ed2a2de6a60ad93f418dd9e58ce2b153932a575810c6168c04b672518fedc079120cb1ca66c3c92a7a4e6cc5adadd2ec606fd6210fbfd7303d3d5df02507a7f6086fd6fcaff2d0dca8bfd22255cbea059706db19f8071fd36fbdfc57674b8f4fbcad0378bc1c0d36526e2ff0ed75ed61fc2e24c2dc0f0b3576acb5230c0940ba6d22f8bff0cdfc5a28c56ca30272dbd7c91a5c6e18d3aa5abc53b020c5de8b7287c39fcb440195765312ee7380acff6ff0626b4a04fc35d1aeade60f13faaa4f629d71a24d412e315b5909f7fe833c820e986be950592a0de527e1e43597ac67cbbe47492f5e98a9d13f79110667060740abe30584f1994bfa47cac1d918d5c1117e0cae0e45cacb58e3ccde9cc76989d9ac566462ce7da1f0b23e89714c6419ae7b4bb6dd08ab115cb688c34c8ad61666696b4930ae74efa47a502230a750d33a8e2738900fb7a101916b13c527ceea715f07a16053d9331d76d639c915532a085df4217b57b620f96eec518765982c46f09039a0f889225c656fe4a2befa61566d597417dd2b0f4b838f499f8f03d4b23875c93951eff7aedf537575aa7291d5d1c2eb6b46009430b1d931a07ee1f26d821a21d7860c01cd17f9753c118561ac705ad2a4b472565731097c2fc14f355e85b8abe0c8e872b18dae092e5676ab84fa57e1148b9c089f06cffbee1b697ebc6334b55c47a0d3b9cc159eb4b6dfdc5ab280b7643a5b260a12bcfdfe0f807bc0c383c8ccb04755131945eb2d4e5b196d26f5778566e74031532381e615cb5f251ab14c658b23c2f02402be7233b15c7a14bde062ad08ee06a609841e890b7cb2e1a67af926906b0603223f1063b2991f1297b55d4e7cc5a424be5d6d233866e9ee134b4465b541b358c3174bb5d4c96518962aa8938b60f33a4c14f5706d916520c6c9f71f6ff3a84136456da6594d7e70d20e4e2ac69b87dc3d78611521746a4c132c0848b7785c92f4b998b493c1657429dc5ca202caca15f0661a08ed2e9a6feb7d700e1c000322f4a2b51db78d58d80b7ea5b290e0ac271c14b236cf5434842256365dde0b65782530caa1916f657ae2afa52845cf5d293f2c4a7829ce8f5c7ce626ceee30718729361acc41628d73d3093610e437f7cfb8a03104eee0bdbe00139d2f7b190ae06acc9a24b2cdee58ffc4974ab5043bfafc3f3368c2b575382f7c770e92f3abe946841dede1fcf3032a10d03e02f84055f29bf6287f076fe47aa979afa7427ff87a2b5c62ac5e4dc3c53ed36564b7a0fac1cbad1ae7f8055394ac6a2fe980df4dfbf47f04dd6cf2492b43c970571d708c86b31594eda4dc9bb291515518b62390914189f6f87518a8ecde9594394c434479ac49598dce4a4dd1eca58395f5aa9160476b57039fdd852e090917088049ce6d942d4d2fde2058fa20490cf4162195eb54bf70f11150be4a37e1fcaed289741f08834b0655bac84da06df310843ef77429cfdbac3f741b2283f7705f00c53ced60bc331794696a7853b53385e8767190e0bca333fe519acc73919af608d35189b93db14a72c3a528593fea05162e18806ff5de783ab582bdada0592bc021d697f8fa156abd01a69526fd650dad0e9b5a52fbecdc19d291077598582f2d2af52a8289285d0d9623c8ccdc86c56b8e1c85097f0a38443c288bdfb94cdc8f21e51ec1819add36ad588b4a4efb53678057cb1b0103f8538fa52aad0b42ad9060ae6365028198f3cfd09c72af95ff7f972609ef27f5f00d148bc8acc5af61f3dc26b1fece5930be7e8184b54a9124521484a3826dc6f76816ba5610b758b60dd8fe5994411d17ffc8ffff1ffdebf752138a4428d224f33e626db5c3875814bc7ce53edc1fafa18633a96f39fbe67b9b5cc3bcbc55e9753f3d324cb47d8be7b07abd7aca03d37cfc923c7993e7581e595261b96af60ac5e232e988e2e662470fae1bfbe61839f3d94af0d7eefdd7474c4a5f979f61d3fce99b959392e16c71089ef982b31b63041810887df57feac159e292e647e0b5bd90747b1e35914d707ea19ae749152da157b061220dc34f812e5dbed82a1cf4b86fd3afb84ab55591f847f992a5787f34636e70c8d669dd1d126cd668dd6489d4623214e34dd76c767d494637cb2685a1fbdc7f8000d10c79a5a2526a968924a848e35688b53b9483b91c25a8373b99f2b8bb5128b74706b29545596580941ae0373f2b787c5d2da8c55ab9773fbedb770e71db7b263c76656ad5e8ed28e6eaf1dd862ffd930fea539d55a4bd8b7e20cb6a5d5acb36cd90863cb46185d36c2e8f828d61971c9f1e92040e644e1e744b0d7e3a6218e1c7b76efe4aebb6ee7de7befe1aebbbfc4f6ed5b986fcf3239392186162d4c27342c042ff93ca1d4ed028729a18e3034bf6884fb062a28b8a0ff182601c2fe4f15f7787af4b1163c458a67a6935c55ed1939d69da639f3dd0e172e4e303171916563e38c8c8e80523e4669ffb5e11dc5347a2293b1f6f85ec49310e4b88a801d3e0ea48174aec785139738f2ce118ebf7588f15ec49e656b59a9132a48679d73640ecee5298767a7f8f0d23956ecdcc47ddfbe978d1b5773eec405de78e6354ebc79802df565dcb469336b479a241e81655babfb83541acc61a60522ed0d7c2ee97082450dc06130ca914731e7da1dfef9f96779f3f4712aa323546a355cac200a83d51f92727d7d280f999c13be1c84495e72a04333876e18605004c9e3ca2053eac40569607a4b63a5246e60b55aa15aa9d0eba56459562234a943478a9d37ec64ddda751cfaf0234e9c3885d691d71dbbc13a3dc38c9388d5ab57b07dfb16b66cdac8e8688b6a35c6da9cc94bd33cf6e8ef999a6ea374e2e37c5ab22ca3524f58b56a056b57afa45249a8562a24710cca114511c658babd2ef3ed79e6e63bcccd77989a9ca2d3694bd8376b69341ac44985b49bd34bf3019ce9763ad4eb356eb9f516962d5fcee18f8ef0f1279f902449d10f8784525bb36e355ffffa7d7ce94bb7b16a659d2c75b4e73b9c3a7586575f7d83f7dedbc7fc7c97288a4b2a0701ab28f615c688e163d9f8181bd6af61c7b62d2c5f364aa3d5a052ad50ad3578f5f5b778eee9e78988fbcc22e0a1959d9e2c169656b3c6dd77dece771ef83396af1da15aaf12553499cd38beef0cfff3fffc9fb878fe920fb4acc0f555255f242c8665fdb1efe77d12ac5a1c8f6567e1ffb7f4e7245cf7b8ecf998bf518cfa9181d98b97b874f602e38d266bc696d14093a429376dddc183f7decbb25a1df25474939e9e5cc1dc0379293ffa61f725ef15150818abae2c6986878cb1989e6576628e0bc7ce317bfa22cb7495d5cd51ea4e49a476df955c29a6f28cd3ed59a65d8fd5dbd6b3f5facdb49a556626a73971e8133ae7a758dd1865edd818cd8a9c082a1b551663948b314d8624cea5fea7b0ecc57432cbbb470e736676065d49882a12f51a2dc315a6449e0f9ffbd7fb752e64da8b42609a321f03a578c5d5c055bc0a8f54e0ef2f2142e90e3293b171cb06feecbbdfe1f6db6fe7c4c9134ccfcca07c9a579425cbba6cdcbc9e7fff1ffe1db7dfb98bf7f71ee0dcf9f3a29b5522991b27d29d8e20372923a375bef68daff2ad6fdfc75d77dfceee3d5bd9b2631d1bb6ad61c3b6356c5ebf8177de798773e72fa275c54b7539ab562de3fefbefe1fefbefe1ce3b6f66f79eebb8e9965decbe7127bb765fcfae1bae93b2fb7aaedbb59d4d5b37b379db268c493979ea18c6a6ecbae13abef1cdafb175eb66ce5fb8c8cc6c5b06588904999b94afdc7b0ffff0dfff801ddbafe7fdf7f772eeec79c9845ab0394b14c15ffce577f8e6b7becc9a2da3d45b09b55a426b599d556b97b16ae56ae6e6e63879f2a4ccab131db4d26208d45a4b10610ccd6695ebafdbc67df7ddcd7d5fbd875b6fddcdd66d1bd9ba6d2deb37af66edf61554a306cf3df32c91961ce03263d21ea129f1815cbe6c846f7ef33ebef1cdfbd9ba6d0318c7d9b367c039c6578cb26cfd28174f4ff2d1a1a3c43e3f4f909cae12753e3594d1bab856a645ffb74c07031859da25a9f06c892e14081d3999cc50b76cb991b42dd6313b3387528ae6480b1527b47b5da6e766583e3ecef2b1519493933df2b8df1996244df91d1619cfac8b76c8cee1aa9620a5a441d63af25e4a7b661e52434547685bda4278fda7b596d4187a26a7de6a30b66c84c4e730c9ba5dd26e8724d2d4ab152a958a37a30dbd6f68d03f3394785ea7dba3db4b7d182baf57291d0103af5f5161eb562ae19ee26cafa0b5fc2debc74ae684a0cc2f4e3d9510ec320c5395168f6b01d98a893e728135558b2e21a9c67ce7bb0ff0ed3fbb9b1bf6ec22b3b9e8d5e81b5ad0f0b56fdccfb69bd6606dc485890ba858894b897672824a1b0c293a32dc7ceb4efefd7ff83b7efcef1fe44bf7ed62e3f6958cad68d2a8578894a556af32b2aec9c8b216d6e512ecd859c0f0b5afddcb0ffffe41eefcfa8decb871135b6fd8c0fa1dab59b36d056b362f67d5da652c5f35c6b255e36cbb6e23b7dfbb8bfb1fb89ddd37ef24cddb8c8cd5f9fe0fbfcb833fba9f5dbbafc7b8bcd0ad05034c5c89f8da37becafaeb5710c58a73e7ce12c5aa841cb295bef1c6dd3cf09d2fd36a3639776282fd7b8ff0d1e18f3971fc14596ed9be6703dffbc19fb365db26b2bce7f5987d6daa685933d6ac1ee31bdff832fff00f7fcd77fee2cbecfed266566f1d65d9ba268db11af56a856a3d62c3da95d4ab12bba15fd3a0aeb4d9acf1ad6fdfcfb7befd0daab50aafbcfc323ffb979ff14ffff42ffce1c96700886b1137dd7233f5661de7dd91c2dc5f06cd3e3fe80fe38217f68d63c2e0244755bf94c3dcc9a3a2e7046ff8095bf2a0fbf5074a8a71d7121cc82ad9ed58a5a012612a3193dd361f9f394dd74735534a62c906fa2a6fe0c40019760e43b4e3fd4dafc83443ffad6f5ddecbc83b5d62a7a8e9b808f419dc4a1c629d4ef30c633da18cb6482a11b9cde9a53d4c9611458a4a3541fb60c3a1dd83631d1a7ced8c637190f5bbe3b3053a2b7a273f27e1c09de7e143b31e20f0a3611c0996c2458b9042d18bf2835f280c22a66c47c4a03032dae4a65b6ea4d1ac73f4d8c7ccccce1225b1305c254efb23632d6ebbfd5622a5787fdf7ece4f5cc4458ad4e4e4d68a11c366a8c472d7976fe3efffdddf70cfb76f65f98a51aa5142dae970e2e4719e7df6190e1dfa102a40052ab50ace0740505e1bb371c3061253a733d1e1ecb1331c3b728c0ff77dc407ef1de0dd77dee7cdb7dfe6f537dfe0c081fd281d516f56c852387aec28bdbcc78ad5cbd9b9eb7a461b4d4e9e3ac1fcfc7c91ba43a461c38a95cbd8bc65230af8e4d851a6a727894b11eed1123cf7aebbeea2123579edd537f997ffdf2ff9e77ffa05bf7ae4311efed56ff8c3934f3337db61cb8ed5dcf3953b882b5a16c670ce5f39d2accbca95633cf0e0d7f9def71ee4ba1b37303ada84d472e9cc458e1dfe84579e7f99ac9b82c507d14d64015116c2b147edb018e28ae6b6db6fe46b5fbf1715c10b2fbdc0ef9ef83dafbdf53a878e1ee1c8279fd0eb0a7dc615c94aea1c28a7d1849ceb9f170d5d0686d02d401fd54b885ff21429ca62a43150975ced0b12fdbb9d5664d66209342dee642ed2744dce547b9e34ef473f2a04b392105306af8d95ffbd10e27ca8b8ab629ac1fdc3e486b4dd25eb74899d1b08d2012ab873929a9c6e969259435cadd01a6b91542be426a3d7eb92e519511c112511d67bf32b7f32a50fa12b81db0f77eb5a21d4a1e8f532b234f7c9a3faa97065a81cbaa45b198605938ae80ecb571683a5eafb2220a8a98355d005f71dc4da9b65293b775dcfeab54d4c06efbcf336c618e23846479124e952b071f30656af1b25efc2fb1fec1366e9730ce1f3c21867b8f9963dfccddffd35db766fa0deaa303731cfde77f7f2e8a3bfe1e1871ee2378f3dcaa18f0ec95466808ffeadfda106a534070f1ee4f15fff9e477ef9388ffeea773cf2d06f79f8978ff2d02f7ec3cfffe5611efae5aff9e52f1fe693639f10d7801c5e7ffd6dde7def5da23862fbf6ed8caf68d199351c3cf411599e17ea1e6118864d9bd6b37c790dd783031f1e902c9bdea2ee944818ab56ad64e7ceeb78eed917f9ddef9ee0837dfb387af428fbf61de0bdf7f6f1d4534ff3eaab6fa023cd4db7ed616cf928c6e5624dd78edca444313cf09d6ff2cd6fddc7ea4de3c44e73f2d8295e7ae1651efdf56ff9cd238ff3f8e3bf65f2d224684957218cae387f258c004b667becdcbd9d07feec9b8c8e8ff0da1baff1fa1b6f7061f2123d9391b99cdc598c1324bb383181b156faec9048eb61f5be22967e1670fd5d982fb2f32c7bdff4cf9807af837271fe2870719cd2ff76c87722ccf89da057b99429d22a1984a00f0dbcc92a24d6449af6ddb78278394498c1aa5f8640376263b80aa689679ace5ab234a53bdfc1f5322a4a538d24f070204c1037936e96d149530c8ec6689366ab898e23b22ca7d3e96272431449087b55da0e3a8675847d4607c1497d70daaf4aa7e841565fc9fe97a6b924b342f9a460fd7a9c2be5f05c04cadf0cb670e9f26f05325ed262eb84765404bb6fbc81b8a5b870699a43870e893e0e99c7dce61867d8b56717c9a8667e3ee3d8f11338a531ce09b3b439b9c9d871fd767efa0f7fc7faed2b8894e6c8be233cf4cb87f8f9cf7fced3cf3cc307fbf671f1d2250e7d7494fd6feee3c9c79ee6d4a9532449426e249d4014c5bcfaeaeb3cf9e4b3bcf2ca5bbcf3ce013ef8e0230e7e789403fb8ff2d147c7387af418cb962de79e7bbe8a065e7ff57d7efbdbdf3231718938a972cbadb713d535a74e9de3c8914fbcde4b76100e87b5393baedb46d2525c3c3fe5fb2cd92029fc272d3ad6bcf9d69b3cfef86ff9e49363a4dd1467a0d7c9e976322e5eb8c45b6fbec9dc5c87751bd6b27ed35a326b4465e12c3ab2dc7bdfdd3cf0c0d758b6a6453a9ff2ca4baff0dbc77fc7f3cfbfc09b6fbfcdbe7d1f72f6ec455e78e925de7aee1dfef0d49374bb9d612d15c6e62c5b3eca5ffcd577b9eec66d642ee383031f70e1d20456437da4c1864d1bd873e30d586bd8ffde11de7eeb6df2cc1412a6e0df17afd3ec83e05a502b045a710cefbc025e964be9be05df08284d29c08c78ade0b7f0914fbf1dd25568b919b4a697a5a479ee0932589206f5a665187ebfa808e49b2b324d851241ca384c2f27eba628e3a8aa584e020ddd6faca3978b3ed32a47ad51a352af80026320eda5905b6a714574a2500c4b60807d5ddef0b00db2a0fefd5e4f318c75255080f6d6f94e37a5ddee4a8810a525e15890382ec32cff6b02e98b5fb19d9305c74afef346a3cef6ebb6420cc74f9c607a7646162e0d4e3b0c96a41673c30dbb20863367cf71fee24551d178a62bc14172bef367df62fbeef5280b6fbdf9360ffff257bcf0e20b9c397b864eb75b487c9f1c3bcebffcf32f78e4d78f71eaf4197414614c2eb36b1df3731d6667dbcccdf5989d4be97472ba5d43d633f47a9666738c1ffdf54fd8b06105278e4df0f0438ff2c9c72770442c5fb99a0d1b24e3e1a12347999c9ac606fcd0e094a5deaab17dc756a8c0e973e7397bf61cda1f3f0c33aeb5e6e2c5097efbf86fb970e102ce388925e134918e505612dccdceccd0ebf668361346c7c6240b260e6333366e5ac7030f7e8365cb5bcc4dcdf3f4934ff19bc71ee59d77dfe1c4a993b43b1d32936381175f7e99877ef9102fbefc22ddb45b788b041c4fd38c2d5bb770d3cd3750a94a9adc1d3b77b063d70e76edd9c91d77dfc95f7cefbbdcffb5af8233bcfcfccb1cd8b75f929fa24b74f3af89d3a113615c5d211d0e53f352e000bc4f72b996e2d9b212d2eb260111c2b42cecd6bb2629a5b168d23c9745da374f793d6a999ff441ae85b7a8122ee1ff5c1644bda571c6917653ba731dc81cd5a842e273ca405f47e0b422f746201769eaad86f8d7012eb7f4e6bb28e36856aad41249ac14d26f2ed6fc3e2cce30978261111b84b9a6b965626a9a4e2f95370e184a0686a9f4e4e7095f54bd8bc3f022d0ebf5185fb98cf59bd60270e493c312c92676e8d8a113874e606c7c848d9bd7818223470f333b3783d2cefb14a6a479873befbe952fdf7b1b715571f8d0611e79e4113ed8f7019d4e875e9aa2234db556a356af114531f3f31d1c3032d2a2d6a8f49db59d0315a1548c755a2ca4cea7bb4553abd6f9d6b7bfcdee3d5b98994ef9fdef9ee2f04747c0ca566cc3864dac5abb0ad3818f3f392a5e10caa2228bb529c6f6181b6fb1698b30d60f0f1e646676962ccb644bec1ddab552983c677ebeddb7ea18394da29cf884a6bd1ea3232d9a8d061a30b997687044b1e2cb5fb9931d3b36d3697778e6a9a778f2e9a73875fa14ed5e8728d6349b7546461b2c5f394eb55e25b719ad913ae3e323dee95fb68f4a495ef16ab54abd59c11aa8d66adc77fffdfcf4ef7fca8fffe6c77ce7cf1ee4eebbbfc49ab5cba9d512d6af5f4b9a66e499290912fd45a1af0bfce38032231c608ae1ff2141701093e573d8362b640e955203a7cce41e4766246381f30f8af4bd50027708890619b9df2ecf84af28695aefd869257d6c67bec3fcf43c2a9768ed89f6693495b8ec3b9f62c128c89d25ae57185b3e064a42cae5bd8cf6f43cda40b3daa01a27444aea08fa8730b18b4d70f8becc3087a5cbc5741202d2dd4e2fe3f4858ba4c6c0503ea370df17cb34ff75a13f3ea27ceff5ba6cdbb68de6f21897c28183fba8351246c71b8c2f6fb266dd0ab66edbc00dbb77b06c79131c74ba6d6ebcf106f6dcb88b46a38a31194a657cef2f1f6464658df66487471e798463c73ea6d96c70ddf5d773d75d77f1e0830ff2c31ffe90bffddbbfe5effeeeeff8c9dffe2dfff0f73fe5a73ffd5b76eebcaed02f39afa211bfcfe0a520f311c51177df7317dffed637e876144f3df52cafbdfa1a58885484358ead9bb730b22c26ede59c3b7f9a6a356264a4ce8a95a3acdfb8922d5bd671f3cdbb59bd7e0c32989f9fe3965b6fe2f6db6fa5564dc4cd484bda57adc478222972a5195a29acc93079ca96cd1bb8f72b5fa1de4a387dea22131726241158a4d9b8610d5fbefb0e1aa315f6ed7b9f679f7b9634edb16ddb36be74fbed7cf39bdfe07bdffb737ef0c3eff1e31fff909ffefd4ff8ebbffe21ffdd7ff737fccd4f7ec49a352b514a5409ce5922ad3972e408fbf67e845210c58ab51b57b17de766366e5cc7aa55ab6834ebe858115715f73df015eeffdafd42e47f743b2637a8ef1cdea02fb2532eaeabbe0a30d857faf822a07ccc8ac0348db585f7043e48b5190a5fb938042bbfb75508732a1620a52ee3a719ac4ace3a6ce6e8cea54c9d9de4c2e1d3a8991e2bab4dc66b0dc9f8e6199575969eb35cea7638d76ba3c61b6cb9611ba3cb5bc44a337d7e92e3073f269feeb266649c958d96a4fbf5dbe6d0a8a59c71032fecff95fb8619ee62e050581d71ecdc04af7eb09f0bf3f3e449446bd9283af1fa3c9c0c69a86be9ea0a50579e050f72e3659ab804a83e43bfca67fbb8e4fb533cefe8a4f37cf72fbfc3ae9bb730757e96975e7c919b6fbd4998e24dbbb9ed4bb770e71db773d75db73332dec0a5d06c34d9b3e74676efb991c31f1d626a6a82952bc7f80fffbb9f12d722de7b672fafbffe1ab7dd7a2b77df7d375fbee7cbdc79d75decb9690fdb6ed8c6e66d9bd8b07503ebd6ae61ddba356cb86e1deda9947d1fecf7adf46a9160b9c7a194e49fde71dd76feddbffb07366e1fe3cd57dfe7b78fff8ea9a96914910463b18e071efc163bf76c62e6d2347bf7bec79ad5abd873e36e6ebbed266ebe7937b7dd7e1337eed9c9aa55cbc141bddae2d65bbec49e3d37f2fedebdcc4c4f9378fdbac9734c6e505acbd1bb2844cbb2ac59b3921ffce02fb9e7ab7791a78e175f7895b7de7e8fb467a856626ebe6917dffed67d4489e6f5575e436bcd5d77dec5dd77dfc32db7dcc2eedd37b0e3c6ed6cdab689f5ebd6b17acd6a56ae1867edb60d6cdaba81a3074f70f2e4c9e2a492c512259ab1f13176efb98148c3fc5cc6a91367397af418278e9fe4f4a9d3ac58b192a41651ad5718a9ade0c37d87999febc8a9a921fc09187125280494ab2e7dbabc3c38bfc32b5c56fa0d1afa2877fb2f3c3fea83ef9b70543970915be6a76731bd94d1568b56ab499ea5649d2ead2461f7d66dac68b5a821cf2c0641202beb6395127b8a06bfa82e0282b73e6f8c7528449763522329381d12cecdeb01415602eb244f72cfe418657d26bb2a9178bed3eba6643d83d691446c4718a452fd15e18b02e705e7f31393ccb733749440290e9f83a0658670e6d45f2f974f0d4179adf06871e5d2d74b2e0ed6fbce0e97c5a50c797fbd5661dbd6cd00d4eb357ef4a31ff0e77ffe20df7ef0eb7ce5debbb8f9961bd9b5671bab37af906dae860debd6b07deb46562e5f2ef9e8716cdab2994a3301a039dae26f7ef213fefac73fe6fe6fdccfae9b76b276e36a46474651197467ba4c9ebdc4f9b3e789a2847aab46a3d528dccdac1306515851115fdf5abdc29f7ff7cfd872c33226cf7579ee99e799b83089b231dac5281b51ab56d9ba7913188863f8f33ffb367ffbb73fe2af7ef020f77feb4eeefaca2ddc76f71eb6eede0c31a43dcbba751bd8b2651dcb968f333e3e2ad29d3364798f2c93f3dbd69fc4c98de4eede71dd76befffdef71cf3d776353c32b2fbfca4b2fbf427bbe4da42565f5fa756b492a1158c70d37ece2877ff503bef1ad6fb0e7b6dd6cdeb28956ab89e964cc4fce70e6d4093edcff01f3f373546a151acd3a8d460d6b8d571728962f1fe5dbdffe265fbae34bb43b19efee3dc8638ffd8e871f7e84471e798cdffce671fecb3ffe33efbdfbbea04c0edbb6af65db755b24087061701964375f245c4e700170f830701e1faf96aa16d060e931ab24068158d8fbb4037dc1cafa7ceecad3f6a09c3b08054f2b5d2bf76a01a71ae8b27711c83343d6cbe9b67b64dd9448692a510cf42dcd32048a9eb5b44d86d19ac6688b66ab2192a383f67c97b49752891292482220475730e0f42130934f070e85559a9976879e91a46aa1babe11c9bf63e835e5e15d38c48bdcb0d48d0af9226c3f8b6dc0e24585933757d0df5e2d586b59b96219dbb66c000bd591845beeb89165e363d46b75ac8313c74fb077ef7e4881184e9f38c7cf7ff60b1e7ee8d73cf69bc7b878fe02796e688d8e4004d6c0f6ebb672eb1db7d21c69d1eba57c7cf8135e7ae1157effbb27f8f9cf7ec9fff7fff35ff8cffff9ffcd9b6fbc4994286c1bdedbfbbea41bf1ccc921fa2915d4ccda72df7d5fe5eeafed862e3cfee8ef39b0ff20d62a9c8b502ec6e68ed52b56b27dfb5a70d06a36d973fb1e366fdfc8f8680b93a69c3975828f3f3a22c73123387ae428bffad523fcf2978ff0ccd34f333b3b4b1469af0fd3444954b81f259584f51b37f0e0771fe4277ffb13eefdea5d9c397b96871efe158f3ef618274e9c208e2392580396152b96892faa52ecb87e076bd7adc55ac7a963a779f3cdb778eaa927f9d93fff8c7ffc2fffc8fff2bffe2f3cfcf0c39c3d7b0e32387de41c939353349a4d74a459b66c946f7ce33ebefbddefd0683478e6d9e778e8e14778f2e9a779f7bdf7f9f8934f3876ec38278e9fe2d0a12332c11a2aa311cb968f4ac45c8f8465a9e9df1cae11950baaf74265ff0bdf2b4dd1431d69928a084361ebee7c1c02e5fa817e820bd4e2443a08c55d2187d1d59c087246221bf5ba3dda736d4c6aa84415e228012b61e0950390bcde3d63e859838b1549ad4a9cc8d1306b2c9d760793e54532b520da6b1f3a2cac528b4b4a9f16425d62359f999d27b316e3b7094a0fae4a7df17c69de3708caaf3dc36529ecf0b506c679a53270df67833ccf59bb7635f5b1846c2663f2cc0497ce5ce4c37d0778f1f99778f491c7f8e5cf7ec98bcfbf54d8c78e7f7292dffdee69fef0c433bcf6ea1bcccf75004d9e1bacf7cb9d9febf0faeb6ff0d8e38ff3cb5f3ec4bffcec5f78e8a18779e4378ff2fc0b2f71e8a3c3c451c2f6eddb5131ecdbfb11efbfbfaf68976c7f42ff1cd6646cdab481ef7defcfa9372abcf1eafbbcf4d24b645986d671b1c05963d9b87e03d59104d7130f8f8b27cfb3efddbd3cf3d4d33cf6eb4779e4a14778f7adb78b2939f6f1319e7efa699e78e2499e7bee39ce9e3b27d66f1f3058694dbd5167eb8e6ddcfd95bbf9de5ffd257ff1bd07d8b47513070f7dc4638f3fce8b2fbfc2d973e7fb3a586447a023c91d4f0c274f9ee0c9a79fe4e1871fe6e73ffb39bf7ae4119ef8c393bcf2faeb7cf0c13eda9d2ebb76dfc8da751be9cef478e985d73875f2344a29c6c65adc72cb1ebef6f5afa294e5e9a79fe2f9e79fe3c4891364595e1a7f6f84f2b60714b427bbcccc4e165be5fe491c19dfa5b0f2b3c352747035f470398953be970da0a7d7f04489369c73a84802c0849dca70580de5423b966acbc2086aaa1437c221bc490fdcb1a0e9c2de4d6e303d43decd5156518d2bc451df728eef440e74ada1e72c71a34a7da4095184758e34cde8b6bbd8cc508922d1212d2241f5c5fbc1efe4723f7a4d599f7975a0989bef303d3b8b71d63b36e3d7a88161299e586a1a3f3b0cbfef2acad576f332200b54ccfe770ff2cc932ff09b5ffd8e5ffcf3af78e4a1c7f8edaf9fe0a5e75f65dffb07b0a985442cc717ce4f3033d3a1dbc9e8cc77b156a19558c215104570e6f4699e78e2099e7afa295e79f5650e1f39ccdcdc1cd56a95ddbbf7f067dffd0bfefac77fc3ae9dbb78efcd0f79f8a1df303d3553b8fc14edf30c73b455e72fffe2bbacbb7e8cf3c767f9fdefffc0d4d434715c1155910f1da750c47185231f9ce495175fe789df3fcb230f3fceaf7ef9288f3df604afbcf43a6fbff50e592f95d758989898607a7a9a2c4d999b9bc3188331161d47ac5eb38a3d37ddc0037ffe003ffcf15ff183bffe1ef77cf5267a79cef32f3ecb23bf7984fd1feea7dbeb51492a54ab153192fa389b69da938e68f8e8a343fceef7bfe599679fe6fd0ff672eedc392c8aeb765ccf7df77f9d1ffde86f78f0c13f071bf3e413cff2e20b2f333535cdfcfc2c9b376fe0ebdfbc8f8ddbd6f1fe077b79fdf55768cfcf11698535467c13ad452b58b366351bbdbb15163efee8134e9d38352078f4e54cafd45ca4f4aded43bbae6b2afe2d03ba4e51bf5d2b886aaadfc0f00607de39b3f49d0fd18717822c1280d81acf389d1889b4f21ade81b8ae4b43687760d44ec9bb173504155d7460724baf9332737196f39f9c259d986745d26079ad41152593174518a5e83ac3c5ce3c13699764c528eb766e65d9ea7174e4483b1947f71f61e6cc042b2a0dd68d2fa71e494839852b62295234d61593111864d8c597b7f357372162399f989ee5fdc31f737eb6cd9ce9a1ab9a91e5a3221d1459f8ae0d2efbfe45bebaecfd1e0691ae3f260bcbc2ebcef5afc9d8f9f729c987d4ebf5f8e0fdfdecddbb9f63c74e73eae4792e9c9f6266ba4daf93d3ebf5b8e7cb7771f31dbbc1c0b34fbcc2c1834788a24a81804e412febf0952fdfc3c8689d3c4b999f9b63b439c2d62d5bb969cf8ddc7acbaddc71c75d7cf5abf7b1fba6eb69544779e5a5d7f9e5cf7fcdd1c3c7703648201eac44c456daf2fdefff25dffaab7ba9d6621e7be8295e7de575ac855827c2e0bc1140ab88f9f61cefbff701efbdf73e1f7e7888631f9fe4c2854966a6e769773acc4c4ff1c003df62fbeeeb2087975f78954f8e1e278a2a98dca2a38495ab56f3cd6f7e9d071ffc26777fe52e6ebe6d175baf5bc3f8ea26d524e6e323c73872f423eaf52a9bb76c66e7ce9d8c8e8c71e6e4e9e28494b539e3e3a3dcf5a52f093ee59624a9b079d3666ebef9166ebfed0eeebcf32eeebefb6e6ebee516d6afd9c0c9e3a779eee99778e1d95799b83809807119dfffe15f72fbbdb79034128e1d3dc6c18f0e8a24ec1c71141147118d5a9d8d1b3772f79d77f1952f7f995a5ce5a37d87f8dde34f73f4f0715c5e667e7d5a12ca5f5802be5c7371576b01f28c5b85b60c836fc712dff6dfe9c1bb1929ad85b93a8546c20ececfcc52ab541969b6c83389df3b5eadb27bcb56568f8d103b23db75c4371706e93230c9e273a95dceb9854cb378d44ade0893397af33d264f4f72f1c479dc7487959506637195c4f9c66b8d8d345d63b8d89e67ca6654568db166c72646578ca0b5a333d3e6c8bec3a4976659531f65556b8c9a92f4bd0a1ff4638851146d52142b8a7c2e75f0aa264c98e6a5e9790e7c7c9c8b736dda26c5d514ade523623dd7fdc0a6d702e5fbcb030d8bcffed5d47f35f72c0565cd46bf1eb9a8b4c6e486d9d936bd6e469e81b3a21fd43a411361f29c071ef8365b77ad8779f8ede3cf72facc7951c704c456303f37cbb6ed5bd8ba6533d5a4c2b62d5bd979fd0decd9b59b5d3b77b3fdba6d6cdcb09e566384c989595e79f1751e7ff4f79c3e751e6b355a451e195541d4d6645c77fd76feeea73f61d9ea16a70e5fe0173f7b888b172e51496a9e38a45f72025293f65266a667999fefd04d33f21c6c60c84ea3b5e2fbdfff1eab37adc6cc399e7bf6454e9f3a8756095a27580737ecdac5dffce4fb6cd9b38665ab5b349b55926a84d6603348920a5bb76ee1965b6e66e7ae5decda79039d769777de7a47b6c85ab684d3d3536cd9bc9935eb56333236cab62ddbd9b56b17d7efbc9eadd76d65c386758c35c6497b8683fb0ff3bbc79ee49db73e6066661e1dc55897536fd4f8e9dfff1de36b4700186d8d313e3eceb2f171d6ac5acdba356bd9b0763d7b6ed8cd1d5ffa1277dc7e078d5a83fd7b0ff0e4ef9f66ef3bfbc952d04ab2acf625ce70bc707128c534ff74c56fff9786c034d5f0171ea49e50e3f0f5420311a445cf343d0b12f7410769a74b7b669e6a9c30d26c897a314d19abd6d9bd752b2b4747889d45797e133c6f065fe98dc1c547cf97bc3e74c1f67c80f29dc3e639592f93e09e9d1e91955ce5ca7faf4b27698c82cc9f2ca9346b34469a125ddb1a3aed36dd4e9b18453da9888bd24098fe6170be047dc752f75d1d487bade408012836e6527779d83ecddbaef5fe2f0a423a82004acb3132f14e504084d615b4aea25c84b31ae5e2a234eb23ac5abe96f6393872e81ce7ce5e20d28977e9f56cce494a93e79f7e8123873ec164303e32ce9a35ab58b97a0563632324d499383bc7cbcfbfc1a30fff8e279f78964b1767d0548854658069863c2ea0b8f1c69b58b57a8c74daf1d20b2f73ead42974e44fee38002d0cb320308552318a188cf4474a8cb33123ad152c1f5f4bf7a2e5c42767b974719a4857d1c428176133cba64d9b58b1a145422461c3e23ee5ea2a2c5f3fc2ba8dab59b96239cd7a93d9e9598e1c394a96895ed15a0716a62e4df3c8af1ee3c0bb87884c8591c608a3e363b4ea2d625363ea7c9777dfdecf734fbdc4934f3cc781fd47989f4fc189fb94b58e6ab5c6e8e8a8bcdcc0b295e3dc7bff3d7cff7b7fc18fbeff57fcf07b7fc5f7bff77deebff77eb66cdac6dcf43ccf3ef53cbf7df40fecdf7798b46b856132681b18d6d52d8480f5d75ac2b35702552c6497d7710e837fcedf1f9e2abf3d5c77fee8a4521a632c7926ab8456c25097f62cf1750f33cf403f25d07a9120c4c582612c2e35b4e7ba5c3a3bcde1b70f73ecedc38ccec30de36b581e55a83a2b62aed6a45a3199a71cb878960b2a67e31dbbb9e1abb7d21c8d31dd794e7f748a971f7b89da64caed6bb7b1617c0589b554b42289fadc3e70754a9225438d2f6fcfafacd37480c212f3d1f1333cf2ec2b1cb838c1d96c1a96c5acdbb61e5df1297983656db88acbc0d2ef1f9e5681e149580c86276ff1895efa3ac8abad93b0fec192d86fab8caffc952263eaa85463beff577f8eb186a3478ff2cebb7bc97271d5e883c5b894b80237ddb88b3dbb77b17efd0692b88a73e2403f3931c3a9936739fcd1112e5ebc48b797114509d6f4df5fd62b39673136e39bdffc1ab7de7a0367cf9de7c5175ee0f499f324714398a5c3139d9205b0840bd619099a11bae6c7a73952e5af7ff403e6e666397efc38fbf71f6076769e24aea1548cb1861ffce02ff9f677eea5d39d95e01b7e410fdb5b632c799a313b35c3e9d367f9e4d8710e1c38c8a5894921d22044003a82ad5b3773f75d77b272d50a7414d1eba64c4fce71f6cc794e9c38c9cccc2c333373a4bd1c1dc5e0442de15c4aa51af17ffebffc0fecbef9fa01fc510e6ceee8747acccecc71f2f8694e9d3ac3e993a7f9f8e3634c4dcd608d43b9c88f911f663fcffde4768bc330ce5d331446198161ec0fb85a5c1f307096f0d0abefcb1232c882820f0707608c6788283016e534ca2ada93b39c3f7e86aa8ed9b8763d89d6a47373acaf37f9c1fd5f67f7c675d46d0e564ef80c4b9ad60de6e70a10dae49cbd0cd3cc2d2e73cc4ecf73e9cc241fbd7988e3ef1e614d56e186f1b58c294d05890f6994a6ab6022eb71e0e259a66a8aed5fbe851bbe7c3371c590cecd70e2c0715e7bfc65c63b8a3b375cc7ead1712263481424f1a03e5390b59f21113e3bd334241cfae414bf7af6650e5e9ae45c3e855a96b07efb7a8845351318e6c0805c0182d8ce6551b204aa3cc88bc330022fc51c97ba0ed2893ed30c2beec28d05289f025618978e14add116ce39badd0edd4e577c2795126f034039875306c8892245abd964c58a95542b55ac71a4598f4ebbc7dcecbc188c941369507b2951a9058a78391924e9206ab598344d69773a224122a29fc5e19c92ada4cf1f14ea927913572139aa80b45659465a4db08e6eb78b738e2ccf89e22ac64a28b8cd9b37b061e36a9ccac87323927941ac965eaf873186f9b979a6a76668cf77e8743b58bf6b91f90a782bb83ad26a31d26c1045b104b1697799999da3d7ed214a4f471c27c562a594039593e519b7de760bb7dc7e23ad9126b55a0de514599ad29e9f677a7a86a9c9694e9d3ccbd4e42cedf90e596670d6a155546cc19d3f7bdd1fdfcbe0ca223877cd30c434f19fc29501a917b9bf4f6d327e57c73425cf8fa4109180c2180b56a1aca23335cff9e3a7899566c3ea7554b4269f6bb3b135224c73c33a6a2e475979bf0e2e6e5ed73bc834654c04577d5b9dbb3cd3b4a9a53dd5e1fc890b1c7aeb2067f71f679da9b373642523282a5acee56628da582e663df64f9ca33d5667d7576f65c7add783ce48a7a7f9f8bd23bcfbd41baca1ceedebb7b3b231d2cf23ac9c8f6be8dba09447a4e25289692ea283f0b038039549c95dccbe2327f8d5732ff3f1f40ce7f269a21515d66f5f8f8b7cb8e04fc13409e27f985edf86259154055789a561b87f4bd5b5d475904e0c4b9a2c3a46a1e5020e9f74cac9ff14471d03d314294fbe35b2f27a86acb52652910f2e0cc61809d8110e317866310cae8c98cafa283662e81186decf3429e04f74385005f1c9f7b24094ef543863887df49b388ec1a759ce8d214e12c04aa6c144931b436e24f2925232c67996617d70ed70cc52b2530a87ea2ff492df2aa81d0a05b3128da1458e133baf970dc7fe20c470b55867482a155a230d926a855aad8a06316864192693134b9d4e4a9605bd5ce4d3606bb1e62fa1bc1c9ef9320ce3dc354381d3327701faf336b835163c2be382e061b854669a412cb1a5ed77ee773f1ad9150749b3333dc785e367c05836ac5d4f4dc7a433b36c1d5dc60fbffe0d76ad5b4bd5e628274e87c2561c4a09ce3a245d890aae46453b9c17f72f93c2b748b96a1da667b03d43a2629228f26e1661fb224ed3d69f06caac25ae5669b49aa24fb50ad3b3b4a766898ca559a910477e451e7239916bfd72ad604b675387afbb908532cd04b9bd8e2c6cc1aeacf3b9320ca040c9a81588ea3323e63581927e3a1f22ccf7d3dbf7868ad7cbf9b97446dc68c2bc16081d7e7c000bedb4cf6b13e3acc6e490e596343585be2f84eb524530dc7e29961a15164569b3731a88b03e677d18d97e0b06c19562320a84bae44e09fd276360adc485b54698bdcd734c66487b3973733dba1d43d6b374db29dd7646dacd3139d81cb01aac97965d842642a3512ef245a36ce4efe98f3f4e619d0617a1552c63e224b56cc155c49f0588c852c3a58959ce9ebec4b18f4f73ece8694e1e3fcbf93397b874699699998e304c3ca3f419367363fcc918a9ae5cbe7028acf28267e1c521379062881e10ddb8f84efa677c350e44fc2b5ca0fc3c96b6d27d5aea4bf820d6701d47e273abfa07437414912489080d7e37a20ba6ed553fa5f74b1b9c2c754170f02a8e45986678da6132310275db5dd26e0f6d1d49148948ebdbec90ff258e664aea2c71ad42bd59278e85384ceac83a29098a7a9250d1fd387f0106194ae84c19c2a4885ea15c4a98572a83609d23cf734cc8b1ed04b185404332fa4f0fe5370eb764616bfe75a1efe5b60835951a679df7620811ec65760b0953105d161a61c422092afcdf053e7efe1ca6278a30e7e127104a6884b89d95ea70fdc0d4a51e4891a679665fae75f0273ce1e82f90816871dea2ec94303d2228f545187b6074b118583c13947ae4fec196f5c179c383f389b9c4f01962b57ac9a5503180b5d2673c13c6456063f1067031e00d5d7e6129330beb8fa286b068c365a8695f0c0c70e97e194c6d41f177c92615b72e794701c23706c75e071f72273b20a514712cf17bfbcff9bffed4dd02f069baa5da410a5e84690a3827cad634f58183bb293ab724ca47db2eee934949ada56372320c512da25a4fd09188b326b5a49d8c5869ea49421c2961539f9a9b0cb2a4a599e7201823e93b0591fa67b7af66723e1ff874ef09abf3e702d7504d1f15e5bff27cf519715f4a186498be2c20983ee8058a787981f27a2bb1f86b7137b19e09f902258b7059aa59a4502c01a12c6ca7525a24c742ba898439f96790b0be0b1686c1cf43a0448a16020c7a4b7f6ba9097d0503035f14b2b897bae4bb60816600c71779fbbf010cd3df302d0e75fc7380b2141a3e07466a8dec9623ad49fc0944896d501eb9fee21576a8aea4a673e17369f75a30cd81e1f78cd0584b9e6564a9e852b47154949663ad6115953d2e398eb6cdc934545b75e25a847539363774e63a7467bb54a32ad5a44a148805c0e7ddb8125308ea82859371a512b66f8eccfa20a4fe551225471608df9acf1dc2eb8430cac4f1d9212048b9946140d75a625203174b8ff42dacfd8b65996d210cf9013af9bb78593833fd19ea339f421af4f10aa478fd5249900908bef03d4b9741c639fcbdafadc4d706cac0c4852d61ff8ee179e89762a75994c0fbd0f8638efd7a07e1f2d8d21f0381056d2e5dffe380e1967958ac9b43d796c2efc0e0ac3f63ae7d742a3cd3c4c822abb51c910e3b4aa54a47a6bd6ebacc789df351b73cc8bb05210ba639009e99586bc94d2e8af0dc10a3c597cd3a710eb5de9ae46368764c8e4de4944d5c8b25606d96d16bf7c8d39c4a54a112895e4179456b7f201c0bcfa09606592116377f4eb85c9686b0c594be747b12c1c6057703ad6540c376e90b81c01042e4e72fea3d02c388a594df52f717d4458c4183782c525d90ece480b96c35cb6a91c5c77e786ec23d01b1c348f747bc34c745594272bb0c0cbff36aca52cf0f2b7dbfd829f3f8510a8da8fce25696c4cb25c0a2f3f8470ab2180596d5b7ec17b37e8d631c9e935a253b9f03d091b87121cc502931046a1d56ad8534b21484bbe47e31d605321eb8a1dc1c6b2c799693a71936cd49d0d47c74222140e1d0c65a526b49b1e85a42a55987486131e4594eafdd85cc52d1b144372a314ae71bb5b86e6198c4168eece033c5140c92a5926d5696e7182352a7d2a2e7088bc9f09b3f2f28244ccfb116f6e08b85f288f4677678dcca10987c20e43231f7a5c16142be5c016fe8f181aa87cb820b61ea166bf81230fccecfb310a4769fdb3c4828c3f72c0e0bd50503aa83f2d6a7d4d781f159a4940f30fcd702c218fb1410c66dd19e2c7a5140dcab06773f05b56b55e47eb25ea88ba3984ab582d2e2b656cce312f3562ca8fd2dc0000c489a81db3b2b59fa509a2c3574e7bbb8dc50f3813642bf9ddfc63bad24652f8eb856a5d6aa17dbab3495d34426cda9a8884a14f9c68ab93fc4d3a48484f2c13728444fbe4676235b17794fa040e54f0a58274ed0712c965d81c507e8d3820a84a6c4713eb47f31c961b87cde10466e7804cbd28cf69277d10ecf30b5964007c34ce35acb52fd96f72e5186db37dcd645def359cb82f7f8722518aea72882794bfe2cee3bfb27004ffb1e6bc32ea0ac9208ff9569466b913a45bd28749f4411f55a4dc2fe95763cd0d78b2fa83ba8ed06e64776400b24cde04e24b52af2ccd06d77d106aa518c1c7e1382b3ce619cf3590ac57257ad55a9d66b45708d3ccfe9b43be0996eacfb8ed44a2b09e0aac4e56770cbd467340bc9fdea41c624f44e63c589400824d2d7be2fb856f8140cffb3c0f0b69312a3d6a5f3b48b81120a2fad35a2a72c249ecf00360458182a9f16943f1d72b54ced6a6181d05b96f84a30cc1c978261dde77051088a0c33e92b95ff4dc312485ae606e55b86c745e6c34b9a1691342b0951a451ba8f8b4b81523e754fc13b94cca37fe982654e39d1573aefbb67b21c9b1922aba8e90a911c5c2ac0794da471e23252a9262489e81450d0ebf6e8ce77d1686ab51a91d2deaa258dd3de67d30d9d60f83cc159445cf79633fcc00cbf4f9798cbf077ff5b812b19dd0a04bc5a89ef1aca17095f74fd9f0586a5eb72c1936540cc25f8c57f3be00d71214e6f98d58171294db58cdda0c015764ce1f041a435b1f69e114e16aaa0ab1e1623bcac507c12be14e26af9b60d3ce121304c6b2cbd7617d34949d054a318edb9b40e59df1ce4dee5c8e068b45ad46a35b1581b43b7dda133df463ba857aa445a4e2d389f71efb36e4fca83256e477d0831f6caa3ac951846069cabc3ea527af65ae0b2cf5df6cb7f3bd08b10f030317f9e305cff1751b4f6ea842fa22cb2085caea8d2dfcb95e2fee101fb6f02867a5df25c18fe365c57c1a019c079dc2a5d53fe68adb5168d234151d39a8ad268e74a62df20fd6b8ff74555ce1f112addcd624c53ce18cba907671ca69362ba19b178a9155b09906c7d466b7aced2b306a315d57a8da41a0943cd0d79af47d6eba191ed7dacfb3acd61e78f8111fb1430a87f1804ad446af2b636b4c39fe6e80f54285ca119c388ff27f8e3004540f42fa07c81f0c5d6fec70a81d83dd35a40fecab3a741ef8fe1fbc2563c8043526038d95b120123b52a63f50635a5241164a8df0dbea70c8111f73d75fb9ca1e032e152601cd64acadd6ea70bc67886a7519e532b1fc0c12a89d6deb11936d6d4479be227650d184367769eacdb25d19ab83002f9265ad1b616ab44996b7d66181c4cad2052e25ea47d24678a41f77aabf0d8652048058b953f06f8bc98f8f0c2f059cbbf160cbff7f32cd7527f216d5e41bd51dcebd52657239d86f27942d9e5ea72e5f3833ed5046167b11e9519258bd09c5cefc75808b60aad25407a642d63b53a63d52a15eb880add7a78ba2f68957b17a44e60c1167e4034734a06cf5a8b31965edaa3d7ede28c25892483647f6b2b086415f4aca19da724cd1ae32b9611c7622eca7b8699c9694c37a556a9124771b185d6fe5451d03b84e62f3670d706e50efae1f11266842b725b83e8f7821dadfc5e3b6449c3f73520eaf0a40d3feb4ac7d78691ee6a1070f8be2bdd3f0caa4484575bfe0457863fb6711a9ec3c54a60c27f7c201252df50a6c19fa59733e9fd2df780b5db05ff5924b286ebd7e5c2bd457e738b7286d17a8d66a542642d512903ad673d526f497092b15be8d5133e2fd8cf16446f2d2637508af611a42c1d3ce695b4bd6772321c71a346a55e958ccdc6d0eba4a4ed2e15a5a9c522a96aa5897cfc41e77c509082210c4feef0e7ab05b7809d69a58850c42a2ab6e42ad632d8ce4110db97e04da1b6d0ffc5ca7fcd50468a3fc1d230cc943eaff2df360cf6bf4c6be56f168e520880eed33e970cbd3a92e3deb52461c5d818cd6a55186641e743b597fe2d0b288bcdcd00d30c7a03e7cf699b5c4a687e601878e66a9c25b78e9e3110c524d5aa4418b1f25cdaee61ba19551d518d2222d5178d45daebaf100b8769d0f177b1c65f1efa1d1777268a483b0122efcf055e6d25a276f17d80f06ef5a9daf127f813fc090294a9bc5f4ab45aa2dcf2ffc5bd0bc973001c8037de455ad3acd7191f6951af54069f5583ef5d00cee7ac1abaacd4227e9a51a4513e3c589ee6a4dd14e594e8233d977661fba914b9b3f4f21c1dc7546b35a2489367a984dbeae6fef8644cbd52915402d6a2fd6b43a315c2b0824369c1c0fc50153a8f2b322c37341d020a2fd2873abcaf96d6c1ebb43c217d4b66f96ff9ff3099c3653108d797fafe8f05ae5505f027f8137c2a281b75860c3c2c426be5cf45809602fa381b04b1c8a7f18da3884869469b2dc647c748e218e785bde2d9b0bba7f8c7c3b0636ef9ff92a4199e118b11281facc3641911508962617a0e7f7c520c4199b5643647c78a5a3d218ec50f33cf0cedb90e6927a35ea9518d2b85681c4aa11b1d68d6c030f9860dde7165e8079208aa04eb233d5b65313eb545a8536af752b053457c19b5889e7578daae06aee5992f827195eb0ccc71b8fc09fe045707d74a8b5709430c74d1e26d13de04ed4b7081f4715abd974fa434915234aa751ab506511c17361bff3a5041202bf7498eb7f669a2dc0281419da6f28132adc31a83e9a5d83427729028ed995e89c09426c791617031d4ea55924498ab492d9db91e2eb754e3844a1c8b9a77c852d66f8e1fb14559cc70c3170c6751a4febe6d0c2703651cb8288238a1670d562ba24802c2ca6449dd8a85ab2021ad41a905570bf24c5f7afd3430cce0aeaa94a35095eaf813fcb70b81ba86cbb58073e158f335c042325d5816b9b418cd28c500b30c4621671cce00ce112945a4359528a2596f50492a42e74a0ca483d42c8c33241e2cff08b92c6cc520d374627932c6603283cb0de43991b1245aa1f1d1ae4b96e1cc183a794e5489a98f36504aa2752ba3e8cef75039d4a2440275f8788481cbf7dd2b423324f0a7f291bc171e51534524ec50163ba6a69588dbc59150a5b13a42556a44f53a26d224b59aa4347012e948787618aef2f0952d7afd08cf65588a21c975cf9007c4fd2b4399017e5608f365fda105b78865ff4f7075501eb3ff9ac6ce1148c6e1942d8a9cefbed612f6d397636f1efccef74a85253e17d57801462b7ff41a51b96569c6ccf40c9313534c4e5c62fad234dd6e97586b6225db74bc8d4615f59719a70747f159e14f0bb9c5333afcff016aa5218dcaaa91390000000049454e44ae426082, 'image/png', '', NULL, 'www.huynhqduong@gmail.com', '112739013180770480868', 'admin', 1, '2026-05-09 21:03:14', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `avatar`, `avatar_blob`, `avatar_mime`, `phone`, `birthday`, `email`, `google_id`, `role`, `is_active`, `created_at`, `employee_id`, `doneness`, `flavor_profile`, `fav_ingredients`, `disliked_ingredients`, `allergies`, `visit_count`, `total_spent`, `drink_preferences`) VALUES
(6, 'Long Hoang', '', '', NULL, NULL, NULL, NULL, NULL, 'hoanglongduongle@gmail.com', '109729244014013210980', 'admin', 1, '2026-05-14 09:27:18', NULL, '', '', '', '', '', 5, 3060000.00, NULL),
(11, 'thongd342_56', '$2y$10$ydLuzErC9LKvHzqEUClVCeqftbUYjR6yJE.4j5PdPw1xSucGF9KFS', 'Huynh Duc Thong', NULL, NULL, NULL, NULL, NULL, 'thongd342@gmail.com', '100631379832642815829', 'chef', 1, '2026-06-02 21:22:51', 5, NULL, NULL, NULL, NULL, NULL, 0, 0.00, NULL),
(12, 'nguyenkhoa', '$2y$10$Ll/kz.YXgWvwp22ZJ6bfQeN.j7cZh8q.uBC7Z8hu4Vpd7Bgf8yIxq', 'Nguyễn Đăng Khoa', NULL, NULL, NULL, '0901234567', NULL, 'khoa.nguyen@email.com', NULL, NULL, 1, '2026-06-05 11:25:03', NULL, 'Medium Rare', 'Đậm đà, Ít cay', 'Thịt bò Wagyu, Nấm Truffle', 'Hành tây sống', 'Không có', 4, 12500000.00, NULL),
(13, 'tranmai', '$2y$10$dBOdF0Rw2aVPXgUjGDd9auuUot/3lbOVworbaSlaKVbLe0bW6QRte', 'Trần Tuyết Mai', NULL, NULL, NULL, '0912345678', NULL, 'mai.tran@email.com', NULL, 'customer', 1, '2026-06-05 11:25:03', NULL, 'Well Done', 'Thanh đạm, Ít muối', 'Cá hồi, Măng tây', 'Ớt chuông', 'Dị ứng Hải sản (Tôm, Cua)', 2, 6500000.00, NULL),
(14, 'lehoang', '$2y$10$dBOdF0Rw2aVPXgUjGDd9auuUot/3lbOVworbaSlaKVbLe0bW6QRte', 'Lê Minh Hoàng', NULL, NULL, NULL, '0923456789', NULL, 'hoang.le@email.com', NULL, 'customer', 1, '2026-06-05 11:25:03', NULL, 'Medium', 'Cay nồng, Đậm gia vị', 'Thịt cừu, Tiêu đen', 'Ngò rí', 'Không có', 1, 1500000.00, NULL),
(15, 'phamquyen', '$2y$10$dBOdF0Rw2aVPXgUjGDd9auuUot/3lbOVworbaSlaKVbLe0bW6QRte', 'Phạm Ngọc Quyên', NULL, NULL, NULL, '0934567890', NULL, 'quyen.pham@email.com', NULL, 'customer', 1, '2026-06-05 11:25:03', NULL, 'Medium Well', 'Ngọt thanh, Chua nhẹ', 'Gan ngỗng (Foie Gras)', 'Tỏi sống', 'Dị ứng Đậu phộng (Peanut)', 3, 11000000.01, NULL),
(16, 'hoangha', '$2y$10$dBOdF0Rw2aVPXgUjGDd9auuUot/3lbOVworbaSlaKVbLe0bW6QRte', 'Hoàng Thu Hà', NULL, NULL, NULL, '0945678901', NULL, 'ha.hoang@email.com', NULL, 'customer', 1, '2026-06-05 11:25:03', NULL, 'Rare', 'Nguyên bản, Không sốt', 'Thịt thăn nội (Tenderloin)', 'Mỡ động vật', 'Không dung nạp Lactose (Sữa)', 1, 800000.00, NULL),
(17, 'vuonganh', '$2y$10$dBOdF0Rw2aVPXgUjGDd9auuUot/3lbOVworbaSlaKVbLe0bW6QRte', 'Vương Tuấn Anh', NULL, NULL, NULL, '0956789012', NULL, 'anh.vuong@email.com', NULL, 'customer', 1, '2026-06-05 11:25:03', NULL, 'Medium Rare', 'Đậm đà, Thơm bơ', 'Tôm hùm, Bơ Pháp', 'Cà rốt', 'Không có', 2, 5500000.00, NULL),
(18, 'ngochuyen', '$2y$10$dBOdF0Rw2aVPXgUjGDd9auuUot/3lbOVworbaSlaKVbLe0bW6QRte', 'Bùi Ngọc Huyền', NULL, NULL, NULL, '0967890123', NULL, 'huyen.bui@email.com', NULL, 'customer', 1, '2026-06-05 11:25:03', NULL, 'Medium', 'Thanh tao, Giữ nguyên vị ngọt', 'Sò điệp, Trứng cá tầm (Caviar)', 'Thịt heo', 'Dị ứng Gluten', 6, 18000000.00, NULL),
(19, 'duykhang', '$2y$10$dBOdF0Rw2aVPXgUjGDd9auuUot/3lbOVworbaSlaKVbLe0bW6QRte', 'Đỗ Duy Khang', NULL, NULL, NULL, '0978901234', NULL, 'khang.do@email.com', NULL, 'customer', 1, '2026-06-05 11:25:03', NULL, 'Rare', 'Khói, Cay nhẹ', 'Sườn bò hun khói', 'Rau thơm', 'Không có', 2, 3200000.00, NULL),
(20, 'Chinh Van', '', '', NULL, NULL, NULL, NULL, NULL, 'hvchinh0211@gmail.com', '112121017916863660614', 'admin', 1, '2026-06-05 21:44:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, NULL),
(21, 'tranminhxuan169_1', '$2y$10$fhNHLt4oXKf.S0ahIKV0muBmaZVG6fbvoQJ6rVKWhUqJ1xsyOmuf.', 'Trần Minh Xuân', NULL, NULL, NULL, '0917904228', '2002-10-12', 'tranminhxuan169_1@example.com', NULL, NULL, 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Chua thanh', 'Cua hoàng đế', '', 'Không', 2, 15423036.00, NULL),
(22, 'huynhngocson924_2', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Huỳnh Ngọc Sơn', NULL, NULL, NULL, '0953062208', '1988-06-26', 'huynhngocson924_2@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium Rare', 'Chua thanh', 'Hải sản', 'Cà rốt', 'Đậu phộng', 1, 9032822.00, NULL),
(23, 'tranvancuong120_3', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Trần Văn Cường', NULL, NULL, NULL, '0997746456', '2000-05-08', 'tranvancuong120_3@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium', 'Ngọt dịu', 'Thịt cừu, Nấm truffle', 'Cà rốt', 'Hải sản có vỏ', 0, 0.00, NULL),
(24, 'vuminhhai815_4', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Vũ Minh Hải', NULL, NULL, NULL, '0995865619', '1998-05-07', 'vuminhhai815_4@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Rare', 'Chua thanh', 'Rau xanh, Gà', 'Cà rốt', 'Đậu phộng', 1, 9695115.00, NULL),
(25, 'vovanyen700_5', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Võ Văn Yến', NULL, NULL, NULL, '0952393534', '2003-12-05', 'vovanyen700_5@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Đậm đà', 'Thịt bò Úc, Cá hồi', 'Hạt tiêu', 'Gluten', 1, 2814244.00, NULL),
(26, 'lethimai163_6', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Lê Thị Mai', NULL, NULL, NULL, '0916261315', '1996-05-04', 'lethimai163_6@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Thanh đạm', 'Rau xanh, Gà', 'Hành tây', 'Hải sản có vỏ', 0, 0.00, NULL),
(27, 'leminhyen932_7', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Lê Minh Yến', NULL, NULL, NULL, '0958971878', '2005-07-05', 'leminhyen932_7@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium Well', 'Thanh đạm', 'Thịt bò Úc, Cá hồi', 'Rau mùi', 'Hải sản có vỏ', 2, 9382086.00, NULL),
(28, 'tranthihai470_8', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Trần Thị Hải', NULL, NULL, NULL, '0987490077', '2000-10-03', 'tranthihai470_8@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Rare', 'Thanh đạm', 'Hải sản', 'Hạt tiêu', 'Không', 0, 0.00, NULL),
(29, 'nguyengiaduong849_9', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Nguyễn Gia Dương', NULL, NULL, NULL, '0927194712', '2004-06-17', 'nguyengiaduong849_9@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium Well', 'Đậm đà', 'Cua hoàng đế', 'Rau mùi', 'Trứng', 1, 6725596.00, NULL),
(30, 'phanhoangson709_10', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Phan Hoàng Sơn', NULL, NULL, NULL, '0948958860', '1998-03-16', 'phanhoangson709_10@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium Rare', 'Đậm đà', 'Thịt bò Úc, Cá hồi', 'Hạt tiêu', 'Gluten', 0, 0.00, NULL),
(31, 'Đangbaoson626_11', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Đặng Bảo Sơn', NULL, NULL, NULL, '0939391711', '1993-12-19', 'Đangbaoson626_11@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium Well', 'Chua thanh', 'Cua hoàng đế', 'Hành, tỏi', 'Gluten', 1, 2461854.00, NULL),
(32, 'legiaan320_12', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Lê Gia An', NULL, NULL, NULL, '0932506191', '2004-11-27', 'legiaan320_12@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Cay nồng', 'Rau xanh, Gà', 'Hành tây', 'Sữa', 2, 12827952.00, NULL),
(33, 'phamĐucquan101_13', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Phạm Đức Quân', NULL, NULL, NULL, '0994414869', '1984-08-13', 'phamĐucquan101_13@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Đậm đà', 'Rau xanh, Gà', 'Hành tây', 'Trứng', 1, 7035593.00, NULL),
(34, 'phamthixuan177_14', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Phạm Thị Xuân', NULL, NULL, NULL, '0985291351', '1985-02-24', 'phamthixuan177_14@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Rare', 'Chua thanh', 'Thịt cừu, Nấm truffle', 'Hành tây', 'Trứng', 0, 0.00, NULL),
(35, 'huynhminhlinh927_15', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Huỳnh Minh Linh', NULL, NULL, NULL, '0920234689', '1989-10-14', 'huynhminhlinh927_15@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Rare', 'Ngọt dịu', 'Hải sản', '', 'Trứng', 1, 9607922.00, NULL),
(36, 'vubaovy464_16', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Vũ Bảo Vy', NULL, NULL, NULL, '0941689163', '2002-12-08', 'vubaovy464_16@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium', 'Chua thanh', 'Rau xanh, Gà', 'Hành, tỏi', 'Gluten', 1, 2308419.00, NULL),
(37, 'phamminhhoa356_17', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Phạm Minh Hoa', NULL, NULL, NULL, '0991796427', '1992-04-08', 'phamminhhoa356_17@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Cay nồng', 'Thịt bò Úc, Cá hồi', 'Hạt tiêu', 'Không', 0, 0.00, NULL),
(38, 'vuhoanghung210_18', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Vũ Hoàng Hùng', NULL, NULL, NULL, '0936065848', '1994-12-02', 'vuhoanghung210_18@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Medium', 'Thanh đạm', 'Thịt cừu, Nấm truffle', 'Hạt tiêu', 'Gluten', 0, 0.00, NULL),
(39, 'phamthanhhung300_19', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Phạm Thanh Hùng', NULL, NULL, NULL, '0990047319', '1992-03-18', 'phamthanhhung300_19@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Thanh đạm', 'Cua hoàng đế', 'Rau mùi', 'Không', 0, 0.00, NULL),
(40, 'vuvanyen951_20', '$2y$10$fNsZjzP/EAbpG0EB21zRXeohiyv1GNC/jBlxjlptqbtnHzZo4tvXm', 'Vũ Văn Yến', NULL, NULL, NULL, '0910434134', '1996-01-20', 'vuvanyen951_20@example.com', NULL, 'customer', 1, '2026-06-05 21:50:01', NULL, 'Well Done', 'Chua thanh', 'Thịt bò Úc, Cá hồi', 'Hành, tỏi', 'Gluten', 0, 0.00, NULL),
(41, 'tphat', '$2y$10$vqbAYPJAtygktjgLOqaGkexxlCimnw/og2JaRovAbGV7GSlXmMOia', '', NULL, NULL, NULL, NULL, NULL, 'tphat@gmail.com', NULL, 'admin', 1, '2026-06-19 20:33:30', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, NULL),
(42, 'Dương', '$2y$10$OVNvyR6tAO2GwhyjRnFrqeDX4YQMud.AS8Fr15n73ybSrKBvEZR12', '', NULL, NULL, NULL, NULL, NULL, 'duong@gmail.com', NULL, 'customer', 1, '2026-06-19 21:05:05', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, NULL),
(44, 'khoa.nguyen_71', '$2y$10$IU.jKJzBLs4Xcb1pNsgsXeExpsIdWWvXhesbkjTloT.cDe9gHmOAi', 'Nguyễn Đăng Khoa', NULL, NULL, NULL, NULL, NULL, 'khoa.nguyen@email.com', NULL, 'cashier', 1, '2026-06-23 10:25:55', 7, NULL, NULL, NULL, NULL, NULL, 0, 0.00, NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `avatar`, `avatar_blob`, `avatar_mime`, `phone`, `birthday`, `email`, `google_id`, `role`, `is_active`, `created_at`, `employee_id`, `doneness`, `flavor_profile`, `fav_ingredients`, `disliked_ingredients`, `allergies`, `visit_count`, `total_spent`, `drink_preferences`) VALUES
(45, 'Huỳnh Thông', '', 'Huynh Duc Thong', NULL, 0xffd8ffe000104a46494600010101006000600000ffe1057245786966000049492a000800000006009f9c01007a020000560000009b9c01006c000000d00200009e9c01007c0100003c0300009d9c010020000000b80400004947030001000000500000002588040001000000d8040000000000004300f4006e006700200074007900200049006e0020004b00f91e2000540068007500ad1e740020005300d11e2000530069006e00630065002000320030003000360020002d0020004300f4006e006700200074007900200069006e002000a51e6e00200054005000480043004d002c002000540068006900bf1e740020006b00bf1e200049006e002000a41e6e002c00200049006e0020004e00680061006e006800200047006900e10020005200bb1e2c00200049006e004b00540053002c00200049006e002000a41e6e002c00200049006e0020004e00680061006e00680020004b00f91e2000540068007500ad1e740020005300d11e20002d00200020001101b71e7400200069006e002000a51e6e00200071007500a31e6e00670020006300e1006f002c0020006200e1006f00200067006900e100200069006e0020007400720061006e0068002000a31e6e00680020007400720065006f0020007400b001dd1e6e0067002000630061006e007600610073002c00200063006100740061006c006f006700750065002c0020007400dd1e20007200a10169002c00200063006100720064002000760069007300690074002c0020007300740061006e006400650065002c00200073007400690063006b00650072002c0020007400fa006900200067006900a51e79002c0020006d0065006e0075002c002000740068006900c71e70002c00200074006800bb1e20006e006800f11e61002c002000740065006d0020006e006800e3006e00200044006500630061006c0020006c006f0067006f002c00200069006e00200070006f0073007400650072002000500050002c0020006200a11e740020004800690066006c006500780000004300f4006e006700200054007900200049006e0020004b00f91e2000540068007500ad1e740020005300d11e20002d00200049006e004b005400530020002d0020004400690067006900740061006c0020005000720069006e00740069006e00670020006c00740064000000740068006900bf1e740020006b00bf1e200069006e002000a51e6e002c002000740068006900bf1e740020006b00bf1e200069006e002c00200069006e0020006e00680061006e006800200067006900e10020007200bb1e2c00200069006e00200067006900e10020007200bb1e2c0020006300f4006e006700200074007900200069006e002000a51e6e002c0020006300f4006e006700200074007900200069006e002c00200069006e002000a51e6e00200071007500a31e6e00670020006300e1006f002c00200069006e002000a51e6e002c00200069006e00200071007500a31e6e00670020006300e1006f002c00200069006e0020006b00f91e2000740068007500ad1e740020007300d11e2c0020006300f4006e006700200074007900200069006e0020006b00f91e2000740068007500ad1e740020007300d11e2c00200069006e0020006e00680061006e00680020006b00f91e2000740068007500ad1e740020007300d11e2c00200069006e006b0074007300000049006e004b0079005400680075006100740053006f002e0063006f006d000000070000000100040000000202000002000500030000003205000004000500030000004a05000006000500010000006205000001000200020000004e000000030002000200000045000000050001000100000000000000000000000a00000001000000300000000100000064b20000e80300006a000000010000002900000001000000923d0000e803000000000000e8030000ffdb0043000503040404030504040405050506070c08070707070f0b0b090c110f1212110f111113161c1713141a1511111821181a1d1d1f1f1f13172224221e241c1e1f1effdb0043010505050706070e08080e1e1411141e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1e1effc2001108035d029d03012200021101031101ffc4001c0001000203010101000000000000000000000304010205060708ffc400190101010101010100000000000000000000000102030405ffda000c03010002100310000001fa819b91819001900003219000c8c64060cb00c80000001922f8c7da7e43c7d3cba5d1d387afc6753b5e3fd1e3bbbf2573d787931af727f3f2a5ddb91b6b3d6ad5e62ef6b99f4ccf497e69f60fcfbd38fe9ee9fe68fba27a3670803191ab3800018c8c031960ce33800cbc76f5eb73e56f1dbd7c3f9f3eb6f9d60fa367e7dea0eca8c51d3cf16e17a1afc1abb179aab5e9b3e3167b6b7f36823ed13fc8bd24bee9c8bb1695e73201930c8c320031013fcc7abd3c75f9ae37c78fe85aa56b15e3b91efb85dbcbe516aaf7f3b19ca359724bd7c7be9a7aae5f577cebfc1bec5f1c963b752397f457b1fca1f54b3eb6e575930063235670000619c0063218647e7bdeb6fa74af79eab1d7af5ec274a4e7adb5d4f383d367cbee9ea66f33b1e861e54d57f10ee47aed118ab2c0b036c665eb5c3d2df43eabe69b9f70bdf14ebc7d65e73d046e018338a1f223e83f1bf3b31eebedff0000fd032fcefcd7d97c0f1f4f988a4df87aa8f17b5c1e9cb970dccf6f353c75fd4d9e13d27afd37ce2b9bcbb9b5fa71c796f9cf77839ba60959c0bdf47f952bf44fa9fc9fe8d3f4abe6bed53acd76356da80018670006327e70dea2ac691582e6f1ea4914958964a3216508b8af296acd5b96492c39b648368c8a29a2934d77d48d9caed9df31574b4b27f67e32e2fd3badf20f327dcbe6df2e8b36cd6d6e4bbd4b352bb9fa67f257ea793a3a6fa5785f23f4df11cfb702ad1bbcbb57f61eabd077f3f91e67a8937cbc367d9d0ae2edb54a9685df171e375c31a000000da68307aefa27c3b27eaeb3f99bedb67ac6708001803191f9d34f67caaf392daac92ed8b26f1d969c883ad5b369ed26e62c2c99b5acd6673b6d5a6ec18ab3568d35b05af3c93112cc9654b327123abe6fce539ab106b9cb1b6b22d9c46dc9e85da58b67f4efe6cfd2f64ff159f8f6791af3433411bfb2f143efdd4fce7eff0059f7f4aecb5c48fa30d51f967d0fe578ba0940000000013c03eb3f4ffcb9d24fd458f85fd1acf5ed3718c8c03e4f46ab686ade9228deb360e74776bd9147d4d97916259882548866422df7c91c16eb14e39abacd66b5a4de3c7968f63cbf2bc796fd0c33701406fa6c6d6aacfb9ad69a1c5f45eefe59f6ad67e7feff00d77657f2d50f41c0960368c5ee97a7b29747a37353cffa88ead7ace455d23c578aef70737025000000000033bc62ceb16b5edfec7f99e693f58be55f50b24647cc6b5c96f681dc913cb47dac4bc2e57b4f29ae5b58e65bb9de586cad1de7dd219e5c9124c91476073abdea86f663de3c5f0368f1a605006c6a01b1b59827dcaba18bdefb67cd3ec9bce7b3e5bd147e73f2ddde0cd67d978dfa8a58b53e9b8bb5ef924fbce53f9bf67911e26b18d00000000000000001b7b4f17bd7dc29fc8fdfcd4da71e9ea7a6cf92918f5d6fc548d7b1a1c0bb674a3a9aa5db9ccb674e5ab6ea5d648e58b6c6b66d1eb21045d7ccbcbe0fa1f93455c19a000ce326019ce24b379a3dca59c6d2fd03ed9f9ffe91acfb0f947bbfcf72c7a92e7d1f9cb35f619eaf4b799baae8441e27b7e48ad57d3f883c60c680000000000000000cedada26cc50d9ecf81f42f17654bf0db857f4d26a79c9fa988e65cc585b5bdde869cdb17e134cef29463e869250d7a35addb7ad525f15e673ae180a001963358119df497525c4b5a219a1b12fd2fd7f13d1ef3f2bf15729e6e0ccb8bb4b63eb9d9f2be9379f451c5cbaa1a67a44bf1dfb57c0336219a0019c67000000000000049629ed53daa12c7e8ca1d8a7a9e6e69636bd7c78858f25c8b746a2815d3add2f3374f45b71e63abbf2f074dccc1d2ad46b575fcbf5be7f2f2306340000338cd6046f24326e4d0cf5635b94ef66fd679fe97e49acf074ce33ace7193507b6f73f34fa56b3d1a17e8e9177b99d93cdfc4fe99f34c5c0940000000000000000037c3a07d87cafa7f09b9bd2d39d97a5d7ce4e9d3e8f1bb6e9431bc771bcb0c89726ab2d93c904d6e74cea90416fbd2f91f0fee7c2e6e04a00c98640cd605897496b48e684c75b93d5c5fabfc6bda78fd4ab92248e488c258a5ecfd3be57f4cd4f4143a1ced4e85fa08f9179d9a1c680000000000ce000000cb000dee52bb5f5cf0bea3ccce95e0b90a72a4da566c7a3e1f633d6ad4b95f58c4b9b3731cdbd858e69645ab15fac43f54f967a5b8f91f2738930250339c6f6631bc7a36d77cb49e293658630821b358c5ca76e5b5563decafb36ad312aa06dae2dafa7fc9fea567aee5f5397a97fcb7a5f97479bd0ce80000000000000000000def54b47b2e274f8fc3d91c5aedae7bdc82ccb3cd0e5ad9b4dac6b774b52ace962ddb3b0549eb66c5caed787d73e18e9c00033be99d49b5df4b2349b2e25c6d0931ae6c104f05338cc59ced9b22df6c18d3780884b9fa57cd7e8f67b6e75ee7ee4bf14fa1fce317025019c00000339c2c6338940000000026b74ed1dbaf574e7da6deaefacdfb105c9aced2ccb5ed6f7960b172c675424e9b4a19e8e538f0f6732f1be5feff00e7bd384418000df06a4bbe931a4766bc4bb64a115629621be9216e343a8c688cea4a067e87f3cf7b67b8a76a86a7cf3cecf06340000000012626af6604a0000000012dbad6abd04fe8b13a7271ea72d79abdd0be9c39fa8294d7f458e54abbed1cf9ba6da6b5ae9bd79af9df8ffa57cd35e6c04000cedad9b3693199628f68f52ced8db35aed195a3df41be9b9356b15e8200033eefc27b9b3dd707d078ad4f9f0c68000036c56046738cd8d6d75d7cf258a00000000026b7cfb967d163a9a5be8ec79d9d7ab7bcfda3af151d17ad0d0d52fcbcb94b9b535966bc70a4f05712fcb7eabf308a63340036bb4ee5658d62bef058b27c92b5da22aea0df4d89609e0a08000cfb7f11ec2cfa37ce3e8ff002ab3ce99ceb000322b7d3394d5ec7e847ca3ddfd2abd9c58aed0d3e77e2fdff80c5c0940ce00036d4033768d8af5b1f94ca7b0b3e36f59e9e6f3f62bbd9e34e74b6e78e8cbcdb05d5758a13f3e58953313f8db5526b6c49ba418bb4a5033bc637c31662cd6b92cb8c869bc45301b6a58af247410000f4be6bb55f5bf90fd6fe2b640491119946f66b9c6da63afc9fabe5eef7c5fd4e3d7f49c2395cc8be666792634000000000cb0589f7dee76bb05d2d4f8b7a41665b6b9b12dc5e76dd6919e43a50573a0e9c5272b6b38af9cf2fd1f9cc6b7921c1b684a0019c6558bd46fc6c64c579e91a0248e4d4d4000003b5c5f595edfe3df4af9a598db566819cb7b37db68ab3fa13e47f683d0d5f93fcf64fae7ce3cb173a92800000000019b35bae97a5929ea5fb3c9b275acf2ec57567e6da5bdb57cb56ae72b6b3b9bf12d497e9cd1a717c97b7f9c472eb6dacd0400000066fd1be6718c9a52b752cc0972c0000000cfb5f15ee6c9fe7fd7e418128c99db12ea20df43a1523d653388000000000000037b15243e9fc49bbfbcf88b3e93cb17a6f359cbd64fe437b7dd49e2ed1eb35f3b2af4abd7b155e49e69a783f77f2f99ceb62ba6825000000def50be6ba6b9b315ad55302500000001d0e780064c024b15e4d483066ed9c2cc636d650000000000001925ed73fd2d9e47d3f921f5eafe0fd86a72a8fbea71e466bb215ac599886c15665a514df4a5e25bb9e0f8ae97325630c80000000cdea12d6d88e4b114f01819a00000000006d8b305699c6d12e966aea683377c6fa6a6a33400000064c66411673830ce058af64e95ea152e69ea2cb3d3ba7a5ecf808acf4b57cf625f47170707661e617a31d245d53ba295ca6600000000df1de3cfe71b18962decc6b246604a0000338d8d59c9a800b75ad54ac6fa4a66292346fa4e69869a319c6280001933586fb23468b9db445fc529cd2c6735ac135bb39033736aaddb36464a626800009ad4592b6bbe2b5c491c000000777e9fe4bddcebf10abec7c75e4cac5959246604a000000000ce325ba96ea562686431a67119b94e6b36833a981280001b491cba93546b9a000066cd5d8bf628f6cf3206fa2ad6f0594a02500067121637db041b690d9bc44a000037d2e1f47e878fe9ee55f09eb7c966cb3d7b16410c91ca100338c99df796cd35db623d2c6d1456ea5b8368b54ef52ac6728d41befad8d4a8ca351280033890de160cb696cd759315032cdc01b6b316f7a77ace4ad55940dac55d8d40000b35ba0662923b2a37d170200000cf6393e853a1d9e2f6b7385e43d9f8ccdb32d6dd6be08000025cc3b56317a739796b00017a959ad59c31006d3d6163585410001926a8ac6f94ce36d658e296b6a6066803726e952b364dc2b35a5000000024df58c303380000006c5deaeb2596badc9ebd9cef15f42f9ecb6f4da1221280000373bd3f9b90dea6700005882c57ac08000000000de782c592662ac5bafaea604a000df4d89fa35b367344a0000009e1b854d6fd334000033800338c9e824c49732f6391d3b64f9dfbef0316a39602212800019922c9b4f5b6b378f78ccebb6171b6bb44f5acd6a0800000000646dbe2a3c4bac699df244ce0009672a6d66b697fa1cff7b27cc04a000001664c6966b092e00000000962ba7677837b2d74f93d1aabe37bfc08b75278170200000000000670248f7d340c8000000067129166498836b18b2b47be8a10066cd51d6adaf42e69fd63e51f69af830ce800004f05f330ef52cc633894033800004e69ddd26b24de1909ee52c279ca99c4d4b14b10000000049374baf67908fb5c69700037d648f4c0c8000000000cec6bbe836c6161996a05cd25ac93487678d2275fee7f23fa9ea7e79df4b79d411f46a9000092e45b91d5df400000000cdfa1d849f7db366b2625b77e7f53811cbce369768e780000032cacc62788ded53c92d79044b1b4b5566b12c72c5581000006700036da72a4e11e1a99db4c98ce06d2637b25979b657487b9c63d07d3be5bf50d67e2f1f5a966ef273fa273f4bd44c67134b671b43559840000000c977a389ec8b120d264c67cd7a3f2ab8db1b45de7c91800006658a4d4ce32d27db39e758cea6d8874310849a67158100003233666aa19b9aa66adcd65d21da4b2188940012462dd592d9ad9e4f613abeffe7feff73e6f0de637e6b4f41c149a9e702dd6b74ad3d64d44a000000b757b44d2699d4c3544b3d69ace7f1ae53ce924731982588000019c64d9aaa492b92787185cb61a08ce00019319cdbaad36d3447b800d331d6ba69ad8c6719a00002e55d4bb8eb71ebd47bbf13eef59e0732671f472f87eaf9b73e4f5e956df3cebbc76458c23025000000b7d9e75bb2657d0b79a9216f11d738ba92ad56b257d4000000064c3b731e7990c00c984b215b39944fbec47b326bb606da908b5877368f08c0940000000bda43da3d0faff002516b3d3e0f6b839eb1d8f35049ea38aabbceb1e8930250000004b174cb79d63b99631732c5293f23afe74884b26d25630000000003bb4292b7d71bc46b1019b292b49a9ec9635ceb1be75896760634d22d4b6a5b4635ce0c094000000003bbd7e251b3a9cade44f6d47d473357c4ddad6b2eff85ef799ac63388c0940000024eef12ea5fad620a87120da4c4c41c4e973612477174ad246000006ddd380fa7fcc8c13d906f34c41bc9a126332491986904f82b6d2c16627c57977923db7358b6cc4633400000000006fa0f47c5eef17599331e92dbcf3a2976ce92198e48ecc0940000000ded52b27560963b9d3494baccd53951cd2cd6bbc948d00019908ef53fa0cbebfd970bd3eb35bf33fe9ff0082578f9ebed5736ad2e526b8ccb9c918d618b7338d46d2d766dacd5dac61aacc8892c6c18128000000000c9dbe7dea7a519239634d6ec52579e392d8704000000006d83162bcc7475db173aed1eeb2d5af0c4b986c2e2bdbedc79997e857f3d3e77dbf532e7a7cef95d4ebf5f3f99f5f62d27a5fa27c0feed52fcafea7e76cfce2923ceb6c606fae06fbc22c471ac09400000000000000000124739d4a96a0b39825e9dca976cabcbbb40c094000001247df3876768ac8333d78e861a9bc1dbf4b35f3df47efe5cf4f33d5e8633bad8b11e771c73c1351c7996e69fd3bcffa2f4f8badcae37ae3e53ddf41e1abe891f3ee9f00f37f5bf92e2e0280000000000000000000000066d57bb64d2c37d38b5bd2c0b575ee79f28e0940000000bde87cef76cd3cff00d0e859e235ea529a97e8b27739f6d76d37c74d75cc6bbe98d6378f186b1aed0c9b34adbe7f4ce766bfa3cbe37ec1e2fbb1d0f9dfb1f919f40e979dbda4df03fb9fccf2f1e33a000000000000000000000019c6c66681a97a4ab3c697789b1eafcad9a8604a0000019b515d4d3104b65cebf9fb0bd7f5ba7639f78a0abcae3e8f45232671be9ace9aeb01b3122c2cf3e4b14a68b59eff0057c1fa0f4793da55e4f0ace9fcce8c59bef7d4fc8fe9d659e35ae2d7ceb1d9e36340000006da8df5c19c0000000000000338c99ce31a92dca37a4e762685a963dfb11c35aac6000002c97e6873673359ab45bf79f38fa8e7af6abc5c0e1eac5cf37f42d73962a389bb9ac3311cf8599a1af3d37c6f72cad0ef81c8e8d0d6387cfefd7e9c7955da267d7791dd3dbf1294356f8f2c528000004c87735c000000000000000067056dd0e774ec979beaaa25ff00b3f8df7c70fca7d03807cc7ce7d5b9c7caf1f51f232f9b6712edd9a9d7b2ad6ebf30a75248e1e87cf6d35ed39343e9bcfb4fcae870b3d39f051b5be7edee6b173ebad6de3b28cb2cf64472cd6c72ba9ac6f477e25cc1c4ce9be58000000000000000000000000000ce0338cd19c23a7cde81eb2af66a6a7d72d63054e176b967231763acf83f55f2e8a7259b38d7520b1a5b1697ae9e2a9fb0e3b3ccd3a7ca8f45f41f967d079fa36f3963cb12fbba1da8e773b9bc9b3d576fca7b3cef6669b54393ac7be37a6a1c2d66ef1345c600000000000000000000000670000659d400640d4c74695d8f7f5aec1a9f5e8fc1fa4cea5e6f5f85acd0d378abc6d1b3c8c3d3712bc33a5aaf673734ae51ac9e8a851a0b244466e52c96aef23a12fd238b6391cbd1cde5c9e8fa71bb626f35cbbfa3e770e8f5e3d38b930eb9eda000000000000000000000000000000019c0049a334e851e8d9efa0bf06a41a4d2797d98db7f9ff5f3fd3783e077df39b343a99d661f555dae1dbd35b8938f2d0860500002eeb54743e89f34f5b8eb9e3679699a66f9e000000000000000000000000019665b20ccf5e5cb7d2b0200db32cb6526dacb9923cd99bdcee99eff005deb6a5e9e39fcfecadf3efa1784ebe7e1cb9e8b31d4f6bcd5b37fcae57d2f91df92ce000000000248c6da800000000000000000000000000ce06d3c12ea59ad3e73696f1e6989890338973d0e75a315efd4a8b38cc2782dea77ba5e16cc7d4a78ac71f4d1f11ee3cc74e5e6fadcfdb5cfd343c8966b4e36d51000000000000000000000000000000000000000339c66cdf3633514d0c19b2d7b311992b6d566acd2a549a22deaed6c8466ed768dfd2861265f5a921879fa35f3fd3f33be5c5b35ac5e7d3a9051350a00000000000000000000000000000000000000092392ae88cc72e2ce7aed3973a026806fa06d245b59a338956aad82bd88763ea15a6a3cfbd6f2bd8f35d38f67d2f27892d2d0b90000000000000000000000000000000000000000339c66cbf98e55db38ca6b1cba9cfd2fd15c08019c0ce5bd45d7e4fa93bfe4febb16b3e4a9747c6f2ed5a9e926b9ed5c00000000000000000000000000000000000670338000001be9b16e682c5631669259df97649e9f435978e96200013c1291fa2e1772cf65e9be4bd3b28701ae373d5db5b000000000000000000000000000000000000000000000369b5dac9ec72f53a54779ca3676ab2dea0c9a80003d477ac74b79f0157d9f9c8f3033a000000000000000000000000000006e635e873c0000000000000009ed52b766d5ad66398bf0ad79e2d0b10243a5caf61e70a0074b9feb6bd9ef8db798e974743e3839e80000000000000000000000000004a6b7b1ba6797d6e4a8000001bab410000001bdba77353736cb5c6f0d6f88e025871d697ad8db6d4f21afa4e566d2f5fcee9d9d2ef791b367a9d639f4f8b8e7a00000000000000000000000000770e55a8a5492c56dec727a9cb970140000df1a80000006719acdcaf650d29cbd49392b3b19e4652fdee17ad58e48ead5bd348c9b30e91d5c73ecd96fafc5d2bfffc4003310000202020004050304020300020300000102000304110510122106132030312232401423415033341524422543073536ffda0008010100010502fea2cff1dbdac699750babfaeab0dcdd06d6312e9e7312cff5a3e93cd501efee6e10b0d39ea98b5859888f75de29c50be19e01c56de1b6e16457974ff7edf6f105e9cc95f76cbc55baab12dc78ce34b676ea9d46798658c082d2a3a6b1fe9c6af713eb3c130ff4e3c41ffe8eaeebc1b8ae4f0dbb83f14a388d5f97d4b06bd9b2c0a1b3745f3e1e24d178934ff92ed4f1146955b5d83f0b8fa05cf695cfe2ea51e6460b0b2ca2c4f481be542751ad0b9e1784b8e317efe3cfff00c3531feec2c9bb1323c3bc61389d3f8a9c563713ed5f12873d3a73789374a7126eaaf8936ade22f31389b6e8cd561fa9ae365a4199dd7214c6c84d6564831df659a33770d1c982d7438f9eca71f8a4a33aa782fa8c5bab6f76eb6ba6be13c5ff00e478c718c4fd452e3b2af6118cb3badeb2d5efc80df245dcc5c77b5f0716bc644f9a46a789ede9e1351967df31322dc5bf8778b3b60e663e627e1ab99e634a72350e58d5d7759957c3fc56dd2d5e605873e2e76e0cb8336365b19e6b34eae479346866c894e53243c45b54f126de0f13956654e11d5bd9e33c67178757c578a657109ffe3e6d714d0238de11162eba618f323e089d0274013a445a998e2f0a79479752aee26f61f53c5d77fd6afeef4635f6d16f0bf15d8828f11f0bba5362d8bf80208df1b890452442f37cb714c0603018a603ccc263733cb515996559ce831b8a1de17105b223061ccfc6665e361d5c63c556db36d63e4765f0336b8f88caa4716e1ac91195965b2fdce981099563b58f8bc15f4b5538ca2bea8a8820026f50b4e3f91e7e7afac19c2b8a65603f0ff1563db3173b172bdfdc0d19a2f728396e1301e46080cdc53162f3630fa0c1c88844d4c6bdab383c4081467ab45b15867712c3c4af88f8b1c8cbcabf2adac7741a969db7872efd3f1c1c9c02bc5785ec831fe5f5168363e0f02257f6f1abb0dcf3c9b8c5c5b62d2526f537df3af1463b12cdecee576156c2e3dc4f14f0df14e3de6ab16cafdeae0337b31b737034dcdc06095c5e66185611ccc1cf502c5495a912ecc5c55c8e3998f2db6cb189d851b20010fc3fcd7b0f8167998d09d0c9e23838e78b710e077c6cea3783760d8fc20f0a22dec9e5833cbab47cb12cb165afbe42788aef784e17c532b02ce07c6f1b88afb5e5c2935179208abd9d232f31162c48be931b98115674ce98122d72d3552995c54c7b199b90dc002cea3b3f131875647091ac2cac8ab1eaf10f19cec955eebcd4953c3bc479f8c387714c6ce46d3070d1f6615d4d46fa4713b3cdcdf7eab1eb7e09e2760b89998f929ec350a4594012daf5c96208218c2149d11522d716b8a9144d4d43c98cfe674c0b1609a9f4a8cae314d532322dbecf421d18bf51b3ed9c2c6f3a965a30f8d71339d957525b141fa3d351757e19c6edac6364d77d6f58d3564c2ba1c42ef2ab2767f014caeeb2b3c3fc499f41e19c7f0b308f51b904760d2dd4e8889153b159a9d30a40916b82b8ab35353535088d0f253179a899b9f4e3cc9cab720fa93ee1f2a06ad3cbc3eaafc53c41c50df31312db1b8660f95389e3fe978911db9e36335b31787a20388845753e33e0e7a343d2d320ea71ebbf6ff0831136a4759df08f1065614e17c5313882fa1a9658aa6794762bdc5a8081069c08256a0c7ad601f51ed019be439086308c27f2a22f69758b5259c51cb5dc4326c5f607c895fdb67ccc7764b383d35ffc2e063d7d3d0b3c49ff00f436fcc03671b01b745317b141b86adc7c20d31c64d4b6dc96a7197de4fe1ef9d36d955bc03c4dd711832f2c84ed555063133f4da9656562a961650cb0fcd2fdbaa2fcc3f222c5e66111c724e5c6b23aeedfb88341bee9c3b1cb1e089ffc270e7faa78a3b71e3cb8260eeb5aa2a6a748dd6b0445ea992eb55794cf66564586dbff001c4e0bc6f2f0457c61ed96788326b676dae3195388e565c01942096a8d64a7d6aa600749da03dba62a415ce89d302cd4d4b04d4512c6f2e9762cfedafcf7e8e5c217787c2aae8e1d59f2729dd52ae3795fade27cbc3d7f9dc30cdcac18b2aacb4b99694e23905d9c79587f90275995ee70dbbf55535ff57eacacff0090ecbc48c19dd7133008f97d40b6c9d74ab4ea954fe2b13a6110c02186149e54559c76f5ab13dc51a8df1cbc36e8c98762b559b4754f15f137e9e7e1fbfcacef98a92baa51447d56bc4720b3535166f109f2b87fe4a8d903b6e58f1ac9d7146e01a9d53ae57676f322b72a9a0695f230cdc2d2b3353a44e257ae1e35f635adeda44f8b3ed83b9e1d53a9c2c970bc578bae2f0967667e78a759359faaa94cde9736fec475363a76f1559bc9fc9a963b8110169c46a2822cabe0f2ad0ce98a25622d7d96a3b5a4c5ed04d461351962769d7a0ae2788b3065657b83e13e6de54f7b786d3a4c6a44f16ddbe21e8c1e9fd6567eba4ca0f6cabb4b79ea9527744fa78ddbe6f14fc841b666e9812769c5e82d4fe9ec9e5b894d3631a787970fc37a6793d10af60bdebf9ab5a4035a8408208440b08844d4c96f2e827dd5f8961e58ff00e6e1e3f6bcc4a2ac9b4dd7fa01d4c4b3cc5a8c56e9aee6dc3f38b5cb9bcaa5cf53fe4ecc1b69d5d32dc7560d855eadc35de1632f5e36322ae4d00ae6200489a84ea536c4b60b675406754ea9d50b42d019e23bfa71fdc5f9021fb5fe6627f9b04fd1e2fc834e1b1d9f4f057de3e3c7fb5a22ecd2ba5f11dbe570afcbd99a31ec5d1c84d5d92bbc5c855b28cb4d65e6274e55e19babb33f72d11a2d916c82c82c9e64f327991ad86d89676e2991fa9caf753ee2cbcf0bfd8e0d8fa4e3b97face25eae08fa6c5f87f87940ee3b2f8c6f1e57e588804c8760bfaab035f90f06658a6ae26e25bc46d795daccdb3a727ab92ee2930404f331e698ccab0d18fef209ff00ae5c257ab88713cafd2f083cfb7a3841ff00b7827b3fdad31e37c7886ff3f8a7e583a89ad5dfe3b9bf72c31a2983e31fef51dae1f57258b041072300d9c6c42ebe2b5f26df7445f87e7c29ba33fc517ee6cf48e5bed35f4ce18759f827b9fb1fe68fb73eff00d3e1d9bdfe58f94f873f464ff9c26e595887622cc6fbd076c91df50080402288a201088655f770be85c3e2f9072b897a84fe3d0bf6af4ef4237dbcb0c7565711bbcfcd6deb97fe473c56e9cac43ab7e6b7fba9f8f17e474e19fcc51b30d9f45c776031cc3f28b291a656ed71dc11628804410080422345fbb8a667e9bc35ebd41be64f695fc9f810f65e58afe5d83e5a09fc0806e1ec79629faaaef4d9f7553c4991e7715fccabe6757d37bfd66c309262fca45818cf9e49144022cd7231e28efe22c8ff00abebf93ff86e42749d28d0fe25bf3cc680f93d13a674e87f1cf08ed317fc791dac6b4534bb75bfba3dfabe6757d37773008822f21162888b144022f331a27cf1b6eacff63f9683b84fb3e06beae567a003d03e3f9f88489bf470dff570fedcdfbf8fdfd1c33f36bf95d685a34ee26e298b1601c9445114458041cb51b73a4cb08a69b9dadb7d4392ea7dc08313ed5d11df637ad0967dfc97e7f93b041ec0f727d3c20ef030fe389f69c7adebcaf7506c9f7d27ccdf2d18a0cad4c543154c1598b5195d512b8b5c154f2a2d70d73cb82b13c4f6f958df23d8d1017e08ec866845821960fab92f2246fd7c08ef0b127193a4b9cd96fba7e8fc0af97e9e263883121c5d4aa8831e793a8b56e2d31575144511674cd4ed08e5e2b2dfacf63f85fb258742af9efe86efcd637b3c03fd3c69e257d62fbc4fe057cba06900dd6069d205ef58ec56289a862725301ec5a7546221613c454f9f89ec563b0edcac2369bdf3684ecf25f9fe3d8e01fead3f1e297fdaf607aa9a6cbac1c1f88f9ce8c8fef061a368d2d9deab469acd85795bc769d73cc8f64164f3279905b0db0db0de635a4c07aa5dfe5f508bf1c99b66ad7a0ea1f9e4bf2db1ecf87ffc49f678a1bfed7b03d1c3f8665660c3f0ee356b556684b05938cf0dbfccf793ede48d3acc0d11cebaa6e7543160e663184f2abe7335faaf52fcff001fc9d74ca7d0fbd7a3e47b1e1e317ecf10b6f897b23e786700cccb1c3fc3f83405aaa4175b580f6cbed6238fdfac4f647a5186bcf13ce112e105a20b22db3cd9e64164f320b20b2079d71ad965d1b2226477cdcee8a3d80c44d9e74fa1be3d07edf638136ad5ee9c5dfcce27ec09c271ce4e75607435c745843d51e6432575f12cafd5e47bfe63cf31a55634adda2b3404c52d06e0dcef141801804223acb2b31a93168332fb6445dce9dc2005f62af8e761edc97e66fb7b1c28eaec73b19077910fc73075cbf83dcf83516bc45a1da250aa0f4f4dcd388f12c7c499f9d7663fe00ae0ac45495ac45edd315622caea9e40d7910573a27488521ae1ac44ae71452b9f0311030018efd94ecbcede69f77b5c3c77c6b7a683dfd7a8fa02b42efc07145347f16baa2f13f10e0d073f8de664fe1569b1045dcac4ae288a911257da298a27408d5c2a66a111bb446efe200067fb89f6f373b6e49f3ed706afad738f9181eb5ec2cf9f0ae3fea38a656761f0faf88f8b5cccdcecacc6fc3a7b20ac408b15162811351488ad15a069d712d312e81b73e6148e9324696dcd7a9f22d7bacf70736f8f7b807d9e26b3a717d4b2df998997918d1d8b37e22fc9b65c8c87ccd41744b0c56317713712013460533462b9112f9e70319c4b74c38ca79791fc7b6bf773f95f7bc3da38fc5723f5399e91f08010df307e38526595ad832312795d317422b081c45b444b9625a2798b058235a235b1ad33ce78b73c4b3738c5de767052411af6d7eee4b3bf4fbd4e43558fea58bf1cfb7e2aea514295c3cb570bd2e2ec7046487acf9ad3cd682d782d7897b417344b5a7531803182b305316997f4d1439ea74d0463b3ed0f983b30dc6df4fe2ac7fb7f2284d1a7412ab1ab3819fde9b16c1938eb68bb03bfe9ca91545a22d11688b4c5ae2a09a0392cf11e4775d43f3ee6c4eb83e0eb7f823d15c6f9e5f3f8b4ebaaa1dcddd1cf0f31eb98b9aaf14abcbe91a6fa4a3081845713cc59e689e788d90b3f5020beb55c8b0e45fef13a117ed3bd7e0d5ad37dd17e4680276797f1eeeb5364faa9f956d0b2cdb1f98ba2519eb3839f2cca52b9190091910e51872cc39661c933f50679ed3cf68d63d93e17dcd1d7359dbf0abd68fcc1f3cf5f4fc9fe7dddeb929e923447488ca44a874cb1fb28eae622f752b19acf694683fc7b606e645391ff001f0725f97ecded0f987d3f09c9236f9ebe8ee7dc0a4cfb4124fa51a02ad0ce9dca93b73ffeb58ff6fb150fa8c63f57b7c171bf5199fa147af2e96c7c9fc5ee57924277cc4eb03da100eff0a7d63e55b6e3b445771cc7d8bf367dbec20fa5d499d2d3a4ce93ed78693a29ade78aebd677255ece3f089fa792b6bd07e91ed02624277ec83a29d4cd510abcf66552cfb7d80089df5d4f03f6eaedec28db621aeaa572167895c3e341f03babfcfaf462033a7e9f2ccf2fb749f4ff00f5fa57e4eb5ed7651cc2ed4fa80dc4fa46cfa6b3a2df6faebfbf93bfb78abd56a132b9c6ff00d79de57b0aff003eb43011a2c361d6757d2350f4731f2df6c13f8e49f71d6bd9f8e60133a608fbf529000259ab5d8f40ec7abe8f5e38db4b37af6f0574b54afede3a3f625626c43ec2b113e7d96e43d03e7ccedfc17dfb206e2a68ea341f57b1529dab002e4e93e8dfb150e94848835af6546cd7d96a951edc73be345f81dd3daaa92f2cc560a7b1f49fb7f02b100006873761eb51b85f42b5d8c9d7b89adb58cc7dcc44d912b957c7161ff4a57f6b8eded25c3c9c3cdf22bc9ff3fa5bedf7c45fb39368476dfb1b32a101ed6585bdce8edee088a1144494ce229d58110fd2ded11019d407ad8fd3ef8f907f6f7fb7367d91f2210dee562124fbcbf0224a0f6ce3ff0046283d3676f6bff22326e326bd23e5fb2fe06fdc104c4acd87dbe9dae846f9f71476022ca6677fa507d8fadfb7b33a9b96f98f97f8f7c68ce8edd0668cfa75b1e95506041a283a655dd7c318c2cabdaa446e987ddafef1018b2af8e30fd38b17fc7f87bedf82366741d761eb57806d5d653f1e185d70bf6b440eb07dec55db8822caa719b376cffebfc3edef833b694a80351fe083ec50fd25936bf6ce175797c33d9a86cc75f7514b9a6b0aba822cae653f99913b74fb6aa58f92d194afab5dbf0ba8fb587675435eef4fa47b28348e7b7b952f53a00b3737018a65b67451cb7a1edf07e96cbe278d68e299d5742fa4fdbf81dc7abaa0e8d14854f3adba5f0156de25d7c80261523d4836d2c3eee28804d4d402013889d57c8fc7b6a4ab79eed75b6358de97f7f4797dca091cf5bf42bea23033cbd8642bcb86d9abeab095959d88e9af4d43e96877eee30d5639a980ce22dbb608df6fb604e93ae969d074120ac429a137f4fbbd27a50ed596560edbb1f4a1d164e55da561516230d37095eab69fb5d3501d4adbb1ee1c68f31d87bb455b83d0b165edd56f2b7a3f49ec2fcffe408a009af41f866044df6f6b4668c0098a3e9eea7e47534f91ea56d4640566259d166757a9c1934b4fd8565a914e8a1d8b87d3caa1b696376f72a5d26a6a01008a25c4255c87cb7b23e77d97e399204679d7f4f2fe3d941c88dc55d7261d53ec2dae9f62b7286c40cb2b7f331783efa68ff1cb1362c42a558ac2c08e483e88c767dbc75db2cdcdc066e219c45cf3afeeb7da27b03d83181be9f3213b3ee2a930a451a1e83090431fabd9c6b3a1f2aaf29f18ea7081ff005b1ffc66b222acb93b58bd27901b24cee14fcfb42535f4a6a6a6a6a011665b755dcab967cfbe06c9f64081468023d6cd37ee27fd9c103a57848ffad8e3f6dea5d3d32c43ab9211a329e9e9df7f73147ee0e5bf431e942767955d94f73ef8f6150c0a00d0e626f9b36896f7b12df2acb9b73852eb16ab0047c982c0c1b5ab6a0c2eac88146cfd2093d3ee5034826e6e03045998daa799ed5fb98784d78fd0838c7b7a950986bec46a55af40f4efb13efd68d638e1b798ac2aa9b342cbf193567d04dda89729177410ea16750d37cfb758ea71f0219a804022899c7f77920d9b4fd5ee6265257855dce2b7fb873036420014f706100ce91af413a0addb6213d9893f81c257f72dc9d4b32099633337ea55d32ccb5cc567886c22d627dec74d299b9b9b80c0606ed6b75d9cabf8fc0504c35c3da54068f71d241479f303433e20ee25a66fe9809fc1c42568b2ddc4fb60c4d0c9a480d5ece3e3ee2515d75da76fee27764f861089de77804d4bcf4d3c93e5fe95f780335a54edc993700e9e6c9031585419b227661dc11a11b7f86bf352eead771f112eac8cc74e9b2c1d745fa9c43303d1bf7429d51da13db7cd6099addf952a34edd4deb130b8467e544f0c04c5e4abd80faf90f9e6796fbbaee699610187713abb1f9f9847e1f0d3d42c6d581c42f2bcb65966596527676613ef751d631eff00c1e6b37d9cf53ca9372d6d0f5e2e2dd913c35c371d386a2683099959a72e57f039ebd07b4dcdf704efccedf499d8c2346068ada875f85c38e9f2874df146e5834dc906d98fd5ef63fdfb879a89776af5b81140eadc63b3e9d289fcf096d61786dbf6230ede2da7cae33018addbad609b1be4e4cdfab7f8f8fd9f37fd98ae57d0bd97dfa3effe0cdf2ead477eb9d73b1961e695d8f2ac1cab26370724f15aeba72946e5286cb7199e89e19bb5c421f8f1e63fed720796f975181cc0760eb5f969f753f39ffec722a1a7946749dbfba06f9d5f7f3fe2c7d983e6ce95baba6fb8e3708b5c53814d53a4003b72cefddcfc4e159441e0d9a0f1ac67b0f0acaf27395c18671cc7fd570d3fd2d5f35ccffbf97f28bb1903a149d9f6d558c1d891d4b17e77ced794536dcd8fc12e694f06c3495e1e3d53a7b7f2443ad084759c0c45c17c75a2aaf4bd3918555abc53015a706ca76a7af62c1b1e21c6fd2f14fe92af8596d7e607a88e5bef4b4cd6dddee54a16bc8d4ada5a20f9e587c372f2262f05c5ae25688baedad72fe218d3530ff66de0b8aeb563a2db766f10f2ef1f6e6d6b7d43756463d9db7b1e33c50f8dfd1a824ac328b1455f4986b13a4c7ed5fb9889d4ea9b97e19657add0f51225219db85f0bae85df7f41e5be6df03eaa30bac53c4a9cd35f09e1ecbe2107418284e223a71b189e80fa996ab918f7a1aadfe8818af372c3aaabba2dbdbca9967f73dcc67e998f7f7aec465c8a55e5f8fd312b66b384e02e1a1826e3efa566f919fc473a80cf8c8c4cadd5939191526163a60ca32d6d378aba73f23a93875be76275185a789e9e9cafe8bbf25d4b7fc3371ede9a98edbdba44edb24894dc44af2770fee9e1bc3d3183b6a2b4077c87c7227b1680cfe4c76eee5fcae1d925e8c13b7b92ab5b19d44e319ab878983617c2f0ce4ffd76b0cea9c568fd4e1fbbdba7f14c41ddfbd5cadb36bedd485d993a47598ac1a28d4ad5ec6e1584b8a8ed326de85a2d36e4a8006f918c636cc0935a8edd8d84c63f4efa976f53d1c4b19e55914d733f8c6252f9d97765db5bb56dc2720e366ab12975be5d78f9765d95c569f2b2bd9009fca5f91dd1975cac3b6f6f1474ad9ad59f729d10e0ce0142ae36fbdd66865d9db8363c309d42e61304d084812cb446b37003d6c227c9599758b1337eab02334602b8493cb87666ebb2fac84c8c9a866ded93ed56dd2d67cfe4098f1e93a646da233bb70acf55b69b6bf6695d910cb179f09757e1d6be865e4f48c2aade219248456c905bac4acf542bb9d31b52e6853baa6a0512d6e98a3b58c633346db4b32bca0edd4dc918a9f3d086b963bb3fb7bedf9386dabd94c6406784b17cfe29e5a997e386199c2b16c993c14096e1df5cf8f401b35d7a523519f51dc11cb84e77e9a64e6a344aaccdbf169af1b1f36c26397488ec5b0ebe94ec23b4668477022772cdd32dd4a8ec381a73a1997010924ff5783f55e54e996783314d581aed67c644b7bcf2b71b06ab0717c3af1e1edcb15626a1008bab8ebd3cd652a6eb30f1abc4a6c6999674cb8b19c27f73297b2b346847651b3a0177a96bc6ea708028bac993780acc58ff005985fed1f865ef8952d341971ed7778cb152748d71760f937105f1abf36df27b3564450d2c56d5db806e74340753808ebe24cd2d7e91674eeeb0df6f0dc35c5ab2b2ca16e20651966eb3ea78aba8fd92d72aacdd6b429aabb2ded9390233163fd6f0ff00f6c884458d2d8e23ced388e5fe9f1f22d773fa2b3a71d3ca897767b44a5d4ce85617e3020d5d26de9ad670fbff004f94f60132afdcc9b19cf05c2f22bbade919366dddf53817d76880cc86256eb3ccb681a96e4055c8c92ffd7afddc3ffdbdf20237c59f371d0679b24713b0e4645342f9f695d3eb7a8e0c476595e499fa80c2eb904b1cb9e55e4da8ad7d8d385279b9c5fb64ddd9c46fdcb3071c62d1fa8eed91a9959110cc9b7cb8c4b1feb401cf03fdb2396371ac9ae63712c5be3e8cc81dfa3be530ae9665a516f259ed6215bba9865a226c466e91eae1f7fe9f218ed6cd4c972c782e19d5f2dfbbaacd5ae443637f62219c37fdb8579377155b91405e32032e4d570cfb763319b7421e93599dc4468bde32896b8dfb15e4da88f7d8f387d62ecc3680b916ecbcb6efeb84d1d73fe37387ff00b7b9d5c91632f6ce60f91bf2cf9f64762c716c12aa83add8c23d04103a6645bbf731acf26eebfd45563685cfbfe9868c74e5fc7a69f961d408d7a7867fb8da8608a2647f8ee5fdc6dee84f32c7c522568caf89700be6298eabacdbbadfdd56658493fd3d53f871cbff003e95f871e9ab7d58f9bd48978d08bf17fdb76faed138526ed29b175023f5214bcacc8cc6b13fb6ae2fc37759b32b853d1518d1975e8c71f5f7056f6027c2dbf6640fddb7edc2212a5b06ac712e65963eff00b70bdb4c223f2b47207515e1413e3955f32c20fa313be4f2ae34b7edc9500dbad83158cdee649dbff6ea3b6a3a4ee0870632eb908ad0804183b10db24ec73c26d5f1627684cb1a669ed636daa6dc41332ce9fee135be6ebb84688300d8e42c318ef90875d3ce8f994c66d42d2c6ef98fde9436db6d1652de73202767fb751be7fc7f046c30d1f6ea33514ea3ec92ddb21fb6536db83216cccf7a531d8edbfba107c01350af5461af6f8553e7e66470c5e9bb05d5ec3a07e32df4cc76784db8f4636664364dbfdd0ef07310c75d8f8f583a960d34f0d1e9cf5d10f8ea4d8099738519566d917a8dae5ffbb5f951d97906104d42b2d5f62ce5c3bf6930b384aed52b90dd35e55ddfe493a1fdd888dd51601d9ebd45765897452ac1d361d7a4fa8f74ad4bb846a903ca731d57372b6df558d754d57f7eb645b62b031ab53190af24b9846296a9edebe014f5db9146c5d5953b8cc4c566584eff00be450604da904725b59679aad1ab5308d72d93ebe134f91845773228dcb71c86fea80d9fc5523535d993d02c3348d08d44a3789e8e1b4f9f9839111e9eff00d4a8d951d0bf8abf23e20854186b9a3cc188a146655e55dc84f0f280c07f58a3715751fecfc55ec57e396f53a818aca66ab319069416387d2833eaaacac8d1801271ff006a9c7cb2b2b60e3fa945ea806808c3f6fd90099a3ed88bf1009d33cbd1f2e1569dc4e1abb6d4e9ed7e3a593f4b64c4c7f2db535db1ad353556ad83fa745df24800967d9ececfbbff0092408186faf73a84ed3b4b5763041146cc1674866dced0c569dba162f5a9ab27e9fe9785529659f0f0727ff1fe285fa7e03af69b33667534eb69d6d31fe9c673da7c027b963a1013badccea33aa7ffc4002711000202000505000203010000000000000001021103101240501320213031324104516070ffda0008010301013f01da7f1bf2271b2717014873359a8fa37a782c296967df249593c24359eaa2ec5c16162d7865d98851f06f86c3c5a1e2c5929f1cb615ebadfb1f0ca872bcb50ddf63ceb62b6d79dec56c287e85b15ef79c7e77ae01e19a0d24224a3e4d068341a071ad8ad8499625950976627cd8ad83c4359a91a8b2fb31256f81e99d3474ce9942ec7f760b63ad9d467519d4359acd6391a997b05c52da515b55b459d0d70f65e543e22cbe1ab895ec8c135c55be2d714b8a5c52ff005cf8bd3c5ca3c4b629bf62ff000b5b44ac7c2a42c367490c4c7c128b1618a09672f9c2430ff6caedc47e3b56d57a70f0ff00b3e77495928b5c0e125a8f827abb2f3915c0274296b3e176f2bcacb1bdfd1f32c226c847f6c94c8f918e4396e9c6b3437dcbe92656a747e2a894cbddc89a5921c7d184e894fcef59310d51ab87965ab8764b896c65f8e224f8a6effe51ffc400221100020104020301010100000000000000000111102030400212213141500351ffda0008010201013f01d4fede8e2e0e3c9721a3a9d483d1139a08208208ba08c1cd4a1899c79b179af59a3a2cf24e18bf9ff39f2a9c692c4abf7f0f97f3917062e3f83358a2b53abda926de5456458f14eb3aba2d2791bb96cbd1e363b9e55926f47ca4d8aaf4a6f777110fd0ad5a5d912891f21324ec763b1362205491d914547a0951d66ce362121fb26d9aaa3bd61ea753a9d48204a902562b951baac3e96193b1d8ec48f02b10c56ac0b1c10884410455d9f2934557621dd1578270c91990c5456ce9ce643aab2749e1f97a1d8f3ce77b089248caa8f0b11348c8e924924e2421e2431136c526cf59e688789510f2a77cdc8747962e4b335721916acaab3a8c5a5f08d678de6e3a0867cc4f047e13c5241155ab0410c8cf3556ad0549a460f148bd58b4e71a1bbd1e85ca890f712ff49c50244e279e2690781e198c4b241e8915bec6b1221ea2749f035581bb249c33a3d8ee220559ace93c4d9d89a338fb3e7e02228f9936f0f76bd49c3272e77af02e49d245a7347879bf02f27abd593a7f2c8228ac8ea7b22d812d54359399c51c9fc38f11d1216bb1513ab15e91e8f625b88e2c9c5c90969c5e854471ac6e7cab56a24471de44dd145442de58d0b7b8e1544b7d6144fe547e42dd8bbe5eb5a32258ff00ffc400401000010204030505050801040105010000010002031121311012302040415161042232718113425052a11423627291b1c1d143536082e1f0051524637392ffda0008010100063f02f843bc93bcd52ea62e1658a2479dd5849774c815dde089e6ae8d67552e0ac57fd2a3a838a27e8b3b9358d1de753c91633fc394ff000b354c227bedfe4754c8f01f9d87affb00a8adfc58ff002b2b9b26f05cc95203604b92e980cb7e2a6506814e0107be462b857a2ed43ffad10a708cd84f799c0a0f84fafbcde237cf10fd7528aeaeb82aaeebb7398f784f09e1de1359a190aa34035827d1088fac4e1d105da67fe949145363c07963dab2bbb91d9e36cfebbb54ababe06aaebc4bc4aae2aaaeae31babecdd5d54aab95578c2a386a97c5786b45c951e1c23f710db4eaa6df1b3c3fd69c9a1597b38627ccf25cde78e31bf1507eaa48e0d8d01e58f6d8a0ded90bfe6ce3e883fb3c50f1d0db74babaa95757d1be9dd71552aa68af5543a2733cbe31b30231233a50c78182ca30e2617f2ac9ddaa03730f7da38f50a60cf62f8d97341b22e3c8059bb4bbd9b7e4175920b0346c42840ddd33b4224088e84e1c5a506f6d86627e36a0df6ee844fced920e6bc381b4be194385e4bbe54da67b3ed3b4466431d53a1760061378c43728b9c7338f1359a0c1c1347ccc70c3c211ed3d91a08bb983deea14db8530aaa0595ad2f772689a07b4bc421f236eb27668619d78aef1d920785941a3382fee9bb4d901daa1be09e6da85381da18ff23f0dba96655530517478cd6f49d516f6285947ceefe918bda223a238f33866c3b2c426433c8fad10ae0518fd9a4d8bc470722d70caf176e160b2b1b9de6cd083fb61a7fa6cfed7b3ecd01b0c740a5c30baa9c5cfe3245c6e74e6090798425da4c46fcb12a10676a67d9dfcfdd41ec787b4d88e3f0ba29c47cba2220bbd9b7a5d6788f739dcce1254c2e81179a86ee246396376984d9f0cd5fd1777b4911059ec6152f699bae52a7da3b6b6137f219fec88ec51a0ba9dead5192ae1c364421c75e702290de2c3628367923f161fe39fc273c47640b276712fc4b339d99dcce32c2d8b07e250bf2a31233f2008883385d97f0dcf9a74f626d241e88322bbed10f93aebee9f277c86e150d769e780a6e01ec716b8588420f6e9ba5fe41fcacd062b5fe5a3296f999d2002cb01bed1dcf8231223c93b33598fa2a9c610fc4839c640356507ee9b401450caf766a5b41d0cb838710833b5b730f9c205ae0e078a9b712ee414cee3541ec7b98e1ef36ea5165da183f5fd506177b28bf23d5f6afbde56f7dfd177dc7cb86d8c0514b0861de6becbd9492c142429061f35f7b51c547ece0d18f23d36790555659a01974e056589dc7e394713b9d0a9f1543faa0d738c583f293fb2060c4ef7161be95f732e79900a505a1bd4a2df6a65d04b5a6cba807289915a2cd2565dafff00d14b090538826792a852d890efb79714eca6a2eb27ca37711213dcc78b10840ffd428eb08bc0f9accd331c25a12d99eb7b269eeb7529b01ea18e89ccc3b5191aba78fdaa236fe0fef6ba619e151c38f34f887de3bcfb3cc6241e46b2538516045329c84491523d8e27ff00de8537073cd8045c789d4a05cce20a633a2ad2a8bdee01a05547ed009734bbbb3e588638f7a09cbe9b5d5646951a2f26d3cf7b9da56217de38088ca3ab29f5c298571ba9ee0608233bf872d5ebb021bfdd33594159c2ff00dba1389ff54ff1b1927dd8a32faf0d8ae0402aa9ace2f7fd06f52525c54f76310f8bdc1ccacef3373afa87c94ee7192cf0ee85e89f1a7f7be160e6539ee3371332762111c1e11f3d832c26a1c2e0c6cff5dea6880aea7bad4a0c67821897af1d52bd316f9a187d95866c8025ff002e3b30735b38d890c64a3bbf1486f565538502f0ab2b6e51227cad9eb1d86a6f927c67f8218994f8cff13dc5db4c7ceed9ed3e21f71a4fe89c799def2cd495b0b296e4208bbea758d14c991c42680a1f6369ef44ef3fcb6c0e465b51ab577706fb6575746aa6a4a851aeb5d39dee8a37726a0f7dd468beecf2b3c86dc46f91da83d9fde273ff00e7d77daa3546aa7b8d1389bca9b9c21d5467368e70c8df32af8cc6c4b9b4ed4420cdacee37d37dba28ee5dd0a140e3966e1af4acf821497ae30c9e6a0f6706c33956d9fdb085e72d98b1fe46cc79aaefb5c0ee41e654132a3c7f99d4d525715647cf186de6e511f3e320af4db84ee4f0a5b10fb38bc474cf90dfeea7b944c8eef3beec68da788a6c71467c71cfc8530b5905e8a4a5c7627b0f00f7610c83e250bb34fdecda23aa98c6d5f35555e1b57aa972539afed5eabacb6219e6d18ba31b31a5c539eeb933df4f96eae1f28034479292b22a4ba6054f625c5554b0ff00b54d8807f0e221f18ae97a0dff00fbdcdf19d6684e88ebbaba5d171457f68aaed744480bd5576a17afef84d36183486d97c49901bfe4333e434f9e00495b42455b407479c079a73cfbc67ac2db8530b2b2b2b6bc307c3929a97c296d036d1ff99c1d5f895d07b477a17fe1d1ae365455d037d1ff0099c18d9dddae21c2617b899482f65f66766cb9ba49163da4385c6bdc6e7509de67438ec75d89eb4bae10dbf867a3d76334387dcf9dd6538e5dda1dd3bad5960b21c16fe06a9e67b9769edaf743609cc30bab2b7c0e2e5b6632d1e989d19cf45c3ae0f1f2803e9a7ed1e040846ce7f14270bed0ee2f896f40abc2ca866a6d6fa95572c9fea3b70babababeb96433de77d354ec1d9969f683f8c8d286c10fda09cdc3a219e7ca428029092a9738f45dd64956a8c48ae9347159ecc1468dc2fb93872c25cd59190d596b0f341443cdc74a2c5cb38914c879053750725546430ef3b3bfe46a9c43268b34586e37dca28eb3c655d6bfa6b028bcfbad274073439a0c6dcd026895b09bc803aa2d86f31ddca1d07ea8b5844167265ff005dca73dce63de68d51b9c63f825faedca6a565c1032a43ef21ede33594a0e27d11676283978677ff004a7da23bdfd274dd25b9192cef333bfcd42842ef74cfa6d9c5dec229879ae45d125c5c4dc9dda9af7c72f3aea8d89ebb84e5de4e70f08a37cb6e5be506ad157071e0da6a8c2e9cad2d7890d8659f426779e657de099fd90c29acf8a6cd08bb99c27ab294d0a79aa9ddc6f33e2aea88072a6140aa30b2b6837b337f33bf8553b8669af4dda4a53dea58c954e95d7882cce727c677be75ef815e26ee671faa9ee35520aba739a912bc4a8aeaebc4bc4ae55caffbc2a69c97a6acf624afbb137c78614d6a6c5b0aedd28a449d20afa9250da61343615662f5dde9cb624719e5aab6a51586d570babfd15b68e95757a32a9cc759c2454480fbb1d2ddbcf6e74b2a0d30ab2d39b672d7f3545656d37443ef1c1b187f91bf51b1696e54db96a4f4a6a6a53d8bea755655ba94b44379a6b0580c20fe6fe3024a1a7d70bed7a6d494b4eb7d89cd4e5ac740634d31d3167e6fe31ae8f8b0bd309aa2953629b76fa6950e3456e0bc95bebb5d572dbbd743d30a0d4cdcf167e6c26afa53dc2ca7fc2b695709fa291a29eed5b9c24afa5240623f3614e4afa60f3536b5ca5b43719ec568a9a37d5aeb66d87749634d360147de7c84a4a27b4cb11f13c323329f50eebb43749e915cf56730358346c45f29e349cb4e46cbb80edf9ee33e4bcf57bba99b92aee51bf261d14b529b74b6fefa6a01b9c5fcb8568a9a9757da0ddc2a6aa7318dd78766fb31def6d330034e655d78cea8f3d9cbf31c2bba4b729570a6dd719299f7a238e9803d5546b4f96cb59cb0bfc1386149290d1929a9aecec94886d74bcb0a4b56414b65eeeb875d490df2ba390a0ce6e9296979abcb564a8365cee98fa6a358e6ce657b080d9b6872fa27ce927812f4da1b8cb68f55c1536015d9b94f37e98d37627683799c46a4c14223cb9eefcc8b8f3da1e5b8f51a53071807ae32c29b335d782aea8da0390c4690fcbb555c5530ff00ad69a92a11a15531874530a499f84cf4a4ba6ae676db8e30e5e20e74fe92d1962796d5b0969db19619a655d54edf459861d1676d8acd8d3432ea81b4e76c7a695fe8a7b155452e3ba48df466107c3b7ed865376227870da974c7cd52cada93e5b6d670d81a7d71b6bf776ba2e3454d2ef780dc2e89ff00950f3c2db525d24b86acb6cf4c4a972f81cb565fe487fb27fe89b8d3649e2ba6acf96d9729e3353de2b85b6eca94d69f02a4993dae38482e1abe7b72e7b892a2466bda72d3d7428aa8e8952d70c68992a6f2d6faa0ce414a6a6a8abb0ed503432f2dc62c07b092f14213a18264efd3cd196d71c2aada35dc1d14fba2430a634525427091d69f3d02ee78cfa6e54d9aec795f19296e713f50a9b6623c51a268996a8d038da68335e6a5f5523b530a4a6d52c253553f552dd08e8ad8dc2385d7b161bdf5a6afb61a0db19b913a33870086fccfa05122468e5d1030e50c149cb19cd70c7f5da96135d70a85e2e1852bb9e53c93b0a2e38dca96b4a68ed9384cd9651a1f76df54d899186312733f8a1845847dd71181d1be135553e2b2f2c6c30e1b904e1d76ccb7590e2a4dac94deba29ed5e7e58063479951619f75d8c47584401c3192be12c6dbf029d3da277699a0e0a810f9965c7bac73bc82a4223cd4e34590e4d460c21468135c1084d1373ce51e68c078cae65c2890e7e3663d9fb434787b876ef854db7eaa279ecc94b96ad342989ca733458aee437bbd1668af0c1caea8d1fca92a2928eefc6507186c8439c6329fa26c5fb4c01598a380fd97da60b1c0fbc2f3f2506293e1748aa611a08bb9b4f347e0edf2da9ea5061d70a6c650b2c286e79e814e2c4643e972bbded221ea64a70e0b07a6d6413ad2974c6185f68edcef172843fb4d739a1af7994ddcd582a0ca7984e8715a1aee0f01361c5a3dbdd38c5681dd777dbebf070ee4a9b0470d5014d48a9ec4e5919f3394e2974677d16463434721a27b4bffc60b91ed747457ce73e2a2468ad9969ca01ac942c91a79dc21d0cfa9c1c04b3b2b2430ba6f6968ab0d7cbe094c729e3b04cf5666c30a2aa961918d249b2117b40cd13972d48a262665fba637236597e64f1d83b4086e778a934d87da62b1cf642cd202cb92706c87926c4e5fdababa7c0759edca9d0dde26991f825b0047355c72f2d592ae1654418d1374e8817563713cb62865b74c03858b32fadd648732e95f80f35f6787103fb53fde368439a9e73df02f57177128813a2310b1a5d2baedc1ae25ad76465682dfd287107bcd9e2238b44bf98f828acf60b94f52654e4a8a85554854af6afac5fdb0be8cf0905dc1de9e66cf9afb9a1f741e1d3d1389f9ae6ee2bc53c9c2c9f02b386647327ba1bba347e2e8bb5c171e01ff5aa89038c374c7962f87ef0ab7cf5baeee11c72ea482a2b614595809257b48bde8a7e9875421975d4b4b32aaf6b0c899f134d9c83233bd8bf93e9f5b14eff00e4332933ac508b98f1162ca4443e3e6e59e29e8d68b342f68deed289ae9d0f74a9928ba4e7741529cc309ed1eecc274bc2eef0f829d7adcecfda4f89d6c0abafb4bc548ee7f7a1754c2451532b29af24270834e5a98628e971e8a824aadaf555c2510bc96a3df94d097681947acd78682b3d21c94c4abcb7a96218c1371b053fb3b97de42737cc68cf9633c6106fbb4575752b426f88ae41482a954d99a90c25d54d49594c9a0520268bb1985de691f9577213479d5778cf4f8ef56be3ed3dd855565646705ad3cc514e14597472ab27f96bb3252c2bb05af1dc2a850850fd4f208428761f52b2857529a1b13545652e28b8f9ec52ea67e18d9f3c5f19d78869b5df6029becee78639a5a0d80d6ccbacbd9b2fef1e6aea5c54cd55a72c2eaf84a58dd1f240e35f86b25cf16416d9836ea7c2a880e0a9b16d80f3ee349c2651738af65045e8ab579b95217559297052c6735e6812e552b2cfc94cfc3a1f9e8922e6ca559953264baedf5c1b13858f9298343848597b78a3ef1d6fc381230793c36080a6ae0aa7c1e7a0143f3d1c8df0b10cdeeaa6cdf0ad4a99c7287505954a861de16f7b09224d106b04d4fde75d5d482ca0f99c32b54cfc3af8c3f3c7ef58228e62880cf94f27d0a9fed8922f245c6e8ba7b254a4baed879f09a153cd359a755d17da5e3f2ff6ba22e0f5fcababcbe270fcf1b294a8bee62b874350b2769865bf89b50b3c388d70e8551eb293b555267eba3943a9d554a870dde1254aca855549b6f87cf62e3087e78db09a2382ee3882aa67e6a654b67234d389e7a8d88382f6ac200553557f8349534782aecc3f3d92d08aaacb8d709acacf08faeb775c4792a9f839c2830ebb77aecd0d909df60a729a2fe58d155646d071f8b9d992be90ebb45140635521f19a9d9aec1c2fb0cf3da9ecd0fc62b7c2987782e988faa9cb12a676038dbfeb0b4f43237d7e315dbeb8d76253d874f960760d5060bb8e1d54cfc7a5a8e1d360aa619be41345f105780e6a7fecb643fd55158a3b0f744748959dd41c072f8dcc6e35f97668b92e121cbfd873d0070f69c4a938e145438651ebfec0a530aaa688e8835b7597092396ca8092a4f12772ff0061d7091a85c9da0f8a6cd12d9ee923c957e3f355c2ebbe14e1955d1683e27778e16f864b79a6c77866f35dda1e4703f378b6590f85ca9636ff0062d9515b19154b70465e1351b111e79651f0f3e5bef053695268994d6c69c8052846678291c241060527d94c7c2e983bcb7799d9bab60f7f218514fc279f35400f59accff17ed8de8a87e115b6c3bcb4a5af7da9f3c67b53145dfbfc1b33eb2e08faec1f2dda784f1babababa60e9a7fffc4002a10010002020103030304030100000000000100112131411051613071812091a14050b1c1d1e1f0f1ffda0008010100013f21fd271fa4363e5057eccfcb162b019d6e04b3f35c57393970fee5062183067e0d4ac7544aa8cad33a4ca83ca2e083921700c3cc7b999f1132f69229ef78c7f28b0951762b998c28ee9f93c4338832ff0044bf826040d1b7d844c001a65bff001b41e792a69f5f3fabe3f5df8ec3acaa71ee5c0235f1180d18c52ea5d393293719bba9fc54d2f6b7ba96aadb4f350adcef17140a2a9b83f4bdb3a622518b5cfb076b82e83bb9133b12e9556836b2a0d1b0d76079e664eef7f1393f725ea673ff0081983df6b97c9c9e7ebe7f47c47195a833125a46287304749f5a75788741fcc53681d450cda6393182afde5a03dbf44e48e0f0671f7890e7509a399be8e4a00cdd5603c4e593b91edd0e89805f459367b687333869379366a522af2fc3a22822e72d8efc32ea043baf0ee3f5f3ebdfa33385482a5862ae56a1a8678194d97de51533e608bb7de2abf713204c4f84a1885b5c55f6836c87586a68bf396c8cf3cb394599394c420fd84d197dd82960f68263e48d57dd411309e9bac4139f6aa97cf5d775bd5ff734b1a8bccdaa8f71e2310a88f6238e9317f88658a3ab2a239fbca7719e13ed095e7c517230b2a276cac01f170683c3e61085eeea1221584a327d5d6793fe0c72bf7b3dc6feae7d62f2fbcf365d207aff0038fcc3de0e67949481801fee57a59472b28cdc6adbef340b1dc618c7274db1e1ea1acb8f306cafbc0d07ee80ccf963250be4c098c0187d0dc18da0edf9e081b780e417fcb505f6935f0214347152ccbe499f03bf8ec44b00ee47de1cc7570b917b23b7996ecb8442eadbf13b61db8b2a00d96e7eef1f1057afb47155aa5509e1e478088058c556dcf51a6655178ecae8856b7e4d3f88b812c5c3efa817ded68cbafd08cdd14b0b50e209b6226e5fbc1630e756c1be9e2dcbc6e3519dfa4a732a0959e96ed899a2b022daf7958238dca781f46ccd6371c5338b65f697456c4dfb5da2c93f3911da4dbdddcb6bfd6dff005051e2391d1da24f2cf607d8bf696e94f3c4f7985d2e67d3714f29c61e5855f75713964168bf77897433ceafeeee1abbd7cc368e90025ab7a96130ff009a1bb3b9f5b1a61eb79b2dd78e27c840bfee135ee4ca7c3fe20fbfac560cb6180d4688f538ca5444c70ce34b450ba8be7a4e3d1ea54c331428d9376540a17b5cc092fc4aa19f2a445f11ec59466bf68e4e2574b127063de03b668c5ee623e587830fb5fec876bb5fe3fdc73b8629c3a80e36abaf8fbf996abe3b6c8af420be2ff39851fa332cefcd5adfcff8405727ef7de1a81216b57716e59088ba2e5da5c673860798a05a5b05327a36bdee51a9aa6a4fb4530db4fcdb96c04c5ad7ccf08f9a27dfd3a66750e8a0e92ac84419161279cc1f4db8a0b8d289c7518189588f4fe29cb0949f7d06d97aba3953fa8852b96d95b9cfb45a909a8b2c1f69c945e6a24da029f32efd1be848de3bc74f835553f941b006749fe4971f0a57f12887ecb5fb708955d81bfbde66885a7cc5364b9567572be3f084ba2242bde567ba36f8af548abbc057864dfc53ccddce395ff3c73e99864a957131e82d2679640768e5ca465f4da5e5ea2c4254606212ba3888c5678e0fb4bcb99e394a63cbcc5df65be5f8227626f64b5e7a59873c42ef9a941ba8ea6ff6e9e731fcca573487e04e59642b6bd9f84a502bbb7e81a55a5531d28f2e1eccbe616d8ca8487ddc1809c89ec8cc9329da57e3f4086c6d148ca75700b3f0e7e20a08f7527c4b38ccb3cfd757518da5c0589552fd1c4994f1f495717b448d5103a241025d131cb600ad74d505410b90be9b056a58796703fccf7910d7b7d465bec3a4e67e224555dba7b08fe65329d63365f9185ef2cb38478cccb5f52914b369165701e7e4e60863e5392bb9322e3a7179a2336456dfd098f6ff00135b107a1ef04a6e2ffa20886de0fb3a63bcf67d570822378813413d9d018109599f40ef50eba02741d2231cd3e8d9c4c71033530f8f32d18edf0f78f1a9783a4b7bb8fabf223dbc32c14badcc47cfa5635773da02cd2c7447b895ab33b38ed2aea889ff0038840bc35afefe84ce7e7bc34e4fb4e548d9ef99112aa34b87da50b08f6260bf03fa226fc270439710c0c1346c31b07bb895b70689f49cb638c5c2d2dc7553773c0267d30179e8943129a4b0d744c3aeba35ea49d1944eed5d2babf7e3549e1dcfb4b5e7d0d77ab94b73c351abb86b9eba60d609bef2b1a0abba2c52d789e0c3ff000ec4c28c1bae88016ba081200a7c7de6adc459e4de008a775ed32556fbe66b45cb48c5c20764220e8bf773fe3f4820a16ba5b1b48e5d240b6d40ff0087b9055832b2b2fae78131ea66ea2f426f3b4af200ab1a521946384cd40590a4692dd19eb4a944acc301ac6e7b9cbcb040873e9d7bb19895b1dacde7a284dcb68697f30bd846e9ef05f135acee1fee2b6de94972c8594b952a0da5c10e61276cc2914d4bdd62bfa1956b6ae35fa859cdbe20f41e8bec7682515937d86284df18cbcbdc1e6a59a7528625fb700c767085d3f9d965509c0cc6fb25bc4c1087c3a0cb84c0e25ae3950b95a66e5117eb0c5fd04f3967789852ceb4576a96b95ce30da8d165c29c9701e63b99afd9d750b01b47bb27f7f6986a2b8dc4e585ac456dd4480c3af75151c2b6e6dafe7f52e9b4b8068af961a015648ccc88108e0710e31d4a31630dcd290e507dc1c305722d1364c994ab9436815980e80855010704749ec9f1cc524947239f42deff004131ddf23b4417edaeae85687785b283ef28d659c475c9beef6ff3fb4b6ab3d545d2c3fe3feb80c1c4420d5c5ef0871d2017ac9841f1be0bfecfd551d82104d60ccefb0e9a894a97932e7a16d18330b07c4d2638d46599ed30d33155ca545c746f30e903b80499a51f28a88d5b9a9fe3d02b9fa3f029c0c8e7c405fa1a7b9a849a83aee4cc811b23845bfbafb7f88e812b1cbbfa137249f794781456c2040db7c47096e35ef304c52bfe4bdd7f80fd485b44c5b7de728202dd6a12050c6ee1cca4626222b1041844b96cbc4e3200b85c6599763c1bd00a841bd4799c24e79e48af2fa8d2837b981a9580be8684587bc5578993bf28a5c3f46cb59ff799c743f12700455b96e930899627127c231fd7ea482ebe66a1c311eee8f682389b17536e72e52e50052feee377fccbe090633a5433027052953b487134d4b2648732c73966d027e111dffefab6c25e3bc402eb8e202943677e86cfccc640c9a4fb6711b4b51eeb7d42dae888474d9291d2f973f99aa5cb15bcc171655cc583f028e8b6a2bdfebbfd1e2abc11550a80975b942a54b0a25b611d80968bcd47d8cf922a0e8d332f625066612224cb987de7be3148132417980fb3fc07ab913cc2a7b0e0b896e336353f8baee60652456af1d7ddbfb4c95752cc9d79cd75fcce3138dcdfa08f04b5e0264eff00e87f581143062c4c0258b2a9a14530933c4b480415d43c2d33da50c973b944d53c93cc4f213dd3dd2fde79e67dc1854ef09e3d6ad2e34e178c473be9c4ccbac6365f12864ad0ed88fe3e8c575b8decfdebfb8ec41d0b66944ee305e00a3f9fd605b50a37e701c129a5a89cb44aa5980620dccc0e4f98f55b8347330bacc163ee9dc44d6e7790b99ef2dde5bbc1fb4c472a7be5efcfa9c74b1f06e385db8eb9a21583815ae0ff003114e7e3cf7e86fbc30ca72e2ea2ab96fad1e1c7f3fd4b41e3a0b30cc886e76e8572617f7bfd41bce3e8714543b6cfbccbd8941f99792d7d070a2f8a2065c7144c7348f462e5d15f8955b416fd6717e9f1bebbfc330e660c5b6fc8ea3a3610ddb1f3a98fc4b736deea6dc425ed7c87ce7a3b57a2e9dff21528af99cdd1fbd04d77ff001bf3160b5d17e5fd65f4dc09de82a21689c98431b962aa265a9d68174de3458bd74b34c1480b4f68e1d3fc0c1fc7d7eeab843a3109bdbd086e3cf8985520687da03475a9cd205c7ae55a8e4307f12e699334cd1adc36630cc726f55e268e73d499d35fbcb1f2ea0aa6ce64e4ec7f6fe22b7be0fd6154b50c6b13222e20c84f30de3306a504abea6007e854d4e423a18e7339dfe25a99fad634f6b8e8851edd410295b86fbccdfb44096030fe4b0dd176c75b1e6efbb885699c39964c02cd09b9952f995878b7e615cce2c8dd91b60b078e86e59e7cc567b74d5133060f70dfe6ff005a05df69c4e2b8e32ba0537d218984a54c554065bad1c43084d3a51c37302b5a7f6c7a05e45ce2140066d2a8151af9e96e26a7cd1da4b3263b7cbf3d1b52f15d4cb50831bf2332c907c263c95c54df77df69a197063955947e8f32ff00c0ccab29f24a5c7e10cd44ca14b3cbf57cfa02fedfa0031a8cf663771ba2617534825bd0c7d1188747d24ee9d9027edfefd031315e88f8035e61c0d3b3dd94f21fc4a0de5bbf699ddcd3994769a50abdae1a4eef3f4645c3bc49892bcdcbee5eeea0140c976b719593df1a8f88ac157f465e2cfb477187ce500e26de45fbd7ae7afb2f55996698e2014be82be20d44413140eb58f50b5a89da060315026897f139d8ccde016facafdba2c2eda1b95d9a6ab70466c06389f071732a2b9dc5d0ad6a5d869f13c04ddd7f8199cacc20e00eee53cef2988d9088f2fcbf4fb781f9c50f1f299543fc9cbfd7adf01994bc6bd70abed302f7d14bde78d9774c54ed22b15286a6e4f04c3a9e1835a9ece887689b9d843559ff0090f2c5403e7cfd76d540b688296a56b172aa445deaa588f151ac46f30edbed34f960cb8afee5b141b8825e7aaacf6838766a8eccd052b54fa143ec3fb9ad4b1bdb7ed37bc9eb2c305f1fa03636c8fc40a31887923a4d2786225d511eb507c256d4a0d4eca63e81c7415894850c3122619bbe730539f4374ad730db077e21acca8043737fb47330f74ae8fe392a2db3f3d42b421114dbad69f4769ff544c025b0a3a3d6223bfd02073ce3a537aebc98219841758533c31849ee8e0e80c79e03bca5b20232019b06c7d165530ea1a51d2a7648564d40b0417d42d53f1de358f5f72fc454b0ed0ac7a3c1ff00144c4fb4f3a2d7b1fefd61bd5ca01d01b88c1f3eade0e97c46f8b4864f5ea24aa63a2a8cc0c2c1b8150615ed0c2074dc77d2b5b97453a0d211525c3b9819a2afbfd615a2727e5d5929c456fa1c82ea2b4f5ee4340b6d66fd176fcbfd4a917766bef7fd7a22f1cb5f43ea7dc57fbc29daec07b7765432e08fcca79e6236a283b03c7e7ea3d025cc9bb9588618810a3712739169be9b56e27a34944c4a40830480ae65b8bc67bfd6a8e6a154cdf98de1477449386bbf4d3e83c06ffafa2d8edfc6bd1e77fe54a7d911cff4c7fbf4083df5da68b8380c3b1ec266007429ff001cc026ba380f6804f8106b053922258aed3b01d17c19ff001e9619fa4b54a662d666e84ab03de79203b92903aaf34f24718c12ae67925f4b9d942b37d14d5d63eb3a8d4f257b7d6709a778fa680a59577af46cccee7de1291d54f8c7f5f513883acd4026f2b1a485098a7785c4514f80405b1daf4465bf0e10d543c99e6833bbc9d47407c407e8080efdc5353b88c257e99c7667cfa4eda256a2f688e23896e5dd40a7caba60763f94d4354df99d943ea379c75157adf3f45181c9bebfc0ff001d1b2bf1bf476c8f9a93cd6bf3d06146cbfb7d0b86cecc379c434c5ee5033bdc6053a8e3fdd32c3c0262c80e20a9e697476d4cfcf6fd15e0c2722138bf785507b42e1035d0bd809a96690af129808c8bb42ed3b2837a8aa3fd9d300557b400b22f117c4ede893dc3e8f77ab3077dfa6b4087a833f05c4a576f4b6aafa9b9c4d3bc4231810bc5728eece2f736eb11a9d6d61fcc148f1acf758fb5ceca2b81e768aaaadafe808f9d3da07885ed0f644ed035d16788c7130424a8105e251a82e23e3d681adcb846527414d3e9f65c74f9e9609d557b8a8d71e9667de35b55d3ef87f9fa8cb53b07f2e22c9d397515e3f222262a57be8824e996dfb4330a130e4bff003ccf7f5541ec6a18fd11b962fed39e4ed8e92be89413b68530c3a963504e607783ce0021d741192542d45779fc1ea1350e61cf4be256719f5ce7d8b0681f600c7e57edf5186536e09a0e2b5d09c4e18abe764796b6ab5fd29bf132c63154399ad46e8919c47114a8b31ba8289cdc130b03453d90a862f8484c1f53f23e86b1f3c7af888ad6bc4bcdc7f87d24c2d86ff118233b9bb2b58cf455fa73ec89399b58175243c1006a568138e81a71d2a3b84d3d1a18883931fca2ed7282ec7e25c8d5cb9a7e4f4ff3ba1541935de59dadbd32c2aabdf3ebbf058a9c1cfd68ce5311db22ce6a3be86f30a3931ef1fd215c2da08f94e00d46728e8ee3b4663b2b1297705953a421b638e67913b466f08a35d0a333b9305f2cb4b91940af9dcb3f4dab359df45695f2d133d97c265d07e8deb66ce296376b933d46a5ff00d51f0fe8cccc8201069f12c0e05d5412abc4c84229668c80fb7519bb12882431180a94962ba3d9809d04a5ab5ea1b89579d4165a17bcb64403c4b391e5cfe885b51eb9ad30997b4b965abae57f82517bfb7e92ea25cf770095fcf4146c698e5965503708d91a92a0eccee214dcee6669572469d93b0cc107f841de27d9c1eda8ef1eb2003d5cb7573bdedb97649fa20b42c9f9dd10592fc4b24c3b12e7ab253192ef994fa86676add5934cfb4d28202e0fa37cb0b8db0bf3377bf4a61c7f72844fb32aa8adb49982210023b645ff00acb7ba2d1f7e31f3f785b65305a67ce7aa4021a74f7fa33caebc1128f97a778afae83496ef32f7bddf4d6f999a4f01baea6cbfbf3140bc63314398f317d037d72c720b4be1b61464df4ce55c58155da2a3817e272a5456e1980d2ce4711ef9d47a60dc5459393378863051576afa04a5264cc45f4772bd3454caea660ead65b67a5337be3a6a3bb0081af4c88168b855e7528b828fa79467aa82b75459293e5eb97b04aca7863d305688158b8360b3ef040747d029a6a57637de6030c0ba44ef58bf0823ea238bf388b05e657e2f46eb3c4455e90949a829ccb6abd262b315ff00a82e2a136b19f9ea3fa22af3a8341a1eaade95e23edd4502c0da38125b57d10ba8e586c7702da2e704c95aafad55bb4f1856a32978f796228ecfd0047b4c595454bf3f478ee79400b7e621ca795f6889758f7f4a9f6717b12b2096c0dffc76ae94dd54cbb3e289457f35fe8469b233654d5f54bd45b7a1b9818366e2fb7a422862adca38b8eed7d1a98aad4d7bcd3cfa0ab6fbc5637bef37fa04110ab0a19795349ff5207f1260c6cf3c7a2e66d5444f489a03108d8fa2ede62b73c24c6b8fb7d6441a46e291ac5c31702fe679096f244f8e2fe9afa823673107556f17e902b82195b75e22db6f5c232ed336941fa92d5c173142b12e77f4e07867e37a02fa0d732cc3f98abb6fd2bd719cc72d81a9a7d230ef7ff9050857edfac6a252a3e5d4d7b74768aa5ca66888c5fde01f928961a23064a9d757199bb0d74a733f2ed5d5509732d15e8988e44cc55dabd35a8e1c6b6b810d53b677730168fbbeabbefdba828b478888fd2f255ca2efb1e858ae8e8d1b2fd4b45cbf1d2228ff008e9e8759f1a82601f78ad5efe863788ddf1c622db9e829a596d55b5f4f0c5f8ef8e8a9b8aadbd5507b4468ae0b47df748de8016e222a23594621d930d2d4856a2db71b98a795ebebc838a944099c07cfd2615e865e172435e77ef06468f6af490c6d610d3445d2e6fb5ff9e89f27f944b8d13d2b7bcbf0a340372d08ff00de2051613ea6df7c7414d3ebda29a351516e798177befd32dd287372a52cf8faed78370301bd312d86d75ea237d4e25e2eb8c455dac55dbe9dcb306ba77898b17bbf974255a2e106979cbe999340a63ffa510040e037c789508a8d539fa8212e4fd011ddfc10a372df10f3f151d662f2bb5c4f13d0c15a3c4c4930c00e087538f5059c1e63ea0b6bbcd441369bcd4951ec7ede956ae0b8bc1e9284ae4b959b2048d8395d79239fab837d98fd01ba7795d07644e4e11a839d4c9779f44881d4218ac4ec1e7d40a554216adf478fa069b2269ed14dfe8d8401ac212817dafd2d07b947de6d028e1de21b1b80ad129abeb7a56ee2c012d5fa030dc10270cb7d3de7f59545aabfbf4c2da2515c57698828a2506057a816d1307dba673d2eb27436963fc235e2f4adaabe81f28b15fc3a082862ab6f455696b8e777eb98955bfb2d96b040886715de0c2990e657317c4a8c0bf398e5fa00d47b4a11df79658cd6e253116bda5dc2c5ec67f9f4c2daa8c17de5b6b47c3114d7b59f5459c23f103bc6464065648fb74162978e7f464527f4214d2cd22bc4c85a2a3e4629fa8c4bdd481bae1d2e92582b9e201e0fe47fd7a60b3cc50fb3d0f517071d01e218b152ade2afcf4395aebbf9fd2698bd7e7d7c748606986a68f7cca2dfbe372b1f92a59a5960b8f432070cff00119b39599dc06458fb9cfa595e328e65d55f67ab4d6d947e5e6020218c371fb8e1d1c783d45008819df6820ba79fa9407bfe88534c3e4ef70a768d707a005dfc328f2eafb9987a01547a55c739304a64f696ddf31fa0c7d79a6b7000a20a0fac7780a1d4a1e6bf9f50e0c7feff00cc71ad090ce4912f318dedc9fb430dfd3f8bfa00b68991b577fa4dccd0d87e10602dfccbdd0f983e2fdbaf8a58c2ba57b85a9579e8258b835a7d5547cb14e521a508fcfabfc7469682a8f1ea7ba9740df89dc2b5ea09c839862da5daba26a46cfdfead3daf48fa705d35d3cbfdc4e531da517878e7a08286b7d4bd442957db316a7118131dfa669c8cf9a960e87c04957372d5fc7d352e6c47ca1a727e7d5ca37be8bc412a040ebed6d138a155bf402e1bc442e9ff002a05c4453882d8313c5f9865545a79560940bca5caafdfb74706f5c8f5897ae6420de63dae70b8ae60b4c7c753a9760e260b71d180730b3edc7651af052c5651605232b25c739e61216e750b688111c1151c9a6e2fa8078382604b65b05ea7b9bd052a9d4c4bc37b1b7f6f406a0bb102ae0bb71a7fa8ee78e60aaed401afe7e8c14a814cefa1c61f7e3d205d13cefb443624d6ca6ef987724ac60c6ae6c8bef07e7eb459cf64fe029d1485cdccc2b9cd007932f49e3941602a25c4ce40535d721c11c95750042efd40568dcb2b8226ba461d41b9db618f78b6db0dc78db0d25767a20af6f78d90e26bba02dc8e21a315d40caa0d565e63a57e5d71cf37af449519da71da100532cb4b51d6608330b7c5e310683052bd1fb843bc502df7daecf4073ba2a58b77a8784dee5bd134a52318d769516deaab23cfda6d1f125c7c80f533331fca28e3d38618cb6601dcf516621c3b7a2446aab9942fb186736e3bc1b633e582e446b9f5557c44bd03ccc75df4de26b1d02ddd7656e512af0687408fa2156587c5de616ed699edf767e430631acc62cc3749852ea5f4a9ee8a34552528d86ee2f0f8f4c2a06d945cb6c21c22a19c4859f613ada89da61e257a26e3f53504ade35e8a2d108b157b2e205d636dee1ef2be85fe789629e3997b5b7d4a21ce07cc32726297ce67ce4c68e9c97874e6b6ff0012e0cf4361ab899bae5551b4cc5bf4eced09a4c4a4b2090d4063412e7b9ea2c5128b9fd024d29e82bb09a059e67681ed2a20b69368f00eb84877fa25bbf55dad8299acacb6cba9a8209c0cd865db44152f022f392a9f79da0779cf7c37eae76b329a8aef2dd14c4ca7e6fa85b5126265c7a84c09032e65640ef9ff009702a93301747d04f0247caefbc4542a660d67a5e08c5c7d23b6e85acec8e995d73ebb1375013784e326189e2b8ac2d042e6350363374276b99c305c12c5ceae53444aedea7e7084e808f5bd3989e21d52a1a9653b7ab4c9c334ff00c4e4754bc3d9de57314eec55d5aa26436be2114c39c813163534225381c6a719c743a158c16ce33510acef528b19887e8156abe419de46a10d711a0ba8b98f7082933426fa29f59acc2425fafe585084bc11136edd714cc439f585d5f4b4ab9832b798154cc89b60c11d37cee28d69de60d8fda1ad2276b20b3572d619492012737340e330a2a19e65b0774d5c5bdfe80b9aa6a09ba5dbadf4582ac42a6ae0f0a95f4b0f32d3dad7c768f8f50d27bc056aa0745203a227b217075e4c06e279cdbeb19d4714ef1b5964d25f6af7f1d05dee1e05746eb1b8d7cb72e510ab38ba873987982daab4cc4d6be630c1be2005db9be7f46448ea773af50d4f08429d657088054c174c19477496efeaef758c4b8326f3d0bd4f31109be533efd71979f88cef3e864d55c246ff00fda62f94293896b973e262ba0e419d4705f0947694555626a7fe67a5e3a2a2e56f79f329e6ab947157de62408c852e72167164ce80bde56c593ca6c0be257b7957e8de9ee28ddaea279a95b8dce5a3766e258b0568fb93019d06bcfade11128bc44b81988f799f31304479c5e96a9afe5087e7f4106f437c254e0555ed80ed0b0aebbcb0a6a9c7c4b863f9ce9902e0fc42558df9eb4b5ef0035d3e2242d9e264b68bed2d95afbc1646e6c0661a4d528220d586ae0c20a5b1da32e25f69fcff0044dc7b88b777450d5c2c1dbad4f8cccd7ae55b710c3715f5b65c9a9c62f621767e254e1c259f77d26e3dd5f67de0ec018981fc5f7189e63c6a9254ce884579758a7f23f7e884aea324ff008113794f68220e4eb71ba798a76afd422f3bdcb2b59fa0534fe842da8a25bdd74028a8aae5beb98357afd07f0422d50ee832b945c9226b7abcee148ac96d5bc4a8075fc870ca4a279c253806c3329e222bbb6aff00b8b6c8aeeca6520f938880589d89785cfcc74d953bb42be1c9f9bea85d732e2d6ef3051c3530d5a11cb78941a36be66d0b77af1fac360cfc4a8297078007f1d7c33e23c33028971551e1dbd5775088d3be8aa0711ae982cc5e304b6a0b05d5f329e08aee12c569c8aa00ec932ff100af9c5a98bd972a139e25a697e23edb40fb351bbcc9f3ad96126a1aee3de2b85863a1ef4dcba400f65ddf6dc24744bfe22c543dd83e2327f13070ae2b93f651bf69854cd7bf5d54f0507cd1ec7a82ab21b89ce64ef2b1d42228c48794154b612c2cf76781e374a339d9ff0087e67f1953f13243392efde148ae131638ccaafcc555ab352b1af7b28b4fa29795dbcfbf139a5c82d357cc4b90971b27ecb7efde5a8ecfe7bcfb4c06bdbda14a9982261217c15927dff6405688768aaa31a582a68cc44c38e96a31b959223d5f11730da866e7f5506bbe5697abe8df1280dee1a3e3bca921e1c7dbfee52d5e3a21c8ca6445c4602f789b332b6581e62165d617cf1f98355a433a73ed3c729d19473c5840e47ec55f996453c5f7e226aca96baf1a8962eaebfc4b42bb2670084aaf88efff007fb254566140317e2691862b6227965f897106a1da5b77ea0ffee92b6236631a6b98d81b21b9c7824db0083e47aff79c42ce35185dccf799625952b16e55ee00550e467c5ed141d5193c3b54b8c0640e718ecfc473866ee555f6fc4c018042b9a9be4df3c4c835fe14942ffcd4a91bcd8a1ef4bc5fb1112a88a6287b4050f79ce830886d5da03947c2651d7aab4d37082450ac863425e7489190503729498c9af023eedca5dd446a58b2261b63d0808845d63ccf38c44c916b991ee143fb88248ff39c3e21f475a029e41f8399b5195de0a77b63520fb6bdf863f2920f3da31dec324dabe544d20d570caca67dc9c3beb1d7faabf3fb15bb8ab6ac1a8c991630f13f9fa08d2c3b2d1888cf6b7ea1e4afb44d05ca8695cc7aec250d200329a0945ee31351a733bb0ab0e21f32c7425ee109edd0b4c6652d909be6302d6582281668069fb595e60869f3972fcc186dec794be2f5e0ed1777021a6af8ed0e2358aac4b1f9bfc4a10e6db67fa37da650357be3fc9f88ca380797fb97351bca80fe05fea2228ecf54cd6bdb38fd199840154f13275bfa292cfcfa943bde36268c4ada4402b4c0c208ebb043358b071ed88cd4ce8a9e3c4371d90d4606f33863d198eef4ca71a84708b5412edf69fd84c7a55d81e4d21a6020d2697f1f912c03d9e0e83bc588ab154e3c1e094ab41f60012bf80407362527f3318df6d669c7704541a180c865d91319135f338f784af73efe9585175fa8be98af698434915156df4b34d7a82ef911f963cfc4cb50cc46461a0bc4bb46e16620cb55c2ad4c21e3fca76c1c09c08c0bb8d5aa80106dd46e0925b39a88c7b4a2edf88afdc8526d56591c46b4e82a1470e5de60293e2dff0032b0b3cffd476ddbd2830510764014d0e9ba97451c0b83ed2b129bcf4b305cb24001ab9d7087ea1532ed6ebc43dcff00c82652c652da0e5978a1e261e9f953d1cef51686c8f9fbba1b9868aa8f39858e05308c9de5f8bb7cce14651e09f96739c0f986af28d1856651752d2a5b92030e8737353f30136b708a69b618c59620134382e64324e588e6dea0f67f32a81b9100ce48778216be99b1b526bb57ea49437b1cb0c5f114b4254f09ba73c401dded1e629daa05f229fc4baf662ff31bca7bc115488fd0c036c221c6e54b0e80b43a93bafc2c0cd7c5ccd58e5b5de617273359ef3e7a41c48186f3dcc89e225911f04b4a165f62d12cc8c13dd2536d6a50603197188cccc0c0441e425e8cfecc63a1d37d2c1577486cee05ea11789f0214b419db1ee624983213e62e998b568b90d902a929e9914bc63a18c4c2e222f1d42b44ba5daf17dfb4275d01e57103102ab4bdf89981f7418283b252bf13d84aa66032099c612e4310a6c658e72bb940ab1976e9332c374778ac1f12e0bf6c23a218903a710d8c2c9c2b9ffae6912d996c22dc0b9bbe214a18b56b3b4acb0a60c4d44518e924bd9ed11505cf15cb9743ef0f80a9e75fdc14be605e66594483134a81cc76d3877e220111e6798762152542d07056650a8c7597a7c25a02b16c252a986102aa02ec7b25c0fdacd751b79acc312e53a3fa40b80852078d07bcbb94997bb0a06b71323bb98045bd4c54708c40ee5b001159dac12e27fb8a950042efde5ca75328b3af783a818767f965ebc54ef0208b72cd2f44a9a4d431897afe2715f8962ded3146f9e221553ccbfd9a9aecf439dde5337b6363e224c3afb4350fb9e999672118a2e2c778074565f32eedc7477ac743cac047819ce3b04fb043b750ebee3353584db8b11ed9fe6a67d3f796ba5ac2e18ef322d2d077b9603616a14cf0ed291cd051378212b5de56302f6fdbd023b1e842b8378c50f8968c200f62cb7fb9ee05dc7f105a29ece4457c66793988e1a03cc4f2cfe5801b5ca45b11bacb0999a8958710e7610ebbcb5155b5b7ea012a0d7d9957584b2a305a1816ad7bb0dc9eaf117b6dd61d18e09c577b8ac00dee2dc51da2ab6b7fb86dda6f664ef5539bff00711afc4b98a1041568c4f7dffe259d9ffb86e63b9ee1dc4eccacf319ef8e6178890ef32837145e9de3b40fcbd1cdb7132a84609bed0a7b17fd4af34283c4592f77c1da32df516b97f6d0f044ac31f402da8a82aad9da59ae6f5d0971c0c3b0fbc066b7f32d6786b52f6eff007da723a311b9f3d014304b5044db895f49ea051d77d77225b285f8f092d8ee31a9a45d7b7eca6fbcf183cd73282f5e7a19c2b1f9fa916398b830a88a8575c574de75068d3037883a78fc832bacbab8d7725377cce9702a9552a4b895623201e61ada60bf59f94354db0fbbfb295cc3da69007b3cc3da19ccb7bc4ba68baef03e814c910e3cea1e72e55f4b1641c8f79405d8c5e4c150c0ccb3518cca57ce5b5a347cc0c84c84c4198997cc23d2bb6e5fddadd4bf755711ae4257782c0ed111a664bbcf7832a366229681afa1d29e350096614ed19e6b3f42a31ec1255a55b0cd1ab9721de1e0f331a076c5edd0652825c74f2feea4223637da57b2cf9815cded04748c029e828e68c42c80230c2dff0031154efa2ac1c45a33a20766478eb78a97ec32a8efa64df7e202aa52c7888817282f7c46a95b72c32d43a6cadf6fddc2da9572425ddfde599a8f683ca19447c913233c1e8a9d5cc96e2fdd337be37026ca8a8489955660b8ddc1c7d00f30bfe5d2d9afb12f18770eeb3332b89a0c5339b8ef05258bb3cbf7842f6ebf68393be2354c42eb99951c3897069b207ba2bb7a60cee97663e8029a0fa862bbcc543d3e3175382c134c710375e1da236c7f787755d0dcadba0d8a8b7212dce7eaca556be8a8a36e28d8916f5058b819a264aa1b66d828e92df8870af8e65199f2fef274e73821731a30879a8aa9fac8470f4f36dbec9822388a8c1e263947bc5787e227822dcc574ac9594e023c4fde843312dbd5f107998e2172d52d01fbf11154fd6cac963e7308489e610cd7ea3a42a2e5d8a3b1600b562c0d02834fdc4f4bf81841f69a57da5a15300ef05ea51276a27c37e867e73a5e9b2455065eae1554199d42ccf9d67e6a77fdf15372a37be9b2a913b8229bbf0c0ef99c372f00dc7a78fad6c41e569c54b4f44a9dd4ced711cb02055e0404a172e7c3f7e14d3102aafe60f2a41eac637661e9614d350420600d1a18154efeba41a0f765e6263630658cf0276885abe55115a57cfefdb973da000bba8861d3bd0f30e2109ccbe222a14c23b0dfd7fe470b0840bd42101fdadaa7e98f9589db9846caf9959777a8a36352a5118773ff0046188a92a6858e38fc4ac7f1f47941f0199c295da1925f01bfed45a882a99c65fd35f42da8d722983e2013cc73394ef3e1896d7dbad81afbf2421630cbc4ff00598f6fa1526344fccb8b84627ed2ee8843189f92fd336c1a992b45f0f5c0be219c6658984973d9c4bb3b5b20d62704cb8d0c7798e31cdfe222052742416ba20e7c657bb12aafba057bb8fed28bb1308310379952797a5ac224a564f4d537da27cecf4571cc7beec9bd6d4d12df6806adf1113223174980f989a86c118fb1108b591d0542d89d04f8b30da6f0f1965cddbf68dcff00643128b98fe62c37cbd208a3896efd32af3a8251ca799b46a3a8652d5b460b036c4d38826818732bf32efed898724a0adc27dd3c234cd46e599f9416622b4b04d747ecd4b28f6d4f7191faa35b92261d810d5770743065c40b02811579cb368904138316d2d9741510c23a53bb86da033168a27fffda000c03010002000300000010045845f7df7584104114dc41041041a6423f618c082009c1041d6dd79065041024f00d7f9ed881fde34d04905d37dd9bfbe916746bdd3bc2c1075f7db41841656ce5b598e882d377f5ddb3f79f4bcfa7ec753c45fc2fbc308349d7df6d049a576f3253fcb05547174b361f43598cacee6d2bef5cdcf3cf3c5a2d3566d2512a90614f60bd50406dac91586fa03639d73cfadacd8bcf3cf3cf3cb0f6dbe4105fbdd359d44f24b656634fcf2e9b6562e3c65c44b07cf3cf3cf3cf0ed2766d73dada471568f686639dcf3af3a67ef5151585995df3cf3cf3cf3cf3cf3c907f3338874502e06aff00273cf3ef07eac8ce700163dccf3cf3cf3cf3cf3cf3c3b5ef3753dd8b054d446bf3cf08f13efe2f1f3a5cdb5af3cf14f3cf3cf3cf3c33ec9699c75fda699039cf3cf28f1d8e81f62beae5a7af3cf3cf3cf3cf3cf3cf1e5622cc69316124bf3cfb8c84549cea38987ed391f6f3cf3cf3cf1cf3cf2ef3897cda6469e4299127cf137a7d3e0681fbc76eb97def3cf3cf3cfbcf3cf3cf2edbef84d7771d3eef3cf078de996d3aabe3fc0454fcf3ef3cf155bcf3cf3cf2a91c9e966f12b2e1f3cf0b293c1c5baf7bfbc46adbcf3cf3cf27f3cf3cf3cf085d17a30e14c2e62f3cf0178dd3c5ae53cf3c062f3cf3cc3c158d3cf3cf3cf3dfbf4ceed617b690f3cf0cf25f2c7bad3cf3c057ebcf04b453c107cf1cf3cf2c6a2c65147f7d6cc356f0e06a728f3cf3cf3cf2c078f45debfd973cf3cf3cf2634f22871bc6f64197cf3c438e2ef32f3cf3ca21fbc9c950325cf3cf3cf3cf3be63f4d118cef99cf3cf3cf18723f0ef3cf3c161f382c9f36f3cf3cf3cf3cf2ea696f82a670cff7f3cf3cf00693f3cf3cf3cb3cfbc3fe0d6f3cf3cf3cf3cf14378f967c9bac40ef3cf3cf2c0c1f3cf3cf3cf3c2aecfe67cf3cf3ce22bb4e8304a4c4b5c382abefbcf3c8ae0e3f3cf3cf38f3cfa8ad5676f3cf284dc708c16f2a33cf3cf2ad3ef3cf3ceb19ebf3cf3cf3cf3ccaef8fa7cf3cf39bbcf3cb9ef2c93cf3cb2e8def3cf38248c5cf3cfb70fb338e2ef3707cf3cebeb47cbcfb5dbcbbcf3cfbc63cf3cf03961ebcf3cf08e24fbcb9ef1c73cf3c539ba7f3cfa45bcf3cf3cabc73cf3ce2c0e3bfcf3cf3e696f3c0fcf3cf3cf3c9fd87cf3cbb4f3cf3cf2a4bcf3cf3cf2392af7cf3cf0e1615385bcf3cf3cf38936abcf3e0a2f3cf3cf210bcf3cf3cabed292bcf3cf3cf3cf2cf3cf3cf3cf2ec99f3cf0e3ddf3cf3ca257bcd3cf38c1afdc7bcf3cf3c77ef3c23cf3ef3cf3cea2cd5f3a1e7b9af3ce16f3cf3cf3fcc81fe5bcf3c15f4c9db683cf3cf1cf2ea3e32e26c72921987cd2ef3cf3cf2858a32a7bcf3c8c01bc89e23cf3ce38f4a8fef3cf001dc1a6fbcdfcf3cf3ca8072d5bef3cf3cab8c7403c7bcea4a8c9286d4f3cf26c2d2461202fcf3cf3c83a097f3a73cf3cf38dbcf38fa669c11ed47f3cf3cf3c7b2502f3ff003cf3cf3a5d0f1cf3af3cf3cf3c922fbc1fe69462187cf3cf3cf3cfbf002ca9f3cf3cf3a23e53fb9cf3cf36c8fef63dad3169abfcf3cf3cf3cf2c2dcfac07cf3cf3cf3a9dccd2ebcf3cbab08e638fb1c6e2644c3f3cf3cf3cf3e23c7e8fbcf3cf3cf3ce70cf2dcbc910cb1f679eb9ff003cf3cf3cf3cf3cf3cf3e03cbabf3cf3cf2ee47d02e63dccba7c55157f3cf3cf3cf3cf3cf3cf3cf3cf2d1d18bef3cf3cf08cc653db8b8372580ae96df3cf3cf3cf3cf3cf3cf3cf3cab973d87cf3cf3c0e1385866102737e95424defdbcf3cf3cd3cf3cf3cf3cf3cf973d63b2f3cf3812bb4dad5dac3bd49a30c5e65bcf3cf1cf3cf3cf3cf3cf3cf1ca632ef845f051ab1ba24decdbc9d2a83cf3cf3cf3cf3cf3cf3cf3cf3cf3ce338d317dd79b19a92164c3b95e234b89f3cf3cf3cf3cf3cf3cf3cf34f3c3bcea18d7543e5b0ad484f0919bc3f14ff003cf3cf3cf3cf3cf3cf3cf3cf3cf3cf34f2015de6ec6a6872dcf3cf21136c7cf3cf3cf3cf3cf3cf3cf3cf3c06653cf25d093df708d8a84bf3cf3cf3cb3cf3cf3cf3cf3cf3cf3cf3cf3cf3ed4a713d3e22a87baf57473bcf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf0344aca8740a7e43a94aee7f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf38b24fb6b9c476fb2a6441cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf03f09b14f3cb8880c20c73cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf9efbcf3ae02b96dbcf3ac55594f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c765c7a53cf3cd05f3cf3cf3cf3cf3cf3cf3cf3cf3cf3ce36f3cf3cf3cf3cf3c7eecb64aef34445f3cf3cf3cf3cf3cf3cf3cf3cf3cf38b96f3cf3cd3cf3cf3ca6e3a78b85aaff009f3cf3cf3cf3cf3cf3cf3cf3cf3cf8b97ff3cf3c3bef3cf3c5fed1deb410e4c9ffc4001f1101000201050101010000000000000000010011102021303140415051ffda0008010301013f10f22e928d44a7d66ee2a82aa822a2defcb72e5cb8379ba8b2e5cb97c142c1800de1b72518058f4115db2792a56facd372f45e1ff82237116d1510d04eb43c2479c865975176b08da5bd4b5ef8dd5516e19b8ba0d01702562f0e94f095602c59de873f70c0b8406a6195a97938eb341dcb2f682c869cd4acdcee06a34be26bb8f16dc19b6841705602e24ee006a741e63186197582c1a8de1a9d079416c4c0902e573070687c83d417094c78ce0c1830f89422c8a9b6dc0853d41fd8cdfe45ed0cb8726a7c42a8c146be46d81212a6da6878de13c232978ab00c045b841e50acb0f0d6f7c4efda6cee6deb0b962cdaa1ddcef01e542d2f172f15c4279d9db55c5c1c151db86fca7017c3dc6042d1a44ac308794ca95c2b508e830b4a60709adea1a0e725d421815ea3fdc01c27a19572a5cbbc15f622b68ee9df09c758be321b11ee538057230e3315c7584fb1597c961625358386b41cdb75aab5c372e1cb7c67333ef22a8f6b1c1f21a5e77d67f24f33cd55c55a1f01aae5cbf20c1c2f0be5628d078abc9b32ddcc5c35393c772b82ae09d4fa22def2b59e5543819516f26a7158382f84789502b8c95aee5414d434dcad152b8c792b0af51ddca8b6506a50dc56deaae7326b72aea7f5c00108e930ddc3060f8c840d6c186f2c86cc18b8c4fc973bc76f1dc4d663d40bda7d62cd91953ac300d4467702a3e4fbc2466e8d0b65cb8b5b4b8ef14750bed8b2af710612e579070c1978ab8c25bb8db6146a0e9951823427695f98d0e09528a8d8da547719492bea30496e345cd846b9475157cee1b216c1768ceb23516cb835b41508b508914fae8c14a830750d36c1b46616fd4e141707445dd0d94c5bfc4a8ceb06a6e3f1d45b4a815f8f4476ca15f6b861aeff000876fca150355c3f0487b5e55a837a58f95210e54952b4a4ffc4002211000300020301000301010100000000000001111021203141304051617150a1ffda0008010201013f10fbce296ca8ec0aa16824f44a39b09de912fbbfd1fc1457a46244c44f06c8dfb9a5ce9c48e0c5d0f698d1a20e215ab225d1d8b83fad9913e5617093843c3fb0234e33b89c133488e11724cb7e3459b9597c50e15170b623b824c5a22f0586e2e49b13e6d120b32920b2c84c5840d885a567983e875e13c170ff001587c35e9af3e4d1763485cadb3a637506e6878416133b7c5398279ec82d16fc2e87de52b8435d6128ae0cefc1f5f553d293884a14d31348a2138511609f11635d8d6d2c25541eb0a55314487d7d52121044271b0ec9117827309e96ba23c3b63a62fc032f479432c2d294a51a4e296b091423a731a41bac4e2c74cb7dbfd3c2947b610b306c798825509e893638ec35e8957b1f43475c3ebea9cc27ac562057855097d2308123437954c70874bb12d31d6c6b85b1e9c3b0f143e8bcfce6f581283084e0c274651ef07496080f483d8d08b3a1b3ec4dfb8ef8ef95c16b1ef9be84e524266372cb64083a679ae0cbc1e909d1b2ec7d9d84a328f4378ee31f3ffd3e1742fd4420862ac1086da744df7c2121bac486763b1d87c3b8fde296ae10dbdfc1ed11093352251291227212a3ac25676c5c5dcea25728f0542531db9aad4585826529de1e1b33a1e613684dc62c2cb7c3be3d1e1176ca92d176745a38f826ce90b1708b0b70d6cd14e17595fbc4d71ef8a731d05533fa5f911d1689e1629707f0ea317538f61abcc8953c213d7cd3876486c265b94da36c6f51f3910da9f198ed6c6c4f43f99464930b0ba139b1baf9227d97711e57c612825636dc11aaaf9c1eb584257a1ab2fa441e451323642f04ae1fa9e7e2d69b16b3be0dd7436df04cd69118a145c135d1e131a8f8a46d8efbc69696123ac7aefc5f58b744a88f38aeb8a62ef103fbc6d23f43778a1d8e91db2a7a49d0db3fd1afd62544c27a4364f8254b4d9b211e1220d4c29ef042dbd10e2b0ec74bc5a1b7bc5c35a45d4cb778251cf04d90b2953b2f2df34a0b047df18270b97525f04123ed952e0abd216df244e68585d714e150a7a39e7c93f46d8953ac350576a128f925a27eb9211d8ec6be4f3cd4c12dc1a69e689fa84ebbc925db1ce4b5f24b44cf9f821382fd9091ef08c58234f2956351ce4b087d08ff0038276392583441bbce6b0f1d1d04c6bd3a63bd8ff79375f248874317176d41ef4367851b6363e3121685aeb1b2d8dde1d9fe9d04d3241cf306ff005c923cc2eb0b8a29fd0d896ae1bc25493b1e9e876d6311885d71e87fbc37d061354f41abd3e4b1d170bae70eb30484df47652884c3fd39aafa13847e8d7d28e21b2d7258bc17539d126c874342be0b0249bc1937af82360d246bd27a873e65c52a42e56686cb06216f16077cbf829e1662c7cd10de26c7ae0dc341448e0652328bab29d144d7a59d3179647c10a289c64a251efe4c62637fb1aa298951ba824531d145139f721f59d392547a3dc43b1fe83765b82d83a8168ec8353f096961aa49f031d2a6e36c3c316863544a61357f054f4b5a43d60df2489e8d054889762e0951db5141521d31aa1f506a7e0282a511fe8897424353937b18b43c093048821220b14ee1398377f093a16771b2e26aa835d849b56688a253105f8c192ec817496561f07b1a2b50a087a08f4d58b78114dfe0257874a616c6a97f30625f42c30985d0de5ad1b06d25126f45cd8a2fc86ef42730cd0d194f62770df18589afc34cd1d7435ab9d4986fd8c3c7a0dd744eb3b17f2909f43a7189f8c479844d0b4424c583b109b1bfcbab095a1cc269f6412dc669d886ec95eb0ee2db1bfccec219fe14b84eac21210d111dbf9ad193087fbca3c3b8d56587f929c1b47f99ec76c506a383435328d0542d7fc069af4b3b2a7d91ae2bafc7353e6d74c64a34d0b635f925c93726b462149b3491d9e0d4120bf112a38651f14df5c58de86a248d4134542621e1899fffc4002a10010002020103030402030101000000000100112131415161711081912030a1b1c1f04050d1e1f1ffda0008010100013f10867d6a1ea69fa0fa0e7e83ec2a57dbb30bb2af6615a53a38036a808280bd531264f65514736f48c76ef90ba07f2f6995305527a2ac07752ba4be3c2a6fd5be9e2331e633da3b7acc89e9965c6f7a88ca8507115836af798377b171727663af10c5b23d992dd7559e61e82accf423f6ece14c2eb9bde5b3a46a5a688828f06d9b13713227e419ec5c57c21e2b9fc1f99c13f52b417cdfbb081bdf429bf001f231cc404f7de3549d9c57bfa57d2fd075f457af3ebcfa99f535f41a7d48af5a95eb5f5d4a95f72a1a6fed50874502bbac2998215b9495614d372f012d54768710b6e3651e4d5ea22542ab42068df7eec10848c31635ef07bd5b114187f2671cc1964852dd3c5cae0425be9ffb2c08c3b3b1dff88c1dc19c8ffc877a9b00d0f64e47a43ea35252cc815c145d7172e9b70bbd51d6594ca23c2c566101082991ec1405532cec57cd17f6456c86c41d34972832431dada1d0c150a356051d3721c0cf5fadfa0ebd5d7d1cfaf3f41a842052072b289f1082d21d99b007920191e195f41a8a02ba25e83271e210d03d506b028b8ea0b6564d423a2c6ee5c0ee1b8c0bed8128bd4e6192ca7c7dca952bd45825d9553a13c25a09eec8c676d95e258b07296626f1490d45b8de2d4e4992411e01ec8bd8666db20454226c611236289a4f44ad2ede825ee566bf31bd6f172f5ec5c08cb0e47877ef2d46d17433095154b796973203ac7f304cd5d98eb996aac18c3a1c8f23861c35d9067855bd0acd5f3620dc516895f43f43dbebe7d7da21a5d31410b0b5d6c8682e98b6a2e1affb2a4d44b009534833d514c95669c061b17b6607a05aa530b5575cdce1064daf301befdfe611690bcb509350eeb8850316b8d55e538b6628c067044a70c1191c31d655a2cb22a807bcb42a19dc04a44a6d8c25ec985a6373a9cacd112a0f2a6204937660250709e952a54a95f4dad4b6b046d2306305b5d601a1eeada2759adfb269abc852cb53d102b899a62a82ca314f4aa7cd7780804ace231dc2608e0dc45896ded3509b519cbd19889ac5c65b6732a7b845741d58d4d079b27fe235a3a4cabfbe25610a42eb785e7bc72690133ff878962da96e3c2f381e6e14e100ea7e8824435dd6bdae10a0ad7ebd2d9aa3a7b8f51d26930c64ec41e543495b4b1e086780683a30d83d30f9f4afa1fa1edf53f453f1437ecf79406deee534a37f7064c64a359944bf923593657596e14f7808171886b7e64a408f662a42f985c3c1033507bc62d2bccc8abc9b8f2c4ded94385f989b389cfc32eb52a92b90907c5d774afa35b14c5fb7b4a97c80aaa01ea443a11546ae0852bc0dfd84a5379c61a96450a254d0b4df4cd47b36a31cadcd95b3c4c569dd6cbf110e5a82ab8a8f90b5d0694d42a5f559ba82ee251a2fa7939805ab66556e9d200b59599604592ccc42df0331c0b9df1fa96944bcdde4f1d215bea0234f2cbb8cdbbd0632bfa947802990ec63c6cf88048140017ba9b638b79ed083e4857d4a99e900396c4f00f16fe259015a11aa88115d57d5813e3acbe2b047630b9d18e62de02e5b59576eada5bcca341b333eff0075b2c5e4e29eb7cfb46870f98239cb8fa5fa2bea053503ac088269944bb8bb2b121e811d2e5515f3155cadef0236ce7571de2995979ed32eea3f27cc528b73de0636ce25045b5d614de602e94c4e78979ed107423483733a662aee5b68746022b35965058446d899ed34527965db9e4e3e868e256c2acef9c7ce267ee4ad5381cf380e437517a56405ab3aedaccbf6edea179ce3de31a14e9c85fbaf105f64e5eb482d18ea22ce8a2f6cb7c6779c73044416e55b494315a009c6c015a3ac15754de6ef150aa4afbc2dca5e8e48751ab896039be636ae0cb3ab265f27b169f88c584b11be17eea742351f3773aabe5ecd4585dc3421511ae8c3e094cbcc6b8c0add4cdf0f36dab5f38f680eb6d6a8bc99fe3eb4db01d8297f12f648afb2e4271d99cee094c747bb1c7e3de1e37aa13929411aff00d404d2feefecd7af3e876cd418d4ace773215360f129d12abcf12b80b77011cc4b4d431a826b1323ac572cb29a987bc765a4c4aad40d1d22f563bde656d8dadb16a054abdc3a882dcad4e8b11ed13c42b9ec72e2182dd6ccc6830156bf707dba6b7f8e6508a10a11e3925e001b1777fd2315aca29d8383b4629957a3a2744098bb6737370b4b4eee599cc3178a557da16f018bbce07f06005013a330347701172a2e9b06fa847502eb515ecbea9b8ab175f4bd5ea7880a28e95950a96a828c742ae95edc1ddc12ab74bd01e8597c17c9280013e5ad2fdf1121875c4c03ecbd6590a774b33d94eac10857e63fcf12f3ab59d81a25868dfaab6c42c44e47ece52a514de6e0c72261e73797e63178a4aae9956fa454450ed4e31452faa2133a1941d4265baf6f106cd0c727abaf575eacd4c15b963a25d6aae1a615b1d05a22a378e6bd32b6e2a39e225845b0c6a11bb269ed30710ce8625e5623c133771768c7106b11af12fcc97512e2a5bc32dea599db9daff89d06642bd00c8f983a3545afd2ea110336df0e5c7b470b60b650ae9503d36f2e894990db7d65e20d3b69956a483c95165ba2e025336c9c6638ff00cf468e8765cf4a332c1ed0a159c0f8658d550280f446b36ed7c46b312d113c5ad4bbd628ec1423cb880e3d9362cb58b9ec62e3e05ca058ac2f15e3312649c9fa859803b10b765703303057c622a835e235b837cf255f32d588a99c300f7bfc7dd48d8a26922ac2070835642998a898e32297971e4832a6570a5b670f192f34230966badfabafad9395ef2fe3f08b5755313115122dda839220a8bc04682aa08e65e9ce788b47a4c528b648fa4766616cd410f3a2586b5096bcf48e561963514c90581424ea718660ba161d25e8d5a96ae81b81162f45cad8648c83d6c34e94e0e751b8a9bddbbf4ab7b6b4e629b5a5976819a95a6582779ebff23a5b4a593663d14f2eafe12f91495bd789551a9695ae26aeaa9ad732d2e8069eadd44b5054d76ffdfa1d015a80f649502217d3f2e0d0d930ac68ebce2e93b97ed1c25ae73b8b683ac3166cdb70b32af1168b9448e52b25b37c1fcdff00801fbab426c4786f364a66030adb41807de8f994c6c507c95920e22d388602addc8ebd6bd5f800dd4521f89a9d398b4d0c2ab0c4aba4f494e04354a4dad44581a95f68934ca9c31888ea006bb4ec4ed4ae66dbc40c462966d8c5966646b7006983480ac34aabae630495840ef9861837b55fbfd23126e0b56f817552fd2fd0051603c768e45284174f7ed330b3b17cf53c456544b0edbe9e84c69b7e10f4b016b2820768570fb46d70d43f6f5bd6553f0308ea98e6eefe3538fa475115461c25660e480089f19ee48b33894bedff00dccbf3475331ca30d11d9ab95d2ee76e3059f3a8972b23caff008353b246baaedd267ee28d4d61cfb7925612c1833ae47e4a80f1ef91e32be0c5ca01b584f0bd5b1f6cc75e9c4d7a5809aeb1e950cad0bed1aee094c31d7a7514e38e91ab71e95c7e62bfe1384865eaa09cc4e8c622d6350764edca2d50522b89a7b450772cc3980e043bf6467365d626f558daa8bf3c434106ea3cdaf88eadbad078e598c3b0cebea55ab5dc6bd9bb2ef3a621a882d337d61e037cbe4f4a89972a02c3be9895101bdd852098b0fc40ef3382676cab6c01735bf716424c1397566bf287a32aefd9dbd8c7d0d49c27bec20077976ed125855ccaea0c85fb1fccb3a94fca4efde0a146b0894c78d0a1a668aebb90cdfe63fe0a46c513490aa13a445403346d7d4eac014372a839eab0f5f44d42f699a1c387a45ae0d803ce1cbb74f1a9dce7d1e7d10aa9de1c17299763bcbb3c6650749d209b8234b060ce10a6d81976dd9118698b50ba73162d0e677e1a20e487136800cf5f42cb9538c45691ab1772f37ace65a6a25b2b8e91a679acb6b9a704c505023867394488a45bdf3d60a6a5fd4c15500acaec55ee1ac4044b4d19d150eb1576cb75e803225b46053a66953294a11cedcf58d557005711ae8aeab44a88bbfde142329c0517d7d1a038016abc10bb9096cbeaff00c6bcea61478a93f12a114052b8f1297c8ae3fba2bb21f81393f03d6265598d27977288b70d4bc89fcc626da0680b09d421ff000d204b60e1f4d19705425585b55de2bbc4afd32d4c140c0d7ec653a18818007178ae687d4288d8975322837da2b475ce239696431bbb4d7ba2aea58531d225b255ee3262f506ee1c41512cc41123bad0c4d258b64545731ee2102941c452cb32ad89e27c4bda80655a62f571d1cadd1f93c47c6b0bf68aae5bfb240a99ed09aeb0eadb775d3b456a85ab378b84b871a63860a6b20be83887392c1f1305828d2ea155082a13f0e11d157bdc398d6c4cfa0c347be0ef9e25a08b9baef2a112a281d1213a4768729c62086806ceb29a035bfc4b3932d30169ea18771cab474ecaf6ff00211201536dc24a16956e2dd5a3a38b8474c3caad1d15cddf688dceea453588c8414651ec43ba8a541dab51da1551c505563118e0d0caf896f100e5498ab6bac4e46e223308caf6f684362569221c44ad3e260c91af4cdaa1f69660e9e20f5a8350cdc61016fe0b961f9cb856febb1d8c215cbf4115b6b1d258002a51b1e5afd4b2a435dd7f8f4206dc7b884b0042474bb7f93e6363d006b0b1ff00d4e352d53db882643baa68178000f9f43985f48f2350bf904e474f107665c237485ef10c00b0e852ed0c850e5d4c43336ef1134ae8cb15fcbdbfc90a40a70c02828563fe91ee0f091e134c570d7219bcca0df73bc75afba583815d6566b5cb0ea1677635827bc2639f6673011e65c8bb99b1925259707587332f3100aa5b83211a8043170cbd4f6e74ac4d6a2709bc47165d910376c67cc0d261cd6bbcb596c93653e75ef1555555dafd7dc60a3c7a9cc2b80b5c46468a85390f3faf98c9b4e5955bd3bfa10bf214b85d18985820174c7fc23d342ad2acdff10e6bced352c1378cf9709c953a5e3fb83e3d3897618075c979bc5f77584d1879a62a9ab5ed0101733a85c6224708f47119ff004c31e92cddbcc4028743190bf25edfe48a646a6f0aed9689b455865152c6055ac5bdddfbc06e6e2ecc79836d2f98896f89b3fda3d6fe650a4e3bc1e035cccbeb28a8906ea16a444ac1e750e966710e4b98a12f7af4b2e23dea37c9e2e17b02fb46a5a078ba9556fe437f0351464392e703a07d83959318a2f3ea6e1334b503d1eb2c1c4c5db45fcc63a0a5535ac9e8e06cbe51905d275d08bc866445eb17e0ac6946bd8b6bf947ab714ad96f2defd6e5d4620dd85822b5a4762e717642a956175132ae4ef5399a8c318dcab980901eb5177745d1eff473fe4a005ab44a2c439bac10e7119cf455d6084086a18fef423765263113cd52bac5738307cb2b9571826f86582965929d44d7388eab98988c7b2c0699b698108d4a11130e62f133edc7e062cd92a451494b46fb224a8aab4efecc7b44558d145f4faf8671ea43eb014ab4400450950e5bdc32e85d8384efe96b743f19834dc5d4cac0e144343850142047ad087b44aaadaf3f41ea803aaaefb4711b56f3c897f9b95d2730a8b5b4b0e02631148aabc6655b931ba60f2b3955f93187dbeb02b40bfe100200dd72ed28570721807c471bd5ba3a85ed6eda332884b2aa893839e8c2b85f13158f1028a23217700b0145e102b89d6a1901c4617105a44f5475a9728ae42016a0fa213683b60f880692864200ea6e374476799ced151f311b4ab9576adcbd5cc7ec1ea6e6e91b252b7f3120d69866ba10b8214f2f4620b9310534eecb04cb4028b7c94af78de2d8a59c874a63e8800cad1e89087c03d666486eb8450f946b366ee034d35866704562d8b9b712ce70c7024f953e522e549376573f5881077fe1d85a52c1c92a8c381711b851a4345c68c8ae40e910c3ae90d58d6a2d3670d6a045636c1cd13064a3901a81816591e97b401c7ace86f10805aeb0b0300170e7804783c417b7bc02db959c90c4d93a6a336a353ae0f1050417387ecf1ea058b103f3382bb62b3aeffcc042020393129b571fd7a0533fb923505a0555d620264cce6f03eea7ad23820340d73e8c7d8b9e9ea4c28597870fdb2c960d7398f16556ae154399506772e4160c622186dea1476a77fca7aff9804006902cf7dcaa13a843c940b4330b141c4b64644cb348a7ac7edc2efb12e88217da11145d540a2bd7582126484985cb5b9560d54d38112a435fcf1264fca679fde7531f328bc27ff5e3559c9cc67cb6c3c5a7defec91f3f420ba16b8e8311e44395f3f11aad2bdfd2d5052e0ead92842ac698731b1127775f904fb90f43285d77995b4f40c7a8502d17c59fa4b68e109415315066c332b5736c07b529b23ec61930027f212bfcbaab51b5e8732f3957c0e0220622d3da5cf329958e06cb4b68e69dc142583ab2e02095628d8d30ee7703417ccb2cb3026ea5e32f98c0b83c3a87cc68dbf3185234f5d66172d758240a346e50e563f12ea9cbb2eeeeefecb350e61e87bec32a5e3a7ee672a92f414dd2477e9a44597d87ff00bed082716c194fb16f625e6eb2ea5658b63b42300a50724b058821811acf171028baadfa9ab6909d502b395cbc18d597a25502e52b10506d60aeb2bed166996f705fe00d098cfd8220545cb5a8359f6f5594aec5ace7bc159db6d93da258bcbf53cf2cc560c351dce56e205d417a258039324a1f117a508330128630788e22759fa6794d1f10cb8b8bad41af6b0c1061f289bb5d6abe83d45327ea17c4f7f4433d9727486e54395979bf0c20bd4971fc79ef080202e87b1f139f4af63217459500c206f657f16aeef7816e80b6aea13e30b9d6a5dbd6b9965106b35786cfe7e272cae9b8c1337b3dbd052d2fc8fe53c22b08ba711ebc4e01c8a206cb04e2e887c588ad46eadd99b7e5ff0031015b583cb049873cca15763cc38ae954985f28c6b84b98210ac13255b26ea450a3b952f311310f50473106e2388b51eac949bbd4144b5692fecc90c0055fcc452dbab81b07b09c7d2468ec3053a7316428564c657cc421428cbd731236289a485256b45bddbe99e08201a47f7330d916b355ef2da8d94d0c9008011835c56bd6b840745bb8c946dddd37c207de03b83c06ab442d1d450a7ea5106865d02d1b7b698bdcbd8e5fb80bb01a3a9cfaaa95e020acc7d67fdcab5ed1dd3bcd63c9984c611986b15d106bf84605743e031f78cfdeda6f4d6de91d6a83a541105aeb2f2abbc4da398e36184db5c285fa831296de21134a4778c424b41b979296a74408f5c4a06c25ed4c6a166652d99842082139ca200aeb4b1fa82952ede9572a5f98535ff3d0e61d6432a2d6345468f17c6e1a3696cb37c446280cd35160d79afd0b85900d38374fa85c73a74d7e4c1ec545aed29069f09f1002e802618c840aed60961a8a095ba5bc5ffc304a02b52adc6afa6e32d045ab9ad547442aac3e8902349a6635aa5afbc1eb23061d6759e3040b3a39adace8df2ec7f9a0c2eb0ed920503a45362d41425eb11664c74948bd3882fd92f85b177326603acdd9a86c8ac092c8d444126356e3965618368f2e5f1b9c44b512f402ae801fb3f59194b4b25bc31a33131cec2fe48043b406bd085d9c94d748090cb6833daa0d687a5d825f030409e96d615fd7fefa6606cabc5db0f434582dab74408b12acb95dfea17f330efe4b71c4e1259685ae371155644564c707686e0885948743dae2ac54166dce7d4e618961efab3f3fb96b8de2510b844a4f1a75651dd6d11e85aae4a2ddf397ea400ab599c6beb3997d2ea93f05c7ef050015543ae37305503c4b9b54d7596524b5618dde661b48811e025d5ccb31539443ce08b4f4ab1887aca3351a9cbf328f2c7e4794639c55c08143dfeb11aacc273d20253b3d29a73e626ece7343d8f7beb3130538e9e02e66405adef8357ecc1eb8de232fe06381dda640e388a2a854a5ae3a402925be27fc8a95d9c8dabb7a9b85c802aacbb663369b6d84fbcb6db3b0abd436c5a5eaf7eb190397a59dd33d8e500a67d4e6159547e62bf89482e250bb896ef691a38bde52fbc7ee9cc483961f7998d6df81186a831d43b76881db18b8cb001798f5140f0d4718849ed3a1834d112cc474c44d24e85ca3b421a638c0ca7a8e01c105afe903f1a756d301ef1a6b6d4d85bffcfad4690a4b9e2a0a5d314d4c1cb8aff9e265dd05ae47a0c40a4ae011e1abf31858d5bdabcc16218605f1effcc14400174696a50d16694ccb99da1b9fabfa3d5edd10ecd30bd08b0eb72e3774072e5afeea520da979dc333a0d5f5ffd8ce54e3257d0731acb77bdaa8fc24537e20b1c50bef2dc4c0aaacc79cfc3ef25956662aec38990e2968761f7eb268b0bc6707e615c34e114fd45072c52a1182e4f21430ea4707484c5f50e2d9a09dd188c19dce92a03f84079466bf888d004ed31b83e256e10294df682bdbda216b1fdcd3f820999308365e7ae8fac21815c817f31882d5a08a8c869a3df881981445be2549b5058440bba4d9ff99804434d34895db7ff004600a1b3b3f298ed30bbe9ff00659c22b17d592290da26c684973e18b4a0a3db172db04a2c97c32d772fe93998d71ecb47f2962c7709a00a781cc51d734e2dc1ec63ef500753972bfeb1555555e7efaaa8258ec7fd854507408a82fc5182c2cbde9dc802c7510b23de5021d749e21da625cbc40a6d04d36f682b6b44432909433956632c988e65710c29c4753de2900c7596a7b5844308cde1e7ebed150077b0673d6113a84e14d7e7cc08056865adcb1236c2dd660127abdb5002c2abcae5292a87a63d0bb5d5d8b8ead8b6d2c19e3d55d806d5a2a15a46858e2acfc7d83999f8d4b31da51925add36b58f66fee9cceae9da225ae8a3fc07e596841ac2e5f44541c45da82a2801f88595625c04ca46ba4034100d261b50178352c6f0652d83da734422e2be445340c4ba5c3cccc7e712e953ad5e6a1f5f3196d2290b73fac106d1aeedd4411104763002d80abc9d2216a45d75ed3076e7ad3eb5011a6ad4aac57863a20ec6bd4f16ad737ad7b5ca4858d014efdf6fd83997b8e5222d01dd2c22ce5bcbf91f1f640b968ae91fa0e0165ebacb3471b6f8e31eec719cc8c503976346f1a8f81d5e43627de1470d4c9acace4fe733016904c537c4cc0449b4ef0156958c262702a12dc199a4b584877b96d512c2d301a25675a8f517f300374e351ed18e2568227523b940d450e85b1f5d0c55d0405002fadb801a2a286da96d26a038a80aa5270db92bff20e69de78f5004d4df5aae9119ab5b6bd7037011a5d690f6b84bac9554f6bfeebec1ccecabd798aab6b11b0d4e575cff81f5f5f4a55aea112ab5d98732a05b46562e226b027b62fd90011c2cedb23f67b4c09a0905e2d16bdd864ac2588d2f2f159cc023ed0b68c0a5e9947e9548a5f6ebf615378f794cbacb2dd45db25543a6628bf309b3e63737980a5be614bfca39adcb297cf78816eca94adb708e4c2c89006615dcaa8a44984f9884a1dde23612cc6896fada866697a0efc4781568b5df12d1610d97f8837aec9b172a269bc158f9fa00c550da183febe80abb633addc03084b0d46301d3ec1ccc4ab2eb3da0022d1b4cff01f66e1f3e9afa92235a8e2166f6da860ab0508b792b7fd26872018e80ab6baa78618dae8109ae5df437d624f42630370681da37ea5d98c6beed2af172b2e31c4ef19089859b6d67b17f947ecac90384a7b8e7e9a320db5b861b02f63f31d8d18affa4267f787461f31e613b5c5b9258dbf32bd65aee60ffa9862ab5c4197e65abfda577fce6408f2cd30edea0aa51e22aabd60ec246dac4202b817e814c8d400821ba4b975d20b7581f04b6a870fa7f1ff00312f17de0df5f8f426600c953aff00cfa58cb485eafb73c7d9cc82f8fb454502804b9d67c03ce388faf105b4b52b26f9d461a597b3641260501315d609eedec68f05d9cdc47bd8c8b54e3bbd32ac50ac811a6cdd778cf4201fc3286745d8fe65c2916da2c3da0633770ab01dfa047b63fdb1eed17e0fbf701cdc7c40148ee627a602ece2290b4cd33ed3342c46ec8d32c14b4b111904eea5318e732999429039226c6f11cc058b0087ac4d73fafe881071bb747bdd4b043390e07fd9c9c2b4debbfd4440a9797a43d0a95119b6f2fa5bd58981b25b2af4343c1ebb3fb65e8b796605ad1e53e3ecdcf0b3fc4cb79a2ba45655c9bddb3896095daf90feafd06af59855e6ea1cbcbbfe988440a9796b50660dcbbedfc6fde24ca5030aae2522dc8abd80aeb6c7889089b15573af5327595a28aa80d1296b2c60851d4d9adfc7f76fb4363c66507f2f77fc156874a2e3574c379401a0034054be5c5207e10189329866d0833f84d67e3360fc251a486ddd4cac475d0d748a11fc25ec1bf1155c2fb4a44623c013f71875942354cc26704f06f862c154e01afa8f5234160a855f3e839301471e943562e6d8f50daa163b8ac90fb4cccb2d867711aab43bdfc229568abe950b281aceaf7ea902349a65d810c768341ab45d62ab03de203c0ac29cea52f9c9b437ecbf10d0804315942df78d560ab18d41f2f8d4395b1889e21a4a4e2d79522e81a8846b856ded4476c8b55b57aff808b14b3a47742bafe71de50c6d100df5316d6a3ab4c54d3a8c6384a4b1df12b454b6224a69b86d57e264525f709599529bdc62e09d79625b1a9634e4fe0f8f4b0b0b29a76455555576fd7ab958f44ba0d31e9ac0a933140b500db035e5c7ad8942a0b2f29899b6d1bebcfda305c23f2cb8ed2dad1fa31c7d268b05b56e88b79a8e5a07448662f40cb3c306868d9b3dd88ca5498bedec5a9d2174920543b83d2e18b95c82eac383dde48954ecf198c2509626925ff826e9616edd46d2b1cdf33824a745d046882a090620401a7c409607c4f61738226c981384685ce781a9c7235a06ba4aac26eb0b9bccd57862ef0d1d0341f702a016ba21431600d100e4d3b3d2d4a5e455d9a3bce7d48efecb2ee291ef9bfe63561c639c6f9407e9792869ba74c2acb0ae4e747ee240b40a70e7d2dfe4482341a1bc9a83200c17dd72fbff008b5d78c87408f150558dc642e8764c4536768d303282a9304b985bd4506256a4b94b530d524667329e0486e57cca1da79655687bc62a4c759a227a457c08f8a88758a7c7ff007ee1b0ea7f70d45a39f448a802aac94e3f53afdd60aeca2b068b5edcc77cbbb4473e56d7aadc7e848d8a269265691292dee947ee6acd639feee5aacd393388ccc15aa681e52bd31fe2d640385e6292161cee30e1aaf044557b92b046a57810b6b3e7d02be965f98115588d5b21b47cc01a200b4dc65b09ec71998c59776145ec17e6535420f8ea631a114abcff00c8f52275a3edfe0ff716d9c4806f91d660b6209607129e83655e8f53b4e5fbc90caf29a74f3af17f58b315aab4bc0f697aebb45a04569efe8c022c1c9d634aaf0304811a77fe2530e01855e598cbeb341d0860954a730e5849ab872043c4be806908b00bc2c5b0ace5165a696559f4334058a07c42f14cc2f716bafc41385a66a251300f2aa35d2d9f76e27916b4c2ebf738985c78fb7ec8787785d168be2031d8a77c0d788f055aaaa5f4586fb96a062ce90ff0958141471cfa9cc284178d6e0eb2eab783d5958709f2545bd30569195488e512b3fe185005ae0254a1fc25cd8795cc3166dcc333463241036e823510d39a8eaa02f12a4983a4a75876a97a4e4c9be2c987a3c471903da71840235f10401889b8d604b4dc0402e236830fc2bed2838add4141a870f6fb8e83694ecd9046b7c55b87b38d468e90a74020a9ceeba41a416477596ffc2731b7044adb984370396d0e482b61d2f8f53400a32d00756a5686e1a458bef5f781742c22dfd05035845354cc84295ad54bc41ab4e10b1b1461b50692017a8f660770d08c494b6710e2b52cbe1403d22ab18f160758f88852cbf32d743b90014cf24318f2dcbe01da254f08e2ac20e0083ff25442b2f0d55fde59e12a9d3d2002153b2f73324614701bdcb46b47185f6c47fc1b0d8dbac93f27fbf4041272e610810ea03a464397d08b7a5aa701d2fc13829babf6fb81540af682d5ac104f7200c42b21a1eeca1c14b2af4c42855e02534bc18bf5ab68dd50f07f71f101a0c62e33521868eb8d4a815594e2540180146177942dd994c7b4116a86ee237ab8a8a437be23f085c4200c6ae1211865f74d611e87fd8557c7ff00d98a91f9533028f16d96f11a0a95c678c6e762fb2b08b6ae33d0fb82057d8629ba79f43708a8d66650ebb5e900c592815b3ace3ed761577acfcfd77ae14f2e92816365a75f460a7005f98a874e5383dbdf519c40e5b756f654a7f50083f33302183a8ff91157ec5502d1796b509c7785805ab802651a9c814736d54d005b4c622aaaaabb58fcb0a49b30b2e0d5e72430142af84a4544d0dd7cc7b4f0d66a22acd80ec7f9a96cadd32f3fda895b5b86a2406c6e0b0b25226181572635adf994265d1a27e5d1bfb01500b5d10ea4c8f29ffb2a152285efb572413afb604540039596c3af74e8a9d0f460566942b1987328b01006ce218f42b1ed38fb561200bd098b9659add4697a41775f485a21054c71b8efd1a5b9b255bd23a15b7456df9e919c41803852b93ad4b152acc005c25fd83b421915a2634435b373325b686bda361d0028fa1fb65d46a1dcd7a56d4b00d39a4ffb0d4b52b15048aae2804b3872b667e8629a0087dc973b15169a84b0d0afb235d837efc42362995632ad9e658522ca6b922812a1a3a7da730739eb75f967da5ef1eae895376d076387c253efe8462e94b29ae631ff0003172cb35ba829c3c50d57fdf52962cbae87bfe2045c517359f4631cdf52df6ed2d075d745f88ceed6feceb8b90f9835435304ba78f89af80a2818810055038fad48ed5971338a50b62f72ecd00b95b7b89b401a3e821412b84ed0d30a141cf7812a0039137f66a6851a3aff00e42e600c8acf48a52de0bfd4150df0ed8a6da3f6ad330ac5711f957da38d66cc470418d3600abd6d2ea0b916e8a84332eec178d5e5255e5df7a71fe030252364176a4bc5643bc37e858a5274e786222d5bd0f4160ba8d30b3bed783fbcc6bbdab80ac9fb87d964a07888ae6e97c8be91229e8707d96238615980405d7f5fc41142e431f406857d2d1f74132df882dba53f9fb01500b5d10195d42cb9bf0e652c20b00de6a202b50ef8b6d01a1798008a44b5fb3bf787d8656867cad41995b26f1ffac000aa53a265f348840c4914db6e37c4cb0d6c50308613c423970cd04abe3bfd615a0b5c04a012d1598106ba3d13fa457150b055db599e2ebf312496a6ab6cb6bc049bed35f421360d5cf8f514c8a7a834a165acc190085c0bfc4095f62a48af010d514ac27b1888995b7d370d0492daccae0c0318dfd550e42f040a808ce737161c7e97cc0c2d900db4e7fafb0417437f19f4c174a3399b5839185885a2eab7f69ab393e78fcc796c15cc7955f98b21541bf28cc10c46b7d97fc4ac08dd83d23a8de49beefd68ac693507562565beeae6155794d53f108863c879dc52ccac58dca27162472d44ea582a555f7884ca05b41f1128ddff001e82cacb0ca584287453b138ae233327313c3c469b280c5363adfa8292cbd55c44994533dd6f51428949b2225637afb08b3994388fdb1d56fd2eb256f3296a0b28bdb5198d2a540f066549b52d5ec9528fa175ecd102a0029d1e7af1001757e3e93078320f5e23525428acdf8fe7ec086ca57a31ab6a2ed49c5d7b4bfb4731005383b8d7e603182e74e5d6222382b0610655a5b8cffe47845e808ae6d5bf61c2d77c89af118a16562a8a6e9fef68c8a57aadfa2c204d23a81b20b65e3e948750df9188c4a9b35dbbc648abcbea84ed0c73a0fbe096ef28a547fde256d00356aa3d7eb22542d854ad65caab405fee3762bc3593de1021522b26757ef2ec229d10e1fc441881e3c078fad390552552d9cfe20b3b5bb2e3b842be1f4a192cce1d6aafcfd8b5243d721c42a0a96559b22ce9061c2de332e2897592f10e7ec98ea208fa0ac33c4a52027c4015a3f90231b954b28dff0043104a76bbecbf6b665901cf104596ee01d37fa5c4320376228f8b1fb7da082a111e1fa896179ddf3539961616535c9f7b705204553bc660b0055d59c1146b295755e9be208e9b974dd1815b3da02cf332eb1e79dc55555577f5322e0ad7426fc55e51fcdc5840de71500f239be9f72afa8594bb7a4b22baa1c08a5a1eab282c68a2dd10534a78fb4b869a698581138e25a99618d5a4b74bbf9e1638871d8b16b3b8176ad8738ddfe7e3ed9d6570452f1ab77e65ed9e99185dba34f19846e5f3c20053dcdfd4de8044def5fa87df55b1acdd947563029554ce2e9afcc28d531740d04a5ad4565ba82701ac776eebe62a14871779fac8b20b452603e62b71c029b3983066bbe33fdf88605139794fb8c2b3ab65f6829411ee73f718cd5a02e652873dd4f4914a61be6ed0d4dfcc067100ee854d636e3cc3b1b9ba1c17cd7dae70070f19fd300456d9549bd3fddb0ab494b6bec05250dc4ab557bfd478246869410e7efd5deb43cf1f982a1bd1c5f5fccc70200396501a34e9d32eb1344b73895f6101a482f42120a02a8e92a83b0b9afb7dc3274581d5cb1d4e57eca94a1be5bdfd0c0948d91175ad5b635c5000eb2c0b6d47ea710a868e1f3dbb7e62155ad921d3ec8d74f726cb953a3b7e224348da58e9cf311396401aaea4c5079a3240016ad1064050db5af5b8d8514bd6364d8addf8edfe0248611b21b1b04475e7cc580568d1c1eb7f616d8c672625f392c5789882df777b7fe3eda005ab44a03402db9aff00ecf0d8204c01aa5bafb8c016ad11c0761982d826d300aa857f6a7cd4e2173950375ffd55cced60a5eaff003f6940921a2f52a0801868bc450ac25387a24403ba771022af2be94145a4686a557225d38e71f7d2ac51349057826582610c2b4dc0b255b438082596aed280abe685076cc2931156ff0048ac717d8a3e836ac35d5023881b06ee100a849ca2398b3a4b595cbbd43800c0bca23f0f9fb7925c07c0c0a11656944edd61918f0bf9455555576bf71ebaf0bf1799bd6dd0656630230a00ca5e95c38372305008554ba8effc248d8a2692392aad5795eafdfaf419003b07711522b45aa81a6216aedd45b6a52a63271f52558a24bd28d589719a8b18a98ba82d86e040ce88d5cbe2a471620fb6066f52b2eea2c84d363633105b561ddfb8c98aaadeb0ecb6653f8445c711b75638965300bbaffc8ca895bd38186bf139ff000dc16ea5ff0058fbe7bd7540eff72f39167221e3571312e327fa732d0c1b4899508f5aa0ab8ea6c16822877afb19cd601d330c019725aa23759392aeb0bec405325bf7f6b3fe25e782002258ec65640a16852ffdfba51e9f0406206785bd7bc01ae610c0403344085d1cc76aa21d8183f5e8ab8886db719fb8510ab57583cccc81d4df9e95ef7da1c23a06afa7d4c0d0b18ff8361616534ec80b555156561f33397bdeb7ef2f720776e6a67a7d2732844c2c7f12abf0b5dd07f33075006828afb4ce5e81e3a4645d378b59d3b4d42e577cdc4aab6af3f42558a2693eb32ba858390e20d03a28a80aedaed33ae5a6a62dfe233cd372565311555555dafa05a1541eebee194010ba6e81f91f04269c506c86d30aaeee0a1a466cc97b641ef1d141a6e9d3f411b468b7ee71f7d002d5a255d4dc0ba3de252ebe6e12e5c4811a4d329888186e9cc68cc633b7f333a43baff917ab8eb944451113770e630fc0c2fcf64fe42a3f12cae5e73e8f9206a590d5d6330fa5682c3e0219c45396b5282e774aff0018f9fbacd3cfc3fb72cd778a0d4a4cb6cb806226b6971c2791e0f44441a5bf3097b60bb70174543eba952bd2bce959355fba837325216807780c6712e20546d6956daed6b7cd4bfa700971f65222349a7e91ab6eaac7a61d7055bfe9fdef08a160966ccf696528d9d335069b2be231268bd01e834f1ee468417ceba4622ef2c4756baba58c88cb696a3cd24aed22956aabd2f848079556134260f4035a3c693ac1050ba88d2566cafa46d744ec4294d508ab37d9e250109c2d7dd035c56bdf5295894367d3045b1050f100aaae0ef9f4692cca68b5ff00be256ebca14a7177fb87d69805d172e5b633ad42540b4b46db47b3c05af42ea0e161b73a97aac35d2dd70f044f5cd53b96e4700f3de5998a64b3704b116b43fa7d01b1b602a1ae0671f7500d2376743ac147a0a2b27bc28b43946eef55326b2c1a20f15f3151d1e58f560e4b2bafad2409daf8ff00c863235ba5e3b7a06341544744ba6c3c5425011c56a2d50859dbff00b053ad113de3b5a460896261ae259288c09c6a929ef2ad10d91f4400b56888497417ab846a7220d244555b5e7ee1bdccb735d60a8382a7765cd5b2cd5bda05a22aeb36d5352f80c7a29c859d98ddfc86aac91f60d6a5187cec055eb571761b85197fe202e15dc4b0f1ed296497ae33f31fb09d855f1af44bf4421baae36f8962cb64e50317e8e366cd22db749c7d9b0b1a2da3444054c36aa1762772a3086e8b61116cfca31a88393a91b2bd49841267cb9c355f8ffc89bd42684c7febff003eb245930ac95cd4446a71fa28fdc445111363387667f7b4264c01f3b88245acf172c4be08d6aaae20e20d5c559ed3945490ced627bca4053257e61e99c55379ebc42a856c59342002de8fb84cad3410736696c81946650eb889b510028bb8ba6b0e7961112d5b65697557cea66aa076e6a12928a64ae2bec12aad0a95a3fdf100b05b253974d7fec7b91269d56b79dffe4f6c6ba76f5b5089666a4acaaa0605d569bf7f50c9d361c75b9c7d848d8a27243b41a884f975fcc1401c462763f899926605e7994b5a8acb7552daa5644a81a72217132bd140aae7e3ec87a91c268746365c1a4a2cadf91972e70d59ca5ee2c98e3e033ff065d978219d2a9be05372b0525dd6a0953d56dc7991495c639f5a32db70a4d0fef781611c8940cecef11588ba33eff70dad6abf84a1b6f53ac8abdb048e9a50accb0c0428736e3d50a686d6aeab3126296f75c3afe7eca4446934c1d504dadc328364ea1d62d58b4469057a6d41661f3c435dce94d458f9fd7a71ebc7d60ba161018a5abdf508f281b362746057c8dac3cd4aa70bef2b92eb1bbf46ab40ba307b7e6661c9452fe6014500384e9f9a895579fb3595ded8b747538788f44594349c27996adfc91afdc61dc5afc9972d7119295500460ba89ec2bda621b05cf68235e8c46d04662109462c750de72558b39d7f1096dabe6d5f9cfdb16ed283ab1160e61cbff9129cc4c09ca09b9514e097380d4be9a01f6ffefabaab9bd9bbe26074b7ebf65258ac7589556b3d218f4af537ef3d0eb05e3b145f5eff00602e032b5e20c0e93231da34ba59bd7d17fde21b3ab4115e97d3d0b59703b3cc228f2434d99c7c46999b76ddf9eb0fb20234d570bdb3e82b0a8865ab0e741287052868edf111eeacf98a1f623cb0d431250cc12e42570a0bbe11a94038be92e522711f15154d6b2ca553f19220b66b02fea26cde03ede5a5dabe8f12bd12f2c9306c4835e48e78982a4d47de94fbd466eed1cb7ea1830e7d88e85aadff00017b63a8d7d60ad02c66f8cf7657428e5177014501bb15fa824b06a0c029abe220b2a540aa15ea6c52f55a25b02b77d4bdd99326ddb7bfbb8c4a2dd5c2488c0064e846ba1bf69760d758d691d352d082fac6d10c6eb6636c14354d0f66082c769659fc214748af2b35f2c7057b29e8273f75ee2325fea11659516de2e63ab883486609bbc4da1cc5b5ba0f6ebeac636b44a68d95d56fee60dd0e74c5aa59b146eaaf682ef9ebab1735d5c2d4ad8035559dc52e06c9616345b468fa02b40aba0978b89ab4cfc430b0e8899bc4569b00079f4a542e9b8af80f1292d92397e8b03190bc95f3a809509a465fbe5285b26576dcb0a0ebde0ad697d4f2cb46f5de30eaf11b080d6e2817a04b6ed1390c86ea5db0b6dd7cc3875d06407111054259d9fbfb8f5965df810d40c15500d8eaa0a350436cdb79f698324a9d0666598163bf5f5ef405bee43acd86bdfeedbd9451e2efb18d3528b28ad945161d997bc4eafb425ad62e2253a27c95ea70f3fa964012ed66fb4484c56083bcc255f44bab45358d4a258e5d4be60e28b3565e9e9e8abd15f168eaf48bb2b479d7f7b4ca4675dd2ec57a00bcc42a6c75d23f7c8cd312ebff008102c51f788bab997c5e0a62e3bb12759553bbb6e115588d8c6a94ee8ccbd3b7a0b660ab0bbe669fbb51a9c8f1c7f7bca198c40b7fb8baa2c666df6976d1ef3ae879088c59495b7a71f8f5417870a0b686ff003129555777f7451b14801081d97b80ba16580d01c876d460dd655b05758a4e93640633897a76868d41da6e00508b58520dca04cb3efd6095d5c293f1ff006210adda8d6ff984027330fee250e355d58eb99a424b94725d56f1f129519564e1e21a6ca139100d38bbae20a6b6c07f6a32b4af55b8fdfc48155e6f9fc44915bbb5e3c464526f2fa53853887cd8add4508e4ecb8c980e00a82596d1c3510ad5a0eb5a1d387de10daf071cfdc3d69cbbf31216015b881c1a8d9a7e65ebbbfc4b458d5f58cb92a26314316abd494e70ff00df518be0957bc4b099969d1e3ee54f781542d9412e805f5fed4ba0d85317d1edff0065670b80b7d10cb9c408a9078b83220deef3e9918618bd5cc8caac9755dee649ab43c3ff0022708da9cbf1c4a748a1ab63b78953ce31ddf10b256d52acc365b6c2d9716b1bdb60b343bfb7e21fe12ba09cdb588544a111e1894325b4737bc7b41400ec15e8be994d4350bc41222d6a5d11e602086f20438f115da73796f32cd71f9ba95f6c17df9536ff007f98ef36456e98197686a9d7998970d17b25c94b8357d56354367a399808160e8707d83b04e006b317f714179d40cfb5c1f7e408247041aa4d3bbb6fa76fe6062525a8c78be2ff00e458c259003f1e79ff00e402e865b71cf581560e8ac4c999685f60412aab37b9411a6a2cb3a5bc1d5e204dba997e3a4e05f81570290525a33506014e569e0eb1954c2a33550cc817918f170c10ca1d5c402ea736b738b85e13c59e097f190b435e7fc3da7166f8ae6158d8c50e6f985965269c57bc3a0072b080a1eb7823e1ba2557e63e72af5852f9682d28e032b196df67f50fbb837535dbde5d2a86078dc41a4d7245327e26422d6afd21de5c46ade2aee27529f45c502cb87202cd31474fac2f539707303de5ef620b22b2960aa6bf98b9a0985cb13a0aecb0fe219094ef41d7b5530e6535d08af764e902343576d4bebea002f0adebad74805000e022db2dabb6eaa3bcc0689cfcc026cd6877f98ee2181cb104da1d5d9d259136104d7b8c496f78605c1fd207a8b2f15758636562363d7bc404c23d662aa54ed7c5c56b8bbc57e88b005e832fd54c5386f87fc020edb6673d58639841ea3e8f8103c1ff00a4c04021a0aaf56a1a56f680e286cf38a7efa2d20bdc273e10384a94747e62e18b7f10b29599c56ff1718a0306eaed78ed2f787a3f5883ae0738a022ba02b5f49b0156b596a008ae98a1eeff009ef1f9c8526355fdef2c9f01eaa0ec3ae70e0b3bdfea5eeb865970d037898ee01680bc5109911dece1f310d2d8aec0dac282c2f94b0ae98437bd4bd06c2bd6ac2345329e655e3356dcb83ea28d8a2698d21584ee186c27cb1e97352c2c2ca69d933fe03082d5a0880633bb8df2a95e2bd1a922ef37897e65ddf5ada4c3716b3f12a947674fbfb3cff642db318c123eba25e584036bbac07580a436755f29f9ef2c02114b6b7bdcab1e13034e9b133f898314e75c707a62096475fd6110e8c1a1edbfc4ead580bb5b5fa97d090b1c255e6d1ec4c08bb3510333358b0833ab71ef1f975c6c699ec9d215d5c97595bfe7f11d5e25eea5d352c60956deeb7d9ee4f42644aa53e25fb6aa05780abe7ffbde58117518b8d3ef2cac30a7f13541b34b9b82bda5a84d2b47f98c982ef2a9d436864711428d1afc3d4cb0d991fb817313b8905631516416ea28528dd7f3fcfdd680285ee3b341b3d1cab913f9fe21bd38850df49ace1f30469405adcaa2d22b9ef2c02a86a1320a0568ef2eb904530508ce8c696279d0471052e95ed6347dd7b4447a16a15b9b7476205dc6587012892686b2dee050a4d052f7da2d93541ce0159b68c04a082a8144418af609612e40cb625250e772f98c11480254236f095a8a1bb6a8ebf12987a44fec965be3fea64e9641b8bd930bfcad3de6152c721c97f2557fa5b9d2c187a3fdb8aa8bd6711646c0fe3d4c6cb98fd04bb14570044c94d551c1f7363d51083d80735896e050375f882c526c89795b10148a72c5742e2158aba9f883dbb914f2e83bb0b64a5da7d46a83f2ed0713d466fb14fcac34126a7ca2e3a55d705e095a659c3e257503a2c99997a027584767c0805b54481cd22665eaf9b4361b0e2b16ed6233506a6db9aa6d54562e39415175c46188baecf3a1a679944048f1ecd322ead4beb085b5b889d6d2ef921003531866a6273c828601dcb18e0ff48081578238ddd2d071e7fbd228db6648b6dc4f4b99c59c8c62851c3362ea5e920a65c44650b120a6451fb84c05e9567ac2e28504c54e51b5c9fd4c4b953e220fa85f882258d8e921784053777fb352950b6b47c7fa65b408ab5276e53ca3b3080bb031ef4621b91755be2084b3586529737bb8695178b9c40b82a5cd1d200d8822074398d0300dd6e5012a14c42af68090f664181d5903b10d357a60d6bbade4cea621a6351603174005050cc6ba06c0c2c71cf88d99000dd6d56c986dc5ca58d06f93697c27b938181cc0d8044331d2944bb5abf15b3cbd666aaf1fe8c050a5df495980c745f58915b94ac30152c293b4bcd0f3157133de731b23b499c14566459b6edbfb95887f3f086746fb33154d598e63b172d214a4672665e945b7aabfc438e8005a2d51c6e02ae33dd35bdc2b4d3003a3a4c64a3da0bd5b1a758ed1b39c35b944f46e75d58a882e6977da5da472f1116055b5ed032ee35d61d0e1c98a9706778c6611a56beaa8bcad998952b681650de0de5032519292155539390a43967428877ef19f2404106523fa41bc65154577afdc971ac0197b28c28cae465065c0f0234fb2d9de531026174efdff0055fe885f21e602d05e340fccbc21ac53e7f9885b5855f3709c056c37d48f212f840ad57a8ee7364c6b03939cff007f7f74b76c86e26051599562b5ef36d0ec660a85f83887eb8b66dcfcd45a0d6c2b9ecd72c35a369c3a94136a9c317002c8e10b2bf88908af0f9960bacebb4174de33773285aef19967ab2bc0d21f136f08d4d85139db101ac56e123f287c201a298016844a561a5df78ad51a8898c5dd7b1d054e009d9af9df34c1c092a130f5a3a8f82fda16cd84659c3ae6a028ee24fc02d38e4be629cbd84209f3fa9688d75b89622bc2d834f70ed005df7b3fd10268d26304adc2756ea35a94b29a8a591025dbb1fccad3060276f42a8476667fb28eac75554adbfb673315298097ef1c8075566260c992e88799657bc510d8399903359cac341be2982f3e60dc2dc90455daddb3e41216d6ec756045d86e22e52fbc3b0a8a890748695b1e661ac9c4ce2f3b96e8ec3e65875c03f10ae7c99b6d07a8b5555c44502391448176a62b9b1d4eabe14130ee158e390a4107723b5eb19723060ded1474a271603ce0c47d777791806c1ab39011fcb91e5398756afe14bdbb9e475ec5bc5913954f11c3fbad40b46a338368f7b1ee436a9293bfdd29c01295848a5286f96f7fe19b5586173120d3bdc6b2ac2f67a44b0a9299dd4b904aa131d1cca7d0b823845eb9a8fdb1884dae842ae473ca34c89b1118c198324c088705c520006cc5d7a80e8e5cf7e20c97766d61a581343ba9b00894f261a3286d88be532b2c4810b0060d2cb30a0dbd65c2ee8a3a44a200405c1bb98092aefa93765d02dd473451976fe331c53ea90c55bc98097c36625598c61414705c013c5c1ccd8562e056383a439a4ab0151a10ae06edc972d7c822ae09ac179eabb8049cd05a4de511db52c19fb716197c34fb454241a34d55efc43a91768bd0084a516ec06c9db38164b000d4000ae00e89f057da013816a1a3d01742ff008b6d65c183b4b5f1ae87a22c16de9e8d5dcb2252e9d1082e5a8e71e8200a60a83f6f161ef0382231a84f10a4f999c15ebbf98f165501dfb4cd9a38c1e43a406779e78826031589944975bac4a175b4523734e2815d97a90539575a8920ad6d88c2ac71141956e2a4acd2f230997dd99ee08255bbb0820583867b3160552dd99be545df0ed285d1d0f98e7a98bbd4066849b3e2c2dd046a0c0051740d9759b8555460ba3ec3fc21e8edc0d7b78f2fb4bbda82de87108da679e5185bed858acc019572afcca60d5b8bb78498ddb896116346f19abcd7da5510e16939b398e0448e05d527b4bde1a6bad7f8dcfa66114e4bab3a4db10d61683a89b22dd56f0e2263337d6174d936a6821e7e04dd96e8f600fbb8f8894a08f73ec5d0740eacb34e75184e65e15b321e9da3346b7ccf2cb5cb257bd24b5817ab6a6bc8155623085752afcb9740e2e1252861ec074aa8ca86cf1855dece52ef56f0936080ba83b9d254e43bca8285d8a308f48dcb3ca5727b471b17561c90f29c597795cfb4c88ac52f30dda5c99c4bea545e030430082821f6f8978c1564080082da1a3d35163a1b1c83a272437ac940eb7b85c8ad6679ce981285a170783edd51ca82d8e83fc948d8a26a221c3a82b55ed1012d6a6509e22b45eaa11fbc50a2d016a8368b7ed33c6336abd70cbf32cc082e0be19fc46d53e6a3ec64f723d30d89492bd499b4a201834cab6f31701c412a838148e05a8941d630e6270bea8dbd48ce2bcae89c88805eb8fd72c2af36e140cbf3f81c4cda82e51a9add4ed84c2df0dcdcab0b184ea0c8356982a8633d666c96fcf686c6536c212761894945d747a4694588b39ac43c70e8ecc4581bc1d4b98b6d673509c002a82ae23b0bfd32b5d0e2b3e8079ac4c5405005af1098e8221f054d803a4571fb40aa0d86deafe23e85fea1889a799b9cc0983a622fda78dcaa5d67287877f1119282e5df3922970348fa08caead85759725d1e48e443d2580aa6650da50ffd8c63b56e9d35055db4b960c9b2295215890f84f2f61d11015955deba78834726374eb0409562ca109c642aac0e1880abb2e12eaba32a8b9745c3d881b57719f6b77d6067b7388e460062dd733d39e4fc92fe46556695a4859a0437cf59895d9cb47494d03201caccbf743821cffa82e6f18d74828d8d26980bcb456e65f03a478c7fcfcc28b97297b20b81330120e3e5f1138de2100414f313215e262db0e60c358e92860309b848285cf0c68d60abef6c746f635c1c43100c80f689b42898c9c731eaf38e91d1672b784b225da20082dc0ea238c0a41647e41e0c6813f78d0a3cbe2a3fba9c0f04797a2d6ea241554b5b8f62331982a5bba7408ab131d12cadeb8b512e6e7acb0aee398416b71770d7cb7f11e10cda1bdcbad029466f533f490765bf31af459a7305c4720efa5c4f69747078ff004bcc7e9d938f42b82952bad46c78af44cd601451d8d4bd619988bca0134442e48fa10aab24eb057c41bb081c8e0bb85ac4bc2d213b2d85db7533a6f88aaa0188a2b2666102d42a195dd90ad31e08a45168ae6b714aaaabb62896d6266f038edbf694470a1c34c05416edc8a56ac82e7d3bc518f4536975e0cf6202c34a15c4ca0c9bac5cab2a383acc538303577ff2104885fcb092c03cadc31101906af97e262dd541a0730980a1c1d0ca994e345d62704308d3e229556de597fe78d74f73ea018acab8fd7d2422463c0a68f88f24e022f9ff00e40a20d6c8974e30897d22dc7c505b2171add7552dd11dee7882a0adb1812a7df84bd01a6e24eaaf88da5621edcbe65ad801be94404455bdcdc2fba2b438304f5070687a3324954e6ec3a40d197da0951b44551a15de8f79582e334a30ed1543182729617339d5b5af111578a7c9a3dae575f507e52d1673bbd1fc4ad16ab02bb1080da60dd5ac50211b15971ca97d8c7fae28b28384c0f198448d963b299d1c8aaae4d40b46768a0540a0562fc7a5de50b74da25c5ec7c3e65fa04ffa323ebb438867d56c0c5ac691cd310b52ad56ec6f31b95aeb8f401cd17994a7581422b464a3f11adb3777a8d7213438eec74a4dafaee57a0d20859ea7b20fb430eefb4aab3de3baf668599e3794f73300d2421a91a51e683cdcc61975e79eb12b416518892682d4349ef1669b25e18a2e4e19fdc64a4ed5b7fd5f1f4541605a5e5e910ba0b1a5f0454e9a2b4b833ff512b7882bca263403b1203ab2ea869ed0e5872af816717ac540cad8a157aa61ec3e6320dcb4bb2559ee1129d941ec7a4a2a6ca477d22a949a0c0d55da888e44f681554b39cdd758bd0096a624b85cdb3e1d08aadadafd8c3a1a1f0f494cf30324cd1a1b4888f7c2503010e8a0511b18b9139e9e20d062e5394b322c3cdc50a2af2ffa43d06730fa516a21b6aea02a0f4faea546ad4c0b5e373205a1a4e084a4d164bfef689e4a5012fd628d07b41682a9bf10940634c5457dc8badb51285dcb783c90dc3b45fca2c5145638262a10c6d2cc8524b409ef2c8aae06b853370810e81dbb768fd9b970fa3bdf609ee2c2ba90d415767411aaaa5157e57c4634b71c8ae92a6bfd195346daeb0628d4cacf65f49cb40c1fdfa1b369d566dbf89cfd206b48c6711a0d508abbb3f899fc7add1437cdbbf4c2a054b7528992afccd9311502199c5a731aa290c1b84aca5221c81c748c12945c42111470c0381e580727ba091776759430c5ae87796faca0d2b4df53ef5d65376dbe27917c8ffa50aec98c51cce6b635ef1e36eb183de602951d4fee0ac958b183593b4028107fddc1061c1290fed88d0655aa87aa1622723301500a54baada5164acfc730f420a3634928519714ad2f105982163c86e5bc17cca96c476f0c6d62b38dca1ce4c1ea4bc1550556b117f2213ff1898e9988c15d89515a3a4be0499cd799ad0b1687a763f70ff5c7d60085a764097d2bcafb77dce8ce65962d9adc298ece9364b8db4eacfcc04c0da9323c2424005bc31114444d8faa07bc7baa3cd01793bfd4baa459131f887aa3029d1f210b40a8ac3ccd0afe25841f300ae69a8416aa2ee35f7197684d4971a6b398e3437677dcd611579201a121e03f33fdaa4d35326ac3bc5dc82b9a75f105884cb43896a6e61e8f697f8cdd37091a156f397ff3d188b798f5430f633115722e0db14ac3776daab98e4e86cf424594b95bf1015582fc40bd916cfbfa12dc5577acfcc34165838067f98952b6bb7d284694651a892ba711811e529b072a111840587784349af88514b5c469ed75788bea714393dbfdb11886d688a4a50cae53b4b2cb7b2f5fb838dc8d067025b32406a104d9c5579805c2b40dfa00ad0393ac2193612bf6799663a97842f48bb951e85637926645b3ac8dc3a0e00335f8f5e202a32fe50f17e8c94f4ddaa00e427329088abae7884a61cb31d0b06acf303ac3008593f1108e32c8fc1fee09e31aaebe86a009784b5b0252bc31e1d3fb95658089c32f2de46b9aea7582ce0c95a8c4948d93500fc317af80d1e8d16ea91baba9a6ace467b7d0610b88e734e7f7f3e9fc7fcc0016d73d20f09b58d8e7b54707539995f20f7dc497b99a815b4dbf28f75a5aff00b84a121b6f5e9540f597a8573922b4c8fbe62002d34a5d778cf2746b6448d8a269258ab6779dfd4222366eb35b95eaa359a146755fccabce6aca98e06eaee5a09ad74265431d1804640e25df71b2bf70e76d3c05c3f6fc469a57fa9041501ba8e3b7fb8398d32289a7d3f4315d16779c87bcb045470b16d97807a77a89c3dfafd6934d40ae0bc43619b376567d2901ab9e034cbd182c54178ac869fa96ad4da88ed692db4af99ad5f7885b7a2f52d8cfd243dc72f9201e028ee85d5776b37fee4e62155586e3b053569fd31cb68715d6183c1880a180d54488edb6008fea2150d7cae393a4d9f5fb044749d19b05983de246c5134900e0283d5aafef78e851b3ac0cdb1d08117683b8bcb959ce639252db5c67092e241a208650a2ab0c5ffb148d962727d07d5bde443b34c6b2f26c5533da130514615a8b88c3da174ddeb3d370cda0a2681c074e6319860115143b75fb0c3453affbe60a363491e5b4c6747ff206a602d61925f6277a115808a4a407bcec7e15ac24c2ba9dba78ff006c7d486335c3cca130e8318803944c6e0e6aba50c4a86d6291fc769b2c5e525bd586f98511788bf88c85853ffd8c98db4bf59876489ce75fa8b6a980cf99565075fb8f852258ec5e158ba138178579e9700b9e2c1abede652c0f83dfbffbeb0b0b29a76406ae397fea350671b8fa979a63ece154ca8a79ae23b6cba8d44c52eae63ca00e87e798a4d06cfad6f172a6bf9aa0b4e0f115a0cf116b163c9731cbd87f31da614aa9f8895ab6ab5ff7c13a49ec8a40b315b7895a520a22289a8550ba0a348a1335918603bf8d7689d81c2448d8a2692565c8c0bbf9fa8958b06533c07b15f98c44d90fd9eb5feb082182aaff008c12b97bfc0ca5e619ad42a2f984775eb7248d234ce812d6df3c7ee122bc8d8f1c9ef2c897796438e64a6bcb05d779859c597dd9affbf4235e17bcf9a0f78828a834e00a8703042013123e3fd51e06af2f4968d448617fc526b92201ae9de047ac10e9d38655b5098bba8dc732cc0650873687a0b15295b076edda34b106fbb541f882217aad09cfb16bd50369784fc407d54aeaddbf144c491c61186a72a897fea74f036f4854ae19797bc05bcff17f8c344aeb2e3dfb4751639bddfea0e026a2fa0d9962d430862de3710b1425564885c11f9768e0bf27211d005016cc036db8f4af420652eaf09fa637dea3605523c7a3977506d988b553e5a15ebc57881d58f07243a272c3a80ad4aff51d32edfe08001170732928d54bdbb5f68756405b881c0b7b157f6d8ced092c1ea960d057f5f4479766a3a2a00e324105f288773ac568868c33c5bcc5162b90a401c88b298a05a6c62d47f1707905557b4212136d4b11af560ee4ed1a9cc0f6a23918b2bc27596e54e7a448062c103a0ca38892d370a870ca733e3fd3de0a838c6dff0020050501404a42c04116b89512c55f9fb4fd362ab7166cb6edbcbf64f4c5cf2cd6e171417c9f31137aebda08b7b55a42b4dd8df4845abdc5dc7db6fbc6a0e8af5b998f917412bff6580abd38c1f8b99312a83ab11ff219ae636a76e60260786230bf32cd547ed09416f6861e0def2cae0e8b1dff00a67542002d38a5f13b845d5e0421a8b52d0417308077e7e7fc50b2064ee96aba407a6029e6889bb30978c7a0302e19d42a80e831e0a1eb999325f822c0ebc10493935bb15fdbf30c463de28daac01c15daee5a708486b100992f8830b219a8cd36df32f6ea9351ea2a7fffd9, 'image/jpeg', '0901 234 567', '2026-06-30', 'taokhoandocla@gmail.com', '112520006843488341382', 'admin', 1, '2026-06-26 19:08:20', NULL, '', '', '', '', 'Hải sản', 5, 1420000.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` varchar(50) DEFAULT 'Home',
  `address_detail` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_type`, `address_detail`, `is_default`, `created_at`) VALUES
(1, 2, 'Home', 'biên hòa', 1, '2026-06-02 11:43:17'),
(2, 45, 'Office', 'văn phòng', 1, '2026-07-01 12:14:51');

-- --------------------------------------------------------

--
-- Table structure for table `user_milestones`
--

DROP TABLE IF EXISTS `user_milestones`;
CREATE TABLE `user_milestones` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `milestone_id` int(11) NOT NULL,
  `achieved_at` datetime DEFAULT current_timestamp(),
  `is_redeemed` tinyint(1) DEFAULT 0,
  `redeemed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_milestones`
--

INSERT INTO `user_milestones` (`id`, `user_id`, `milestone_id`, `achieved_at`, `is_redeemed`, `redeemed_at`) VALUES
(1, 2, 1, '2026-06-29 18:17:14', 1, NULL),
(2, 2, 2, '2026-06-29 18:17:14', 0, NULL),
(3, 2, 3, '2026-06-29 18:17:14', 0, NULL),
(4, 6, 1, '2026-06-29 18:17:14', 0, NULL),
(5, 6, 2, '2026-06-29 18:17:14', 0, NULL),
(6, 12, 1, '2026-06-29 18:17:14', 0, NULL),
(7, 15, 1, '2026-06-29 18:17:14', 0, NULL),
(8, 18, 1, '2026-06-29 18:17:14', 0, NULL),
(9, 18, 2, '2026-06-29 18:17:14', 0, NULL),
(10, 45, 1, '2026-06-29 18:17:15', 0, NULL),
(11, 45, 2, '2026-06-29 18:17:15', 0, NULL),
(13, 2, 4, '2026-06-30 13:53:49', 1, '2026-06-30 17:45:21'),
(14, 5, 4, '2026-06-30 13:53:49', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_vip`
--

DROP TABLE IF EXISTS `user_vip`;
CREATE TABLE `user_vip` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_vip`
--

INSERT INTO `user_vip` (`id`, `user_id`, `plan_id`, `start_date`, `end_date`, `status`) VALUES
(2, 6, 1, '2026-06-16 10:36:03', '2027-06-16 10:36:03', 'active'),
(3, 21, 1, '2026-06-16 11:04:15', '2027-06-16 11:04:15', 'active'),
(4, 2, 1, '2026-06-20 10:44:04', '2027-06-20 10:44:04', 'active'),
(5, 42, 1, '2026-06-19 21:24:51', '2027-06-19 21:24:51', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `video_type` enum('youtube','local') DEFAULT 'youtube',
  `video_url` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `video_type`, `video_url`, `file_path`, `created_at`, `title`, `description`) VALUES
(1, 'local', '', 'uploads/videos/0f88aecd97b86f173c7e.mp4', '2026-04-02 07:36:16', 'NHÃ', 'Nằm giữa lòng biên hòa, chúng tôi mang đến một không gian ẩm thực tinh tế ẩm thực văn hóa cao cấp.');

-- --------------------------------------------------------

--
-- Table structure for table `vip_plans`
--

DROP TABLE IF EXISTS `vip_plans`;
CREATE TABLE `vip_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `discount_percent` int(11) NOT NULL DEFAULT 0,
  `duration_days` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vip_plans`
--

INSERT INTO `vip_plans` (`id`, `name`, `discount_percent`, `duration_days`, `price`, `description`) VALUES
(1, 'Hội viên VIP', 10, 30, 299000.00, 'Quyền lợi Đặc Quyền:\n- Giảm 10% tổng hóa đơn.\n- Ưu tiên xếp bàn.\n- Huy hiệu Hội viên VIP độc quyền.');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('main','kitchen','bar','virtual') DEFAULT 'main',
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `type`, `status`) VALUES
(1, 'Kho Tổng (Tiếp nhận hàng)', 'main', 1),
(2, 'Kho Bếp (Chế biến thức ăn)', 'kitchen', 1),
(3, 'Kho Bar (Pha chế đồ uống)', 'bar', 1),
(4, 'Kho Lạnh (Bảo quản thực phẩm)', '', 1),
(5, 'Kho Vật Tư (Đồ dùng tiêu hao)', '', 1),
(6, 'Kho Xuất (Hàng đã bán)', 'virtual', 1),
(7, 'Kho Hủy (Hàng hỏng/Hết hạn)', 'virtual', 1),
(8, 'Kho Đông (Bảo quản đông lạnh)', '', 1),
(9, 'Kho Nguyên Liệu Khô (Gia vị, đồ khô)', '', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_categories`
--
ALTER TABLE `about_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `about_comments`
--
ALTER TABLE `about_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content` (`content_id`),
  ADD KEY `idx_author_ip` (`author_ip`);

--
-- Indexes for table `about_comment_bans`
--
ALTER TABLE `about_comment_bans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip_ban` (`user_ip`),
  ADD UNIQUE KEY `unique_user_ban` (`user_id`);

--
-- Indexes for table `about_comment_likes`
--
ALTER TABLE `about_comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`comment_id`,`user_id`);

--
-- Indexes for table `about_comment_reports`
--
ALTER TABLE `about_comment_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `about_content`
--
ALTER TABLE `about_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `about_likes`
--
ALTER TABLE `about_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`content_id`,`user_ip`);

--
-- Indexes for table `about_saved_posts`
--
ALTER TABLE `about_saved_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`post_id`);

--
-- Indexes for table `about_shares`
--
ALTER TABLE `about_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content` (`content_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bespoke_budgets`
--
ALTER TABLE `bespoke_budgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bespoke_occasions`
--
ALTER TABLE `bespoke_occasions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bespoke_styles`
--
ALTER TABLE `bespoke_styles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_details`
--
ALTER TABLE `booking_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_inventory_deductions`
--
ALTER TABLE `booking_inventory_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bot_context_logs`
--
ALTER TABLE `bot_context_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bot_responses`
--
ALTER TABLE `bot_responses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`session_id`);

--
-- Indexes for table `chefs`
--
ALTER TABLE `chefs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chef_certificates`
--
ALTER TABLE `chef_certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chef_id` (`chef_id`);

--
-- Indexes for table `chef_gallery`
--
ALTER TABLE `chef_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chef_id` (`chef_id`);

--
-- Indexes for table `chef_reviews`
--
ALTER TABLE `chef_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_combo_theme` (`theme_id`);

--
-- Indexes for table `combo_items`
--
ALTER TABLE `combo_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `decor_packages`
--
ALTER TABLE `decor_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_decor_event` (`event_type_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_types`
--
ALTER TABLE `event_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_food_theme` (`theme_id`);

--
-- Indexes for table `food_recipes`
--
ALTER TABLE `food_recipes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_toppings`
--
ALTER TABLE `food_toppings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `topping_id` (`topping_id`);

--
-- Indexes for table `footer_links`
--
ALTER TABLE `footer_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `galleries`
--
ALTER TABLE `galleries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_audits`
--
ALTER TABLE `inventory_audits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_audit_details`
--
ALTER TABLE `inventory_audit_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ingredient_id` (`ingredient_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `expiry_date` (`expiry_date`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_stocks`
--
ALTER TABLE `inventory_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_wh_ing` (`warehouse_id`,`ingredient_id`);

--
-- Indexes for table `inventory_transfers`
--
ALTER TABLE `inventory_transfers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_units`
--
ALTER TABLE `inventory_units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `milestones`
--
ALTER TABLE `milestones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `navigation_menu`
--
ALTER TABLE `navigation_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_item_toppings`
--
ALTER TABLE `order_item_toppings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `topping_id` (`topping_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_orders`
--
ALTER TABLE `pos_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_order_items`
--
ALTER TABLE `pos_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pos_order_id` (`pos_order_id`);

--
-- Indexes for table `po_receipt_inspections`
--
ALTER TABLE `po_receipt_inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_expenses`
--
ALTER TABLE `restaurant_expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `fk_sb_chef` (`chef_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key_name`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_certificates`
--
ALTER TABLE `supplier_certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `toppings`
--
ALTER TABLE `toppings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `topping_recipes`
--
ALTER TABLE `topping_recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topping_id` (`topping_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `transfer_details`
--
ALTER TABLE `transfer_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_milestones`
--
ALTER TABLE `user_milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `milestone_id` (`milestone_id`);

--
-- Indexes for table `user_vip`
--
ALTER TABLE `user_vip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_vip_user_id` (`user_id`),
  ADD KEY `fk_user_vip_plan_id` (`plan_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vip_plans`
--
ALTER TABLE `vip_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_categories`
--
ALTER TABLE `about_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `about_comments`
--
ALTER TABLE `about_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `about_comment_bans`
--
ALTER TABLE `about_comment_bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `about_comment_likes`
--
ALTER TABLE `about_comment_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `about_comment_reports`
--
ALTER TABLE `about_comment_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `about_content`
--
ALTER TABLE `about_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `about_likes`
--
ALTER TABLE `about_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `about_saved_posts`
--
ALTER TABLE `about_saved_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `about_shares`
--
ALTER TABLE `about_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bespoke_budgets`
--
ALTER TABLE `bespoke_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bespoke_occasions`
--
ALTER TABLE `bespoke_occasions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bespoke_styles`
--
ALTER TABLE `bespoke_styles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_details`
--
ALTER TABLE `booking_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1088;

--
-- AUTO_INCREMENT for table `booking_inventory_deductions`
--
ALTER TABLE `booking_inventory_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=320;

--
-- AUTO_INCREMENT for table `bot_context_logs`
--
ALTER TABLE `bot_context_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `bot_responses`
--
ALTER TABLE `bot_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `chefs`
--
ALTER TABLE `chefs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `chef_certificates`
--
ALTER TABLE `chef_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `chef_gallery`
--
ALTER TABLE `chef_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `chef_reviews`
--
ALTER TABLE `chef_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `combos`
--
ALTER TABLE `combos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `combo_items`
--
ALTER TABLE `combo_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `decor_packages`
--
ALTER TABLE `decor_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `food_recipes`
--
ALTER TABLE `food_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `food_toppings`
--
ALTER TABLE `food_toppings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=326;

--
-- AUTO_INCREMENT for table `footer_links`
--
ALTER TABLE `footer_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `galleries`
--
ALTER TABLE `galleries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `inventory_audits`
--
ALTER TABLE `inventory_audits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_audit_details`
--
ALTER TABLE `inventory_audit_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=252;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=992;

--
-- AUTO_INCREMENT for table `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_stocks`
--
ALTER TABLE `inventory_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=699;

--
-- AUTO_INCREMENT for table `inventory_transfers`
--
ALTER TABLE `inventory_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `inventory_units`
--
ALTER TABLE `inventory_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `navigation_menu`
--
ALTER TABLE `navigation_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `newsletters`
--
ALTER TABLE `newsletters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_item_toppings`
--
ALTER TABLE `order_item_toppings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1016;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `pos_orders`
--
ALTER TABLE `pos_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1036;

--
-- AUTO_INCREMENT for table `pos_order_items`
--
ALTER TABLE `pos_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `po_receipt_inspections`
--
ALTER TABLE `po_receipt_inspections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `restaurant_expenses`
--
ALTER TABLE `restaurant_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1128;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `supplier_certificates`
--
ALTER TABLE `supplier_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `themes`
--
ALTER TABLE `themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `toppings`
--
ALTER TABLE `toppings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `topping_recipes`
--
ALTER TABLE `topping_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `transfer_details`
--
ALTER TABLE `transfer_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_milestones`
--
ALTER TABLE `user_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_vip`
--
ALTER TABLE `user_vip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vip_plans`
--
ALTER TABLE `vip_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `chef_certificates`
--
ALTER TABLE `chef_certificates`
  ADD CONSTRAINT `chef_certificates_ibfk_1` FOREIGN KEY (`chef_id`) REFERENCES `chefs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chef_gallery`
--
ALTER TABLE `chef_gallery`
  ADD CONSTRAINT `chef_gallery_ibfk_1` FOREIGN KEY (`chef_id`) REFERENCES `chefs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `combos`
--
ALTER TABLE `combos`
  ADD CONSTRAINT `fk_combo_theme` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `decor_packages`
--
ALTER TABLE `decor_packages`
  ADD CONSTRAINT `fk_decor_event` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `fk_food_theme` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `food_toppings`
--
ALTER TABLE `food_toppings`
  ADD CONSTRAINT `fk_food_toppings_food` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_food_toppings_topping` FOREIGN KEY (`topping_id`) REFERENCES `toppings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_item_toppings`
--
ALTER TABLE `order_item_toppings`
  ADD CONSTRAINT `fk_order_item_toppings_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `booking_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_item_toppings_topping` FOREIGN KEY (`topping_id`) REFERENCES `toppings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_order_items`
--
ALTER TABLE `pos_order_items`
  ADD CONSTRAINT `pos_order_items_ibfk_1` FOREIGN KEY (`pos_order_id`) REFERENCES `pos_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `po_receipt_inspections`
--
ALTER TABLE `po_receipt_inspections`
  ADD CONSTRAINT `po_receipt_inspections_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_receipt_inspections_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sb_chef` FOREIGN KEY (`chef_id`) REFERENCES `chefs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `supplier_certificates`
--
ALTER TABLE `supplier_certificates`
  ADD CONSTRAINT `supplier_certificates_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topping_recipes`
--
ALTER TABLE `topping_recipes`
  ADD CONSTRAINT `fk_topping_recipes_item` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_topping_recipes_topping` FOREIGN KEY (`topping_id`) REFERENCES `toppings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_milestones`
--
ALTER TABLE `user_milestones`
  ADD CONSTRAINT `user_milestones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_milestones_ibfk_2` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_vip`
--
ALTER TABLE `user_vip`
  ADD CONSTRAINT `fk_user_vip_plan_id` FOREIGN KEY (`plan_id`) REFERENCES `vip_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_vip_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
