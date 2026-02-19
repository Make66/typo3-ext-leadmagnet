<?php

declare(strict_types=1);

defined('TYPO3') or die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

// Register the plugin as content element
$pluginKey = ExtensionUtility::registerPlugin(
    'Leadmagnet',
    'Show',
    'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:plugin.title',
    'leadmagnet-show',
    'taketool',
    'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:plugin.description',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,--div--;Configuration,pi_flexform,',
    $pluginKey,
    'after:subheader'
);

$GLOBALS['TCA']['tt_content']['types'][$pluginKey]['columnsOverrides']['bodytext']['config']['enableRichtext'] = true;
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:leadmagnet/Configuration/FlexForms/Show.xml',
    'leadmagnet_show',
);
