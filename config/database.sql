-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the TYPOlight *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

--
-- Table `tl_form_field`
--

CREATE TABLE `tl_form_field` (
  `mp_forms_afterSubmit` text NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

----------------------------------


--
-- Table `tl_form`
--

CREATE TABLE `tl_form` (
  `mp_forms_getParam` varchar(255) NOT NULL default 'step'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;