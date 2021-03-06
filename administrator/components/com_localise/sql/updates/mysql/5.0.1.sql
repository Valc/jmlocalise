CREATE TABLE IF NOT EXISTS `#__localise_known_core_files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__localise_known_deleted_keys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reflang` varchar(6) NOT NULL,
  `key` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__localise_known_renamed_keys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `client` varchar(255) NOT NULL,
  `reflang` varchar(6) NOT NULL,
  `key` varchar(100) NOT NULL,
  `replacement_key` varchar(100) NOT NULL,
  `reflang_string` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__localise_false_positives` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `client` varchar(255) NOT NULL,
  `reflang` varchar(6) NOT NULL,
  `targetlang` varchar(6) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `is_false_positive` tinyint(1) NOT NULL DEFAULT 0,
  `key` varchar(255) NOT NULL,
  `reflang_string` longtext NOT NULL,
  `targetlang_string` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#__localise_known_core_files`
--

INSERT INTO `#__localise_known_core_files` (`id`, `filename`) VALUES
(1, 'com_actionlogs.ini'),
(2, 'com_actionlogs.sys.ini'),
(3, 'com_admin.ini'),
(4, 'com_admin.sys.ini'),
(5, 'com_ajax.ini'),
(6, 'com_ajax.sys.ini'),
(7, 'com_associations.ini'),
(8, 'com_associations.sys.ini'),
(9, 'com_banners.ini'),
(10, 'com_banners.sys.ini'),
(11, 'com_cache.ini'),
(12, 'com_cache.sys.ini'),
(13, 'com_categories.ini'),
(14, 'com_categories.sys.ini'),
(15, 'com_checkin.ini'),
(16, 'com_checkin.sys.ini'),
(17, 'com_config.ini'),
(18, 'com_config.sys.ini'),
(19, 'com_contact.ini'),
(20, 'com_contact.sys.ini'),
(21, 'com_content.ini'),
(22, 'com_content.sys.ini'),
(23, 'com_contenthistory.ini'),
(24, 'com_contenthistory.sys.ini'),
(25, 'com_cpanel.ini'),
(26, 'com_cpanel.sys.ini'),
(27, 'com_fields.ini'),
(28, 'com_fields.sys.ini'),
(29, 'com_finder.ini'),
(30, 'com_finder.sys.ini'),
(31, 'com_installer.ini'),
(32, 'com_installer.sys.ini'),
(33, 'com_joomlaupdate.ini'),
(34, 'com_joomlaupdate.sys.ini'),
(35, 'com_languages.ini'),
(36, 'com_languages.sys.ini'),
(37, 'com_login.ini'),
(38, 'com_login.sys.ini'),
(306, 'com_mails.ini'),
(307, 'com_mails.sys.ini'),
(402, 'com_mailto.ini'),
(39, 'com_mailto.sys.ini'),
(40, 'com_media.ini'),
(41, 'com_media.sys.ini'),
(42, 'com_menus.ini'),
(43, 'com_menus.sys.ini'),
(44, 'com_messages.ini'),
(45, 'com_messages.sys.ini'),
(46, 'com_modules.ini'),
(47, 'com_modules.sys.ini'),
(48, 'com_newsfeeds.ini'),
(49, 'com_newsfeeds.sys.ini'),
(50, 'com_plugins.ini'),
(51, 'com_plugins.sys.ini'),
(52, 'com_postinstall.ini'),
(53, 'com_postinstall.sys.ini'),
(54, 'com_privacy.ini'),
(55, 'com_privacy.sys.ini'),
(56, 'com_redirect.ini'),
(57, 'com_redirect.sys.ini'),
(462, 'com_scheduler.ini'),
(463, 'com_scheduler.sys.ini'),
(58, 'com_search.ini'),
(59, 'com_search.sys.ini'),
(60, 'com_tags.ini'),
(61, 'com_tags.sys.ini'),
(62, 'com_templates.ini'),
(63, 'com_templates.sys.ini'),
(64, 'com_users.ini'),
(65, 'com_users.sys.ini'),
(66, 'com_weblinks.ini'),
(67, 'com_weblinks.sys.ini'),
(308, 'com_workflow.ini'),
(309, 'com_workflow.sys.ini'),
(68, 'com_wrapper.ini'),
(69, 'com_wrapper.sys.ini'),
(403, 'files_joomla.sys.ini'),
(404, 'finder_cli.ini'),
(70, 'joomla.ini'),
(405, 'lib_fof.ini'),
(406, 'lib_fof.sys.ini'),
(407, 'lib_idna_convert.sys.ini'),
(71, 'lib_joomla.ini'),
(408, 'lib_joomla.sys.ini'),
(409, 'lib_phpass.sys.ini'),
(410, 'lib_phputf8.sys.ini'),
(411, 'lib_simplepie.sys.ini'),
(412, 'mod_articles_archive.ini'),
(413, 'mod_articles_archive.sys.ini'),
(414, 'mod_articles_categories.ini'),
(415, 'mod_articles_categories.sys.ini'),
(416, 'mod_articles_category.ini'),
(417, 'mod_articles_category.sys.ini'),
(418, 'mod_articles_latest.ini'),
(419, 'mod_articles_latest.sys.ini'),
(420, 'mod_articles_news.ini'),
(421, 'mod_articles_news.sys.ini'),
(422, 'mod_articles_popular.ini'),
(423, 'mod_articles_popular.sys.ini'),
(424, 'mod_banners.ini'),
(425, 'mod_banners.sys.ini'),
(426, 'mod_breadcrumbs.ini'),
(427, 'mod_breadcrumbs.sys.ini'),
(72, 'mod_custom.ini'),
(73, 'mod_custom.sys.ini'),
(74, 'mod_feed.ini'),
(75, 'mod_feed.sys.ini'),
(428, 'mod_finder.ini'),
(429, 'mod_finder.sys.ini'),
(430, 'mod_footer.ini'),
(431, 'mod_footer.sys.ini'),
(310, 'mod_frontend.ini'),
(311, 'mod_frontend.sys.ini'),
(432, 'mod_languages.ini'),
(433, 'mod_languages.sys.ini'),
(76, 'mod_latest.ini'),
(77, 'mod_latest.sys.ini'),
(78, 'mod_latestactions.ini'),
(79, 'mod_latestactions.sys.ini'),
(80, 'mod_logged.ini'),
(81, 'mod_logged.sys.ini'),
(82, 'mod_login.ini'),
(83, 'mod_login.sys.ini'),
(312, 'mod_loginsupport.ini'),
(313, 'mod_loginsupport.sys.ini'),
(84, 'mod_menu.ini'),
(85, 'mod_menu.sys.ini'),
(314, 'mod_messages.ini'),
(315, 'mod_messages.sys.ini'),
(86, 'mod_multilangstatus.ini'),
(87, 'mod_multilangstatus.sys.ini'),
(88, 'mod_popular.ini'),
(89, 'mod_popular.sys.ini'),
(316, 'mod_post_installation_messages.ini'),
(317, 'mod_post_installation_messages.sys.ini'),
(90, 'mod_privacy_dashboard.ini'),
(91, 'mod_privacy_dashboard.sys.ini'),
(318, 'mod_privacy_status.ini'),
(319, 'mod_privacy_status.sys.ini'),
(92, 'mod_quickicon.ini'),
(93, 'mod_quickicon.sys.ini'),
(434, 'mod_random_image.ini'),
(435, 'mod_random_image.sys.ini'),
(436, 'mod_related_items.ini'),
(437, 'mod_related_items.sys.ini'),
(94, 'mod_sampledata.ini'),
(95, 'mod_sampledata.sys.ini'),
(438, 'mod_search.ini'),
(439, 'mod_search.sys.ini'),
(96, 'mod_stats_admin.ini'),
(97, 'mod_stats_admin.sys.ini'),
(440, 'mod_stats.ini'),
(441, 'mod_stats.sys.ini'),
(98, 'mod_status.ini'),
(99, 'mod_status.sys.ini'),
(100, 'mod_submenu.ini'),
(101, 'mod_submenu.sys.ini'),
(442, 'mod_syndicate.ini'),
(443, 'mod_syndicate.sys.ini'),
(444, 'mod_tags_popular.ini'),
(445, 'mod_tags_popular.sys.ini'),
(446, 'mod_tags_similar.ini'),
(447, 'mod_tags_similar.sys.ini'),
(102, 'mod_title.ini'),
(103, 'mod_title.sys.ini'),
(104, 'mod_toolbar.ini'),
(105, 'mod_toolbar.sys.ini'),
(320, 'mod_user.ini'),
(321, 'mod_user.sys.ini'),
(448, 'mod_users_latest.ini'),
(449, 'mod_users_latest.sys.ini'),
(106, 'mod_version.ini'),
(107, 'mod_version.sys.ini'),
(450, 'mod_weblinks.ini'),
(451, 'mod_weblinks.sys.ini'),
(452, 'mod_whosonline.ini'),
(453, 'mod_whosonline.sys.ini'),
(454, 'mod_wrapper.ini'),
(455, 'mod_wrapper.sys.ini'),
(108, 'plg_actionlog_joomla.ini'),
(109, 'plg_actionlog_joomla.sys.ini'),
(322, 'plg_api-authentication_basic.ini'),
(323, 'plg_api-authentication_basic.sys.ini'),
(324, 'plg_api-authentication_token.ini'),
(325, 'plg_api-authentication_token.sys.ini'),
(110, 'plg_authentication_cookie.ini'),
(111, 'plg_authentication_cookie.sys.ini'),
(112, 'plg_authentication_gmail.ini'),
(113, 'plg_authentication_gmail.sys.ini'),
(114, 'plg_authentication_joomla.ini'),
(115, 'plg_authentication_joomla.sys.ini'),
(116, 'plg_authentication_ldap.ini'),
(117, 'plg_authentication_ldap.sys.ini'),
(326, 'plg_behaviour_taggable.ini'),
(327, 'plg_behaviour_taggable.sys.ini'),
(328, 'plg_behaviour_versionable.ini'),
(329, 'plg_behaviour_versionable.sys.ini'),
(120, 'plg_captcha_recaptcha_invisible.ini'),
(121, 'plg_captcha_recaptcha_invisible.sys.ini'),
(118, 'plg_captcha_recaptcha.ini'),
(119, 'plg_captcha_recaptcha.sys.ini'),
(122, 'plg_content_confirmconsent.ini'),
(123, 'plg_content_confirmconsent.sys.ini'),
(124, 'plg_content_contact.ini'),
(125, 'plg_content_contact.sys.ini'),
(126, 'plg_content_emailcloak.ini'),
(127, 'plg_content_emailcloak.sys.ini'),
(128, 'plg_content_fields.ini'),
(129, 'plg_content_fields.sys.ini'),
(130, 'plg_content_finder.ini'),
(131, 'plg_content_finder.sys.ini'),
(132, 'plg_content_joomla.ini'),
(133, 'plg_content_joomla.sys.ini'),
(134, 'plg_content_loadmodule.ini'),
(135, 'plg_content_loadmodule.sys.ini'),
(136, 'plg_content_pagebreak.ini'),
(137, 'plg_content_pagebreak.sys.ini'),
(138, 'plg_content_pagenavigation.ini'),
(139, 'plg_content_pagenavigation.sys.ini'),
(140, 'plg_content_vote.ini'),
(141, 'plg_content_vote.sys.ini'),
(158, 'plg_editors_codemirror.ini'),
(159, 'plg_editors_codemirror.sys.ini'),
(160, 'plg_editors_none.ini'),
(161, 'plg_editors_none.sys.ini'),
(162, 'plg_editors_tinymce.ini'),
(163, 'plg_editors_tinymce.sys.ini'),
(142, 'plg_editors-xtd_article.ini'),
(143, 'plg_editors-xtd_article.sys.ini'),
(144, 'plg_editors-xtd_contact.ini'),
(145, 'plg_editors-xtd_contact.sys.ini'),
(146, 'plg_editors-xtd_fields.ini'),
(147, 'plg_editors-xtd_fields.sys.ini'),
(148, 'plg_editors-xtd_image.ini'),
(149, 'plg_editors-xtd_image.sys.ini'),
(150, 'plg_editors-xtd_menu.ini'),
(151, 'plg_editors-xtd_menu.sys.ini'),
(152, 'plg_editors-xtd_module.ini'),
(153, 'plg_editors-xtd_module.sys.ini'),
(154, 'plg_editors-xtd_pagebreak.ini'),
(155, 'plg_editors-xtd_pagebreak.sys.ini'),
(156, 'plg_editors-xtd_readmore.ini'),
(157, 'plg_editors-xtd_readmore.sys.ini'),
(480, 'plg_editors-xtd_weblink.ini'),
(481, 'plg_editors-xtd_weblink.sys.ini'),
(330, 'plg_extension_finder.ini'),
(331, 'plg_extension_finder.sys.ini'),
(164, 'plg_extension_joomla.ini'),
(165, 'plg_extension_joomla.sys.ini'),
(332, 'plg_extension_namespacemap.ini'),
(333, 'plg_extension_namespacemap.sys.ini'),
(166, 'plg_fields_calendar.ini'),
(167, 'plg_fields_calendar.sys.ini'),
(168, 'plg_fields_checkboxes.ini'),
(169, 'plg_fields_checkboxes.sys.ini'),
(170, 'plg_fields_color.ini'),
(171, 'plg_fields_color.sys.ini'),
(172, 'plg_fields_editor.ini'),
(173, 'plg_fields_editor.sys.ini'),
(174, 'plg_fields_image.ini'),
(175, 'plg_fields_image.sys.ini'),
(176, 'plg_fields_imagelist.ini'),
(177, 'plg_fields_imagelist.sys.ini'),
(178, 'plg_fields_integer.ini'),
(179, 'plg_fields_integer.sys.ini'),
(180, 'plg_fields_list.ini'),
(181, 'plg_fields_list.sys.ini'),
(182, 'plg_fields_media.ini'),
(183, 'plg_fields_media.sys.ini'),
(184, 'plg_fields_radio.ini'),
(185, 'plg_fields_radio.sys.ini'),
(186, 'plg_fields_repeatable.ini'),
(187, 'plg_fields_repeatable.sys.ini'),
(188, 'plg_fields_sql.ini'),
(189, 'plg_fields_sql.sys.ini'),
(334, 'plg_fields_subform.ini'),
(335, 'plg_fields_subform.sys.ini'),
(190, 'plg_fields_text.ini'),
(191, 'plg_fields_text.sys.ini'),
(192, 'plg_fields_textarea.ini'),
(193, 'plg_fields_textarea.sys.ini'),
(194, 'plg_fields_url.ini'),
(195, 'plg_fields_url.sys.ini'),
(196, 'plg_fields_user.ini'),
(197, 'plg_fields_user.sys.ini'),
(198, 'plg_fields_usergrouplist.ini'),
(199, 'plg_fields_usergrouplist.sys.ini'),
(336, 'plg_filesystem_local.ini'),
(337, 'plg_filesystem_local.sys.ini'),
(200, 'plg_finder_categories.ini'),
(201, 'plg_finder_categories.sys.ini'),
(202, 'plg_finder_contacts.ini'),
(203, 'plg_finder_contacts.sys.ini'),
(204, 'plg_finder_content.ini'),
(205, 'plg_finder_content.sys.ini'),
(206, 'plg_finder_newsfeeds.ini'),
(207, 'plg_finder_newsfeeds.sys.ini'),
(208, 'plg_finder_tags.ini'),
(209, 'plg_finder_tags.sys.ini'),
(210, 'plg_finder_weblinks.ini'),
(211, 'plg_finder_weblinks.sys.ini'),
(212, 'plg_installer_folderinstaller.ini'),
(213, 'plg_installer_folderinstaller.sys.ini'),
(338, 'plg_installer_override.ini'),
(339, 'plg_installer_override.sys.ini'),
(214, 'plg_installer_packageinstaller.ini'),
(215, 'plg_installer_packageinstaller.sys.ini'),
(216, 'plg_installer_urlinstaller.ini'),
(217, 'plg_installer_urlinstaller.sys.ini'),
(218, 'plg_installer_webinstaller.ini'),
(219, 'plg_installer_webinstaller.sys.ini'),
(340, 'plg_media-action_crop.ini'),
(341, 'plg_media-action_crop.sys.ini'),
(342, 'plg_media-action_resize.ini'),
(343, 'plg_media-action_resize.sys.ini'),
(344, 'plg_media-action_rotate.ini'),
(345, 'plg_media-action_rotate.sys.ini'),
(486, 'plg_multifactorauth_email.ini'),
(487, 'plg_multifactorauth_email.sys.ini'),
(488, 'plg_multifactorauth_fixed.ini'),
(489, 'plg_multifactorauth_fixed.sys.ini'),
(490, 'plg_multifactorauth_totp.ini'),
(491, 'plg_multifactorauth_totp.sys.ini'),
(492, 'plg_multifactorauth_webauthn.ini'),
(493, 'plg_multifactorauth_webauthn.sys.ini'),
(494, 'plg_multifactorauth_yubikey.ini'),
(495, 'plg_multifactorauth_yubikey.sys.ini'),
(220, 'plg_privacy_actionlogs.ini'),
(221, 'plg_privacy_actionlogs.sys.ini'),
(222, 'plg_privacy_consents.ini'),
(223, 'plg_privacy_consents.sys.ini'),
(224, 'plg_privacy_contact.ini'),
(225, 'plg_privacy_contact.sys.ini'),
(226, 'plg_privacy_content.ini'),
(227, 'plg_privacy_content.sys.ini'),
(228, 'plg_privacy_message.ini'),
(229, 'plg_privacy_message.sys.ini'),
(230, 'plg_privacy_user.ini'),
(231, 'plg_privacy_user.sys.ini'),
(346, 'plg_quickicon_downloadkey.ini'),
(347, 'plg_quickicon_downloadkey.sys.ini'),
(482, 'plg_quickicon_eos310.ini'),
(483, 'plg_quickicon_eos310.sys.ini'),
(232, 'plg_quickicon_extensionupdate.ini'),
(233, 'plg_quickicon_extensionupdate.sys.ini'),
(234, 'plg_quickicon_joomlaupdate.ini'),
(235, 'plg_quickicon_joomlaupdate.sys.ini'),
(348, 'plg_quickicon_overridecheck.ini'),
(349, 'plg_quickicon_overridecheck.sys.ini'),
(236, 'plg_quickicon_phpversioncheck.ini'),
(237, 'plg_quickicon_phpversioncheck.sys.ini'),
(238, 'plg_quickicon_privacycheck.ini'),
(239, 'plg_quickicon_privacycheck.sys.ini'),
(240, 'plg_sampledata_blog.ini'),
(241, 'plg_sampledata_blog.sys.ini'),
(350, 'plg_sampledata_multilang.ini'),
(351, 'plg_sampledata_multilang.sys.ini'),
(242, 'plg_search_categories.ini'),
(243, 'plg_search_categories.sys.ini'),
(244, 'plg_search_contacts.ini'),
(245, 'plg_search_contacts.sys.ini'),
(246, 'plg_search_content.ini'),
(247, 'plg_search_content.sys.ini'),
(248, 'plg_search_newsfeeds.ini'),
(249, 'plg_search_newsfeeds.sys.ini'),
(250, 'plg_search_tags.ini'),
(251, 'plg_search_tags.sys.ini'),
(252, 'plg_search_weblinks.ini'),
(253, 'plg_search_weblinks.sys.ini'),
(352, 'plg_system_accessibility.ini'),
(353, 'plg_system_accessibility.sys.ini'),
(254, 'plg_system_actionlogs.ini'),
(255, 'plg_system_actionlogs.sys.ini'),
(256, 'plg_system_cache.ini'),
(257, 'plg_system_cache.sys.ini'),
(258, 'plg_system_debug.ini'),
(259, 'plg_system_debug.sys.ini'),
(260, 'plg_system_fields.ini'),
(261, 'plg_system_fields.sys.ini'),
(262, 'plg_system_highlight.ini'),
(263, 'plg_system_highlight.sys.ini'),
(354, 'plg_system_httpheaders.ini'),
(355, 'plg_system_httpheaders.sys.ini'),
(464, 'plg_system_jooa11y.ini'),
(465, 'plg_system_jooa11y.sys.ini'),
(264, 'plg_system_languagecode.ini'),
(265, 'plg_system_languagecode.sys.ini'),
(266, 'plg_system_languagefilter.ini'),
(267, 'plg_system_languagefilter.sys.ini'),
(268, 'plg_system_log.ini'),
(269, 'plg_system_log.sys.ini'),
(270, 'plg_system_logout.ini'),
(271, 'plg_system_logout.sys.ini'),
(272, 'plg_system_logrotation.ini'),
(273, 'plg_system_logrotation.sys.ini'),
(274, 'plg_system_p3p.ini'),
(275, 'plg_system_p3p.sys.ini'),
(276, 'plg_system_privacyconsent.ini'),
(277, 'plg_system_privacyconsent.sys.ini'),
(278, 'plg_system_redirect.ini'),
(279, 'plg_system_redirect.sys.ini'),
(280, 'plg_system_remember.ini'),
(281, 'plg_system_remember.sys.ini'),
(466, 'plg_system_schedulerunner.ini'),
(467, 'plg_system_schedulerunner.sys.ini'),
(282, 'plg_system_sef.ini'),
(283, 'plg_system_sef.sys.ini'),
(284, 'plg_system_sessiongc.ini'),
(285, 'plg_system_sessiongc.sys.ini'),
(496, 'plg_system_shortcut.ini'),
(497, 'plg_system_shortcut.sys.ini'),
(356, 'plg_system_skipto.ini'),
(357, 'plg_system_skipto.sys.ini'),
(286, 'plg_system_stats.ini'),
(287, 'plg_system_stats.sys.ini'),
(468, 'plg_system_tasknotification.ini'),
(469, 'plg_system_tasknotification.sys.ini'),
(288, 'plg_system_updatenotification.ini'),
(289, 'plg_system_updatenotification.sys.ini'),
(358, 'plg_system_webauthn.ini'),
(359, 'plg_system_webauthn.sys.ini'),
(484, 'plg_system_weblinks.ini'),
(485, 'plg_system_weblinks.sys.ini'),
(470, 'plg_task_checkfiles.ini'),
(471, 'plg_task_checkfiles.sys.ini'),
(472, 'plg_task_demotasks.ini'),
(473, 'plg_task_demotasks.sys.ini'),
(474, 'plg_task_requests.ini'),
(475, 'plg_task_requests.sys.ini'),
(476, 'plg_task_sitestatus.ini'),
(477, 'plg_task_sitestatus.sys.ini'),
(290, 'plg_twofactorauth_totp.ini'),
(291, 'plg_twofactorauth_totp.sys.ini'),
(292, 'plg_twofactorauth_yubikey.ini'),
(293, 'plg_twofactorauth_yubikey.sys.ini'),
(294, 'plg_user_contactcreator.ini'),
(295, 'plg_user_contactcreator.sys.ini'),
(296, 'plg_user_joomla.ini'),
(297, 'plg_user_joomla.sys.ini'),
(298, 'plg_user_profile.ini'),
(299, 'plg_user_profile.sys.ini'),
(300, 'plg_user_terms.ini'),
(301, 'plg_user_terms.sys.ini'),
(360, 'plg_user_token.ini'),
(361, 'plg_user_token.sys.ini'),
(362, 'plg_webservices_banners.ini'),
(363, 'plg_webservices_banners.sys.ini'),
(364, 'plg_webservices_config.ini'),
(365, 'plg_webservices_config.sys.ini'),
(366, 'plg_webservices_contact.ini'),
(367, 'plg_webservices_contact.sys.ini'),
(368, 'plg_webservices_content.ini'),
(369, 'plg_webservices_content.sys.ini'),
(370, 'plg_webservices_installer.ini'),
(371, 'plg_webservices_installer.sys.ini'),
(372, 'plg_webservices_languages.ini'),
(373, 'plg_webservices_languages.sys.ini'),
(478, 'plg_webservices_media.ini'),
(479, 'plg_webservices_media.sys.ini'),
(374, 'plg_webservices_menus.ini'),
(375, 'plg_webservices_menus.sys.ini'),
(376, 'plg_webservices_messages.ini'),
(377, 'plg_webservices_messages.sys.ini'),
(378, 'plg_webservices_modules.ini'),
(379, 'plg_webservices_modules.sys.ini'),
(380, 'plg_webservices_newsfeeds.ini'),
(381, 'plg_webservices_newsfeeds.sys.ini'),
(382, 'plg_webservices_plugins.ini'),
(383, 'plg_webservices_plugins.sys.ini'),
(384, 'plg_webservices_privacy.ini'),
(385, 'plg_webservices_privacy.sys.ini'),
(386, 'plg_webservices_redirect.ini'),
(387, 'plg_webservices_redirect.sys.ini'),
(388, 'plg_webservices_tags.ini'),
(389, 'plg_webservices_tags.sys.ini'),
(390, 'plg_webservices_templates.ini'),
(391, 'plg_webservices_templates.sys.ini'),
(392, 'plg_webservices_users.ini'),
(393, 'plg_webservices_users.sys.ini'),
(394, 'plg_workflow_featuring.ini'),
(395, 'plg_workflow_featuring.sys.ini'),
(396, 'plg_workflow_notification.ini'),
(397, 'plg_workflow_notification.sys.ini'),
(398, 'plg_workflow_publishing.ini'),
(399, 'plg_workflow_publishing.sys.ini'),
(400, 'tpl_atum.ini'),
(401, 'tpl_atum.sys.ini'),
(456, 'tpl_beez3.ini'),
(457, 'tpl_beez3.sys.ini'),
(460, 'tpl_cassiopeia.ini'),
(461, 'tpl_cassiopeia.sys.ini'),
(302, 'tpl_hathor.ini'),
(303, 'tpl_hathor.sys.ini'),
(304, 'tpl_isis.ini'),
(305, 'tpl_isis.sys.ini'),
(458, 'tpl_protostar.ini'),
(459, 'tpl_protostar.sys.ini');
