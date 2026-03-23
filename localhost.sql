-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-03-23 11:54:37
-- 服务器版本： 5.7.44-log
-- PHP 版本： 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mutu`
--
CREATE DATABASE IF NOT EXISTS `mutu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `mutu`;

-- --------------------------------------------------------

--
-- 表的结构 `admin`
--

CREATE TABLE `admin` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `last_login_ip` varchar(50) DEFAULT NULL,
  `last_login_time` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nickname`, `avatar`, `last_login_ip`, `last_login_time`, `status`, `create_time`, `update_time`) VALUES
(1, 'admin', '$2y$10$VweGsC1xdtfqtOYDloTJV.ZIa28SRHFvP68O7VS983Pnat8GcirsS', '超级管理员', NULL, '112.47.210.142', 1774178693, 1, 1773561403, 1774178693);

-- --------------------------------------------------------

--
-- 表的结构 `ad_config`
--

CREATE TABLE `ad_config` (
  `id` int(11) UNSIGNED NOT NULL,
  `position` varchar(50) NOT NULL COMMENT '详情页/detail 个人中心/user',
  `type` varchar(50) NOT NULL DEFAULT 'mp' COMMENT '公众号流量主',
  `code` text NOT NULL COMMENT '广告代码',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `frequency` int(11) NOT NULL DEFAULT '1' COMMENT '展示频率（次/天）',
  `sort` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ad_stat`
--

