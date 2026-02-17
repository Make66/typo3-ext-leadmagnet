<?php

declare(strict_types=1);

defined('TYPO3') or die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

// Register the plugin as content element
ExtensionUtility::registerPlugin(
    'Leadmagnet',
    'Show',
    'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:plugin.title',
    'leadmagnet-show',
    'plugins',
    'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:plugin.description',
);

// Add FlexForm
$GLOBALS['TCA']['tt_content']['types']['leadmagnet_show']['showitem'] = '
    --div--;LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.type,
        CType,
    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.general,
        header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
        bodytext;LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:bodytext,
        pi_flexform,
    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
        --palette--;;frames,
    --div--;LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.access,
        --palette--;;hidden,
        --palette--;;access,
';

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:leadmagnet/Configuration/FlexForms/Show.xml',
    'leadmagnet_show',
);

// Enable richtext for bodytext in this CType
$GLOBALS['TCA']['tt_content']['types']['leadmagnet_show']['columnsOverrides'] = [
    'bodytext' => [
        'config' => [
            'enableRichtext' => true,
        ],
    ],
];
