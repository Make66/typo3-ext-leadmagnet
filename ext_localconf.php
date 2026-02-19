<?php

declare(strict_types=1);

defined('TYPO3') or die('Access denied.');

use Taketool\Leadmagnet\Controller\LeadmagnetController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'Leadmagnet',
    'Show',
    [LeadmagnetController::class => 'show,submit,download'],
    [LeadmagnetController::class => 'show,submit,download'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][419] = 'EXT:leadmagnet/Resources/Private/Layouts';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][419] = 'EXT:leadmagnet/Resources/Private/Templates/Mail';