CREATE TABLE `ad_stat` (
  `id` int(11) UNSIGNED NOT NULL,
  `ad_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `impression` int(11) NOT NULL DEFAULT '0',
  `click` int(11) NOT NULL DEFAULT '0',
  `revenue` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `balance_log`
--

CREATE TABLE `balance_log` (
  `id` int(11) NOT NULL COMMENT '日志ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `before` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '操作前余额',
  `after` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '操作后余额',
  `amount` decimal(10,2) NOT NULL COMMENT '变动金额（正增负减）',
  `remark` varchar(255) DEFAULT '' COMMENT '操作备注',
  `create_time` int(11) NOT NULL COMMENT '操作时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户余额变动日志表';

--
-- 转存表中的数据 `balance_log`
--

INSERT INTO `balance_log` (`id`, `user_id`, `before`, `after`, `amount`, `remark`, `create_time`) VALUES
(1, 1, 10110.00, 10110.01, 0.01, '1', 1773747217),
(2, 1, 10110.01, 10110.00, -0.01, '管理员操作', 1774178951);

-- --------------------------------------------------------

--
-- 表的结构 `complaint`
--

CREATE TABLE `complaint` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `images` varchar(1000) DEFAULT NULL COMMENT '图片,分隔',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待处理 1已处理',
  `reply` text,
  `create_time` int(11) NOT NULL,
  `handle_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `distributor`
--

CREATE TABLE `distributor` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `real_name` varchar(50) DEFAULT NULL,
  `id_card` varchar(30) DEFAULT NULL,
  `id_card_front` varchar(255) DEFAULT NULL,
  `id_card_back` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '分销等级',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待审核 1通过 2禁用',
  `apply_time` int(11) DEFAULT NULL,
  `pass_time` int(11) DEFAULT NULL,
  `total_commission` decimal(10,2) NOT NULL DEFAULT '0.00',
  `frozen_commission` decimal(10,2) NOT NULL DEFAULT '0.00',
  `withdrawn_commission` decimal(10,2) NOT NULL DEFAULT '0.00',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `distributor_order`
--

CREATE TABLE `distributor_order` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_no` varchar(50) NOT NULL,
  `order_type` varchar(20) NOT NULL COMMENT 'resource/mall',
  `order_id` int(11) NOT NULL,
  `distributor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT '下单用户',
  `amount` decimal(10,2) NOT NULL COMMENT '订单金额',
  `commission_rate` decimal(5,2) NOT NULL COMMENT '佣金比例%',
  `commission` decimal(10,2) NOT NULL COMMENT '佣金金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待结算 1已结算 2已失效',
  `settle_time` int(11) DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `goods`
--

CREATE TABLE `goods` (
  `id` int(11) UNSIGNED NOT NULL,
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `points_price` int(11) NOT NULL DEFAULT '0' COMMENT '积分抵扣',
  `stock` int(11) NOT NULL DEFAULT '0',
  `sales` int(11) NOT NULL DEFAULT '0',
  `is_presell` tinyint(1) NOT NULL DEFAULT '0',
  `presell_days` int(11) NOT NULL DEFAULT '0' COMMENT '预售发货周期（天）',
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `goods_category`
--

CREATE TABLE `goods_category` (
  `id` int(11) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `goods_category`
--

INSERT INTO `goods_category` (`id`, `pid`, `name`, `icon`, `sort`, `status`, `create_time`, `update_time`) VALUES
(1, 0, 'ceshi', NULL, 0, 1, 1773684840, 1773684840);

-- --------------------------------------------------------

--
-- 表的结构 `goods_order`
--

CREATE TABLE `goods_order` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `points_pay` int(11) NOT NULL DEFAULT '0' COMMENT '积分抵扣',
  `actual_pay` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_type` varchar(20) DEFAULT NULL,
  `pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待支付 1已支付 2已取消',
  `pay_time` int(11) DEFAULT NULL,
  `shipping_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未发货 1已发货 2已收货',
  `shipping_code` varchar(100) DEFAULT NULL COMMENT '物流单号',
  `shipping_company` varchar(50) DEFAULT NULL,
  `receive_name` varchar(50) NOT NULL,
  `receive_mobile` varchar(20) NOT NULL,
  `receive_address` varchar(255) NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `refund_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '退款状态：0无退款 1申请中 2已退款',
  `refund_reason` varchar(255) DEFAULT NULL COMMENT '退款原因',
  `refund_remark` varchar(255) DEFAULT NULL COMMENT '退款备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `goods_order_detail`
--

CREATE TABLE `goods_order_detail` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `goods_id` int(11) NOT NULL,
  `goods_title` varchar(255) NOT NULL,
  `goods_cover` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `num` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `invite_record`
--

CREATE TABLE `invite_record` (
  `id` int(11) UNSIGNED NOT NULL,
  `invite_user_id` int(11) NOT NULL COMMENT '邀请人ID',
  `invited_user_id` int(11) NOT NULL COMMENT '被邀请人ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未完成 1已完成（如消费）',
  `reward_points_inviter` int(11) NOT NULL DEFAULT '0',
  `reward_points_invited` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ip_blacklist`
--

CREATE TABLE `ip_blacklist` (
  `id` int(11) UNSIGNED NOT NULL,
  `ip` varchar(50) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `create_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `material`
--

CREATE TABLE `material` (
  `id` int(11) NOT NULL COMMENT '素材ID',
  `file_name` varchar(255) NOT NULL COMMENT '文件名',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_size` int(11) NOT NULL COMMENT '文件大小（字节）',
  `file_ext` varchar(20) DEFAULT '' COMMENT '文件扩展名',
  `folder_id` int(11) DEFAULT '0' COMMENT '所属文件夹ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材库文件表';

--
-- 转存表中的数据 `material`
--

INSERT INTO `material` (`id`, `file_name`, `file_path`, `file_size`, `file_ext`, `folder_id`, `create_time`) VALUES
(3, 'ce111', '/uploads/material/20260318/869ab31fe7141448becfd58e19ea75e8.jpeg', 442310, '', 0, '2026-03-18 20:23:47'),
(5, 'tongfa.jpeg', '/uploads/material/20260319/602b2cfbb724358c9f312daa4071ec2e.jpeg', 442310, '', 0, '2026-03-19 02:01:16'),
(6, 'tongfa.jpeg', '/uploads/material/20260319/fafa10e80e3949fc6f4755909edc1fe5.jpeg', 442310, '', 1, '2026-03-19 02:04:12');

-- --------------------------------------------------------

--
-- 表的结构 `material_folder`
--

CREATE TABLE `material_folder` (
  `id` int(11) NOT NULL COMMENT '文件夹ID',
  `name` varchar(100) NOT NULL COMMENT '文件夹名称',
  `parent_id` int(11) DEFAULT '0' COMMENT '父文件夹ID（0为根目录）',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材库文件夹表';

--
-- 转存表中的数据 `material_folder`
--

INSERT INTO `material_folder` (`id`, `name`, `parent_id`, `create_time`) VALUES
(1, 'ceshi  555', 0, '2026-03-18 13:33:14'),
(10, 'ceshi1', 0, '2026-03-19 02:02:05');

-- --------------------------------------------------------

--
-- 表的结构 `message_config`
--

CREATE TABLE `message_config` (
  `id` int(11) UNSIGNED NOT NULL,
  `scene_key` varchar(50) NOT NULL COMMENT 'login_sms, member_renew, order_pay, commission_arrival, point_expire, marketing',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1开启 0关闭',
  `day_limit` int(11) NOT NULL DEFAULT '0' COMMENT '营销类每日限制',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `message_config`
--

INSERT INTO `message_config` (`id`, `scene_key`, `status`, `day_limit`, `create_time`, `update_time`) VALUES
(1, 'login_sms', 1, 0, 1773561403, 1773561403),
(2, 'member_renew', 1, 0, 1773561403, 1773561403),
(3, 'order_pay', 1, 0, 1773561403, 1773561403),
(4, 'order_ship', 1, 0, 1773561403, 1773561403),
(5, 'commission_arrival', 1, 0, 1773561403, 1773561403),
(6, 'withdraw_audit', 1, 0, 1773561403, 1773561403),
(7, 'point_expire', 1, 0, 1773561403, 1773561403),
(8, 'marketing', 1, 3, 1773561403, 1773561403);

-- --------------------------------------------------------

--
-- 表的结构 `message_log`
--

CREATE TABLE `message_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1成功 0失败',
  `send_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `message_template`
--

CREATE TABLE `message_template` (
  `id` int(11) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'verify/notice/marketing',
  `name` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `vars` varchar(255) DEFAULT NULL COMMENT '可用变量',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `message_template`
--

INSERT INTO `message_template` (`id`, `type`, `name`, `title`, `content`, `vars`, `status`, `create_time`, `update_time`) VALUES
(1, 'verify', '验证码模板', '您的验证码', '您的验证码是{code}，5分钟内有效。', 'code', 1, 1773561403, 1773561403),
(2, 'notice', '会员开通成功', '会员开通成功', '您已成功开通{level_name}，有效期至{expire_time}。', 'level_name,expire_time', 1, 1773561403, 1773561403),
(3, 'notice', '订单支付成功', '订单支付成功', '您的订单{order_no}已支付成功，金额{amount}元。', 'order_no,amount', 1, 1773561403, 1773561403),
(4, 'notice', '订单发货通知', '订单已发货', '您的订单{order_no}已发货，物流单号{shipping_code}，快递公司{shipping_company}。', 'order_no,shipping_code,shipping_company', 1, 1773561403, 1773561403),
(5, 'notice', '佣金到账通知', '佣金已到账', '您有一笔佣金{commission}元已结算到账，当前总佣金{total_commission}元。', 'commission,total_commission', 1, 1773561403, 1773561403),
(6, 'notice', '提现审核结果', '提现申请审核结果', '您的提现申请{amount}元已{status}。', 'amount,status', 1, 1773561403, 1773561403),
(7, 'notice', '积分过期提醒', '积分即将过期', '您的{points}积分将于{expire_time}过期，请及时使用。', 'points,expire_time', 1, 1773561403, 1773561403),
(8, 'marketing', '营销消息', '系统公告', '这是一条系统公告{content}', 'content', 1, 1773561403, 1773561403);

-- --------------------------------------------------------

--
-- 表的结构 `operation_log`
--

CREATE TABLE `operation_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `create_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `operation_log`
--

INSERT INTO `operation_log` (`id`, `admin_id`, `action`, `ip`, `create_time`) VALUES
(1, 1, '管理员登录', '180.233.64.143', 1773652211),
(2, 1, '管理员登录', '180.233.64.143', 1773652212),
(3, 1, '管理员登录', '180.233.64.143', 1773652213),
(4, 1, '管理员登录', '180.233.64.143', 1773652214),
(5, 1, '管理员登录', '180.233.64.143', 1773652215),
(6, 1, '管理员登录', '180.233.64.143', 1773652215),
(7, 1, '管理员登录', '180.233.64.143', 1773652216),
(8, 1, '管理员登录', '180.233.64.143', 1773652216),
(9, 1, '管理员登录', '180.233.64.143', 1773652219),
(10, 1, '管理员登录', '180.233.81.90', 1773652251),
(11, 1, '管理员登录', '180.233.81.90', 1773652252),
(12, 1, '管理员登录', '111.229.120.241', 1773652260),
(13, 1, '管理员登录', '180.233.81.90', 1773652407),
(14, 1, '管理员登录', '180.233.81.90', 1773652408),
(15, 1, '管理员登录', '180.233.81.90', 1773652409),
(16, 1, '管理员登录', '180.233.81.90', 1773652410),
(17, 1, '管理员登录', '180.233.81.90', 1773652442),
(18, 1, '管理员登录', '180.233.81.90', 1773653657),
(19, 1, '管理员登录', '180.233.81.90', 1773653661),
(20, 1, '管理员登录', '180.233.81.90', 1773653704),
(21, 1, '管理员登录', '180.233.81.90', 1773653715),
(22, 1, '管理员登录', '180.233.81.90', 1773653717),
(23, 1, '管理员登录', '180.233.81.90', 1773653717),
(24, 1, '管理员登录', '180.233.81.90', 1773653718),
(25, 1, '管理员登录', '180.233.81.90', 1773653718),
(26, 1, '管理员登录', '180.233.81.90', 1773654579),
(27, 1, '管理员登录', '180.233.81.90', 1773654581),
(28, 1, '管理员登录', '180.233.81.90', 1773654582),
(29, 1, '管理员登录', '180.233.64.143', 1773655214),
(30, 1, '管理员登录', '180.233.64.143', 1773655231),
(31, 1, '管理员登录', '180.233.64.143', 1773655232),
(32, 1, '管理员登录', '180.233.64.143', 1773655255),
(33, 1, '管理员登录', '180.233.64.143', 1773655255),
(34, 1, '管理员登录', '180.233.64.143', 1773655256),
(35, 1, '管理员登录', '180.233.64.143', 1773655256),
(36, 1, '管理员登录', '180.233.64.143', 1773656039),
(37, 1, '管理员登录', '180.233.64.143', 1773656040),
(38, 1, '管理员登录', '180.233.64.143', 1773656041),
(39, 1, '管理员登录', '180.233.64.143', 1773656041),
(40, 1, '管理员登录', '180.233.64.143', 1773656042),
(41, 1, '管理员登录', '180.233.64.143', 1773656043),
(42, 1, '管理员登录', '180.233.64.143', 1773656053),
(43, 1, '管理员登录', '180.233.64.143', 1773656054),
(44, 1, '管理员登录', '180.233.64.143', 1773656054),
(45, 1, '管理员登录', '180.233.64.143', 1773656080),
(46, 1, '管理员登录', '180.233.64.143', 1773656080),
(47, 1, '管理员登录', '180.233.64.143', 1773656081),
(48, 1, '管理员登录', '180.233.64.143', 1773656082),
(49, 1, '管理员登录', '180.233.81.90', 1773660985),
(50, 1, '管理员登录', '180.233.81.90', 1773661007),
(51, 1, '管理员登录', '180.233.81.90', 1773661009),
(52, 1, '管理员登录', '180.233.81.90', 1773661009),
(53, 1, '管理员登录', '180.233.81.90', 1773661012),
(54, 1, '管理员登录', '180.233.81.90', 1773661013),
(55, 1, '管理员登录', '180.233.81.90', 1773661014),
(56, 1, '管理员登录', '180.233.81.90', 1773661015),
(57, 1, '管理员登录', '180.233.81.90', 1773661017),
(58, 1, '管理员登录', '180.233.81.90', 1773661019),
(59, 1, '管理员登录', '180.233.81.90', 1773661020),
(60, 1, '管理员登录', '180.233.81.90', 1773661020),
(61, 1, '管理员登录', '180.233.81.90', 1773661021),
(62, 1, '管理员登录', '111.229.120.241', 1773661092),
(63, 1, '管理员登录', '180.233.64.143', 1773661603),
(64, 1, '管理员登录', '180.233.64.143', 1773661604),
(65, 1, '管理员登录', '180.233.64.143', 1773661605),
(66, 1, '管理员登录', '180.233.64.143', 1773661605),
(67, 1, '管理员登录', '180.233.64.143', 1773661606),
(68, 1, '管理员登录', '180.233.64.143', 1773661606),
(69, 1, '管理员登录', '180.233.81.90', 1773662639),
(70, 1, '管理员登录', '180.233.81.90', 1773662651),
(71, 1, '管理员登录', '180.233.81.90', 1773662853),
(72, 1, '管理员登录', '180.233.64.143', 1773664460),
(73, 1, '管理员登录', '180.233.64.143', 1773664910),
(74, 1, '管理员登录', '180.233.64.143', 1773666189),
(75, 1, '管理员登录', '120.32.156.55', 1773674432),
(76, 1, '管理员登录', '120.32.156.55', 1773674513),
(77, 1, '管理员登录', '120.32.156.55', 1773675005),
(78, 1, '管理员登录', '27.148.97.14', 1773675484),
(79, 1, '管理员登录', '27.148.97.14', 1773675694),
(80, 1, '管理员登录', '27.148.97.14', 1773675969),
(81, 1, '管理员登录', '27.148.97.14', 1773676977),
(82, 1, '管理员登录', '27.148.97.14', 1773677990),
(83, 1, '管理员登录', '27.148.97.14', 1773680824),
(84, 1, '管理员登录', '27.148.97.14', 1773681581),
(85, 1, '管理员登录', '27.148.97.14', 1773681874),
(86, 1, '管理员登录', '27.148.97.14', 1773681984),
(87, 1, '管理员登录', '27.148.97.14', 1773683058),
(88, 1, '管理员登录', '27.148.97.14', 1773684303),
(89, 1, '管理员登录', '180.233.82.18', 1773720770),
(90, 1, '管理员登录', '180.233.66.83', 1773721273),
(91, 1, '管理员登录', '180.233.66.83', 1773726772),
(92, 1, '管理员登录', '180.233.66.83', 1773729346),
(93, 1, '管理员登录', '180.233.65.117', 1773739381),
(94, 1, '管理员登录', '180.233.82.18', 1773741211),
(95, 1, '手动调整用户1会员等级为4', '180.233.82.18', 1773741461),
(96, 1, '管理员登录', '180.233.82.18', 1773743285),
(97, 1, '管理员登录', '180.233.65.117', 1773747658),
(98, 1, '管理员登录', '180.233.65.117', 1773757012),
(99, 1, '管理员登录', '180.233.65.117', 1773759231),
(100, 1, '管理员登录', '27.148.97.13', 1773766296),
(101, 1, '管理员登录', '27.148.30.136', 1773776743),
(102, 1, '管理员登录', '27.148.30.136', 1773778688),
(103, 1, '管理员登录', '27.148.30.136', 1773780732),
(104, 1, '管理员登录', '180.233.65.58', 1773824157),
(105, 1, '管理员登录', '180.233.81.142', 1773825562),
(106, 1, '管理员登录', '180.233.65.58', 1773826236),
(107, 1, '管理员登录', '180.233.81.142', 1773828704),
(108, 1, '管理员登录', '180.233.65.58', 1773829008),
(109, 1, '管理员登录', '180.233.65.58', 1773829658),
(110, 1, '管理员登录', '180.233.81.142', 1773835962),
(111, 1, '管理员登录', '180.233.65.58', 1773843527),
(112, 1, '手动调整用户1会员等级为4', '180.233.65.58', 1773847743),
(113, 1, '管理员登录', '180.233.65.58', 1773849496),
(114, 1, '管理员登录', '180.233.65.58', 1773849510),
(115, 1, '管理员登录', '180.233.65.58', 1773849531),
(116, 1, '管理员登录', '180.233.81.142', 1773849604),
(117, 1, '管理员登录', '180.233.65.58', 1773850080),
(118, 1, '管理员登录', '180.233.65.58', 1773850137),
(119, 1, '管理员登录', '180.233.65.58', 1773850284),
(120, 1, '管理员登录', '180.233.65.58', 1773850352),
(121, 1, '管理员登录', '180.233.65.58', 1773851804),
(122, 1, '管理员登录', '180.233.66.26', 1773919352),
(123, 1, '管理员登录', '180.233.64.12', 1773976586),
(124, 1, '管理员登录', '112.47.210.142', 1774006397),
(125, 1, '管理员登录', '112.47.210.142', 1774073349),
(126, 1, '管理员登录', '112.47.210.142', 1774178693);

-- --------------------------------------------------------

--
-- 表的结构 `point_log`
--

CREATE TABLE `point_log` (
  `id` int(11) NOT NULL COMMENT '日志ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` tinyint(1) NOT NULL COMMENT '变动类型：1-邀请奖励，2-消费，3-充值',
  `points` int(11) NOT NULL COMMENT '变动积分（正增负减）',
  `remark` varchar(255) DEFAULT '' COMMENT '变动备注',
  `create_time` int(11) NOT NULL COMMENT '变动时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户积分变动日志表';

-- --------------------------------------------------------

--
-- 表的结构 `point_record`
--

CREATE TABLE `point_record` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'sign/invite/consume/system',
  `points` int(11) NOT NULL COMMENT '变动积分（正增负减）',
  `balance` int(11) NOT NULL COMMENT '变动后余额',
  `remark` varchar(255) DEFAULT NULL,
  `expire_time` int(11) DEFAULT NULL COMMENT '该笔积分过期时间',
  `create_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `point_record`
--

INSERT INTO `point_record` (`id`, `user_id`, `type`, `points`, `balance`, `remark`, `expire_time`, `create_time`) VALUES
(1, 1, 'system', 1, 10001, '11', NULL, 1773742376),
(2, 1, 'system', -11, 9990, '1', NULL, 1773742393),
(3, 1, 'system', 10, 10000, '1', NULL, 1773742404),
(4, 1, 'system', 2, 10002, '管理员操作', NULL, 1773747226),
(5, 1, 'system', -2, 10000, '管理员操作', NULL, 1774178962);

-- --------------------------------------------------------

--
-- 表的结构 `resource`
--

CREATE TABLE `resource` (
  `id` int(11) UNSIGNED NOT NULL,
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL COMMENT '资源标题',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面图',
  `description` text COMMENT '详情',
  `file_path` varchar(255) NOT NULL COMMENT '文件路径（相对路径）',
  `file_name` varchar(255) NOT NULL COMMENT '原始文件名',
  `file_size` int(11) NOT NULL DEFAULT '0' COMMENT '文件大小（字节）',
  `file_ext` varchar(20) DEFAULT NULL COMMENT '文件扩展名',
  `download_count` int(11) NOT NULL DEFAULT '0',
  `view_count` int(11) NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
  `member_discount` tinyint(3) NOT NULL DEFAULT '100' COMMENT '会员折扣（100无折扣）',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1单次付费 2会员专属',
  `sort` int(11) NOT NULL DEFAULT '0',
  `is_top` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1上架 0下架',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `resource_category`
--

CREATE TABLE `resource_category` (
  `id` int(11) UNSIGNED NOT NULL,
  `pid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `resource_category`
--

INSERT INTO `resource_category` (`id`, `pid`, `name`, `icon`, `sort`, `status`, `create_time`, `update_time`) VALUES
(1, 0, '小学', NULL, 1, 1, 1773721917, 1773768898),
(2, 1, '一年级', NULL, 0, 1, 1773724877, 1773768907),
(5, 0, '中学', NULL, 0, 1, 1773771380, 1773771380),
(6, 5, '7年级', NULL, 0, 1, 1773778505, 1773778505),
(7, 6, '7年级上', NULL, 0, 1, 1773845075, 1773845075);

-- --------------------------------------------------------

--
-- 表的结构 `resource_material`
--

CREATE TABLE `resource_material` (
  `id` int(11) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_ext` varchar(20) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT '0' COMMENT '被引用次数',
  `create_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `resource_material`
--

INSERT INTO `resource_material` (`id`, `file_path`, `file_name`, `file_size`, `file_ext`, `usage_count`, `create_time`) VALUES
(1, '/uploads/resource/20260318/f9e984094d78ec4f6a999b2917086706.jpeg', 'tongfa.jpeg', 442310, 'jpeg', 0, 1773774113),
(2, '/uploads/resource/20260318/99e12a3e7e16792a99375f15e88762e9.jpeg', 'tongfa.jpeg', 442310, 'jpeg', 0, 1773774139);

-- --------------------------------------------------------

--
-- 表的结构 `resource_order`
--

CREATE TABLE `resource_order` (
  `id` int(11) NOT NULL COMMENT '订单ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `resource_id` int(11) DEFAULT NULL COMMENT '资源ID',
  `order_sn` varchar(32) NOT NULL COMMENT '订单编号',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态：0-待支付，1-已支付，2-已取消',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `pay_time` int(11) DEFAULT NULL COMMENT '支付时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资源订单表';

-- --------------------------------------------------------

--
-- 表的结构 `system_config`
--

CREATE TABLE `system_config` (
  `id` int(11) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text,
  `description` varchar(255) DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `system_config`
--

INSERT INTO `system_config` (`id`, `key`, `value`, `description`, `create_time`, `update_time`) VALUES
(1, 'site_name', 'MuTu轻副业', '网站名称', 1773561403, 1773561403),
(2, 'site_logo', '', 'LOGO地址', 1773561403, 1773561403),
(3, 'site_copyright', '© 2025 MuTu', '版权信息', 1773561403, 1773561403),
(4, 'site_icp', '', 'ICP备案号', 1773561403, 1773561403),
(5, 'kefu_mobile', '', '客服手机号', 1773561403, 1773561403),
(6, 'kefu_qrcode', '', '客服微信二维码', 1773561403, 1773561403),
(7, 'download_link_expire', '600', '下载链接有效期（秒）', 1773561403, 1773561403),
(8, 'download_limit_per_day', '10', '普通用户每日下载次数限制', 1773561403, 1773561403),
(9, 'point_expire_days', '30', '积分有效期天数', 1773561403, 1773561403),
(10, 'point_expire_notice_days', '5', '积分过期提前提醒天数', 1773561403, 1773725093),
(11, 'refund_minutes', '15', '支付成功后多少分钟内可退款（分钟）', 1773561403, 1773561403),
(12, 'auto_confirm_days', '7', '订单自动确认收货天数', 1773561403, 1773561403),
(13, 'invite_reward_enable', '1', '邀请奖励开关', 1773561403, 1773561403),
(14, 'invite_points_inviter', '100', '邀请人获得积分', 1773561403, 1773561403),
(15, 'invite_points_invited', '50', '被邀请人获得积分', 1773561403, 1773561403),
(16, 'distributor_enable', '1', '分销开关', 1773561403, 1773561403),
(17, 'distributor_apply_need_real', '1', '申请分销是否需要实名', 1773561403, 1773561403),
(18, 'commission_resource_rate', '10.00', '资源佣金比例%', 1773561403, 1773561403),
(19, 'commission_mall_rate', '5.00', '商城佣金比例%', 1773561403, 1773561403),
(20, 'withdraw_min', '10.00', '最低提现金额', 1773561403, 1773561403),
(21, 'withdraw_fee', '0.00', '提现手续费（固定金额或比例）', 1773561403, 1773561403),
(22, 'message_enable', '1', '站内信总开关', 1773561403, 1773561403),
(23, 'login_captcha', '1', '登录是否开启验证码', 1773561403, 1773561403),
(24, 'watermark_enable', '0', '资源水印开关', 1773561403, 1773561403),
(25, 'watermark_text', 'MuTu', '水印文字', 1773561403, 1773561403);

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `points` int(11) NOT NULL DEFAULT '0' COMMENT '积分',
  `level_id` int(11) NOT NULL DEFAULT '0' COMMENT '当前会员等级ID',
  `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '会员到期时间戳',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `invite_code` varchar(20) DEFAULT NULL COMMENT '我的邀请码',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '邀请人ID',
  `last_login_ip` varchar(50) DEFAULT NULL,
  `last_login_time` int(11) DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `delete_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`id`, `username`, `mobile`, `password`, `avatar`, `balance`, `points`, `level_id`, `expire_time`, `status`, `invite_code`, `pid`, `last_login_ip`, `last_login_time`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '平台官方', '17750067811', '$2y$10$xM66UUajlnYGGL2rdXQ0uebnYREPZLXjHv896lE9TjuBBTj.wyxfi', '', 10110.00, 10000, 4, 0, 1, 'zaYaZH06', 0, NULL, NULL, 1773729698, 1774233567, NULL),
(2, '官方2号', '13015858702', '$2y$10$hKqSSs3Kx0KWL1g8dFbtQ.0BU0S5JS5oOYqfl4bpR.hMqQU89hMuW', '', 2.00, 2, 4, 0, 1, 'surjT0AT', 0, NULL, NULL, 1773748001, 1773753403, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `user_download_record`
--

CREATE TABLE `user_download_record` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1成功 0失败',
  `create_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `user_level`
--

CREATE TABLE `user_level` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL COMMENT '等级名称（月卡/季卡/年卡/永久）',
  `duration_type` enum('day','month','year','forever') NOT NULL DEFAULT 'month' COMMENT '时长类型',
  `days` int(11) NOT NULL DEFAULT '0' COMMENT '有效天数（永久为0）',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
  `renew_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '续费价格',
  `download_times_per_day` int(11) NOT NULL DEFAULT '0' COMMENT '每日免费下载次数',
  `resource_discount` tinyint(3) NOT NULL DEFAULT '100' COMMENT '资源折扣（100表示无折扣）',
  `icon` varchar(255) DEFAULT NULL COMMENT '等级图标',
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `user_level`
--

INSERT INTO `user_level` (`id`, `name`, `duration_type`, `days`, `price`, `renew_price`, `download_times_per_day`, `resource_discount`, `icon`, `sort`, `status`, `create_time`, `update_time`) VALUES
(1, '月卡', 'month', 30, 29.80, 25.80, 10, 90, NULL, 1, 1, 1773561403, 1774178779),
(2, '季卡', 'month', 90, 79.80, 69.80, 15, 85, NULL, 2, 1, 1773561403, 1774178831),
(3, '年卡', 'year', 365, 299.80, 259.80, 50, 75, NULL, 3, 1, 1773561403, 1774178848),
(4, '永久会员', 'forever', 0, 599.99, 0.00, 99, 70, NULL, 4, 1, 1773561403, 1774178893);

-- --------------------------------------------------------

--
-- 表的结构 `user_member_order`
--

CREATE TABLE `user_member_order` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int(11) NOT NULL,
  `level_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `pay_type` varchar(20) DEFAULT NULL COMMENT '支付方式',
  `pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待支付 1已支付 2已取消',
  `pay_time` int(11) DEFAULT NULL,
  `expire_time` int(11) NOT NULL COMMENT '会员到期时间',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `withdraw_apply`
--

CREATE TABLE `withdraw_apply` (
  `id` int(11) UNSIGNED NOT NULL,
  `distributor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `actual_amount` decimal(10,2) NOT NULL,
  `account_type` varchar(20) NOT NULL COMMENT 'wechat/alipay',
  `account` varchar(100) NOT NULL,
  `real_name` varchar(50) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待审核 1已打款 2驳回',
  `remark` varchar(255) DEFAULT NULL,
  `audit_time` int(11) DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 表的索引 `ad_config`
--
ALTER TABLE `ad_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ad_stat`
--
ALTER TABLE `ad_stat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ad_date` (`ad_id`,`date`);

--
-- 表的索引 `balance_log`
--
ALTER TABLE `balance_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 表的索引 `complaint`
--
ALTER TABLE `complaint`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- 表的索引 `distributor`
--
ALTER TABLE `distributor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- 表的索引 `distributor_order`
--
ALTER TABLE `distributor_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `distributor_id` (`distributor_id`),
  ADD KEY `order_no` (`order_no`);

--
-- 表的索引 `goods`
--
ALTER TABLE `goods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- 表的索引 `goods_category`
--
ALTER TABLE `goods_category`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `goods_order`
--
ALTER TABLE `goods_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `goods_order_detail`
--
ALTER TABLE `goods_order_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- 表的索引 `invite_record`
--
ALTER TABLE `invite_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invite_user_id` (`invite_user_id`),
  ADD KEY `invited_user_id` (`invited_user_id`);

--
-- 表的索引 `ip_blacklist`
--
ALTER TABLE `ip_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip` (`ip`);

--
-- 表的索引 `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `material_folder`
--
ALTER TABLE `material_folder`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_name_parent` (`name`,`parent_id`) COMMENT '同一父文件夹下名称唯一';

--
-- 表的索引 `message_config`
--
ALTER TABLE `message_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scene_key` (`scene_key`);

--
-- 表的索引 `message_log`
--
ALTER TABLE `message_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `message_template`
--
ALTER TABLE `message_template`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `operation_log`
--
ALTER TABLE `operation_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `point_log`
--
ALTER TABLE `point_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- 表的索引 `point_record`
--
ALTER TABLE `point_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `resource`
--
ALTER TABLE `resource`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat_id` (`cat_id`),
  ADD KEY `idx_status_is_top` (`status`,`is_top`),
  ADD KEY `idx_cat_id_status` (`cat_id`,`status`);

--
-- 表的索引 `resource_category`
--
ALTER TABLE `resource_category`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `resource_material`
--
ALTER TABLE `resource_material`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `resource_order`
--
ALTER TABLE `resource_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_order_sn` (`order_sn`);

--
-- 表的索引 `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- 表的索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`) USING BTREE,
  ADD KEY `pid` (`pid`);

--
-- 表的索引 `user_download_record`
--
ALTER TABLE `user_download_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `idx_user_time` (`user_id`,`create_time`);

--
-- 表的索引 `user_level`
--
ALTER TABLE `user_level`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `user_member_order`
--
ALTER TABLE `user_member_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `withdraw_apply`
--
ALTER TABLE `withdraw_apply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `distributor_id` (`distributor_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `ad_config`
--
ALTER TABLE `ad_config`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ad_stat`
--
ALTER TABLE `ad_stat`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `balance_log`
--
ALTER TABLE `balance_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID', AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `complaint`
--
ALTER TABLE `complaint`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `distributor`
--
ALTER TABLE `distributor`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `distributor_order`
--
ALTER TABLE `distributor_order`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `goods`
--
ALTER TABLE `goods`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `goods_category`
--
ALTER TABLE `goods_category`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `goods_order`
--
ALTER TABLE `goods_order`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `goods_order_detail`
--
ALTER TABLE `goods_order_detail`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `invite_record`
--
ALTER TABLE `invite_record`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ip_blacklist`
--
ALTER TABLE `ip_blacklist`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `material`
--
ALTER TABLE `material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '素材ID', AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `material_folder`
--
ALTER TABLE `material_folder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文件夹ID', AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `message_config`
--
ALTER TABLE `message_config`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `message_log`
--
ALTER TABLE `message_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `message_template`
--
ALTER TABLE `message_template`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `operation_log`
--
ALTER TABLE `operation_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- 使用表AUTO_INCREMENT `point_log`
--
ALTER TABLE `point_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID';

--
-- 使用表AUTO_INCREMENT `point_record`
--
ALTER TABLE `point_record`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `resource`
--
ALTER TABLE `resource`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `resource_category`
--
ALTER TABLE `resource_category`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `resource_material`
--
ALTER TABLE `resource_material`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `resource_order`
--
ALTER TABLE `resource_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID';

--
-- 使用表AUTO_INCREMENT `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `user_download_record`
--
ALTER TABLE `user_download_record`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_level`
--
ALTER TABLE `user_level`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `user_member_order`
--
ALTER TABLE `user_member_order`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `withdraw_apply`
--
ALTER TABLE `withdraw_apply`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
