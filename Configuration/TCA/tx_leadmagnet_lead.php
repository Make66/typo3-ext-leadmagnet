<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:tx_leadmagnet_lead',
        'label' => 'email',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'iconfile' => 'EXT:leadmagnet/Resources/Public/Icons/Extension.svg',
        'searchFields' => 'email,token',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'email,token,content_element,downloaded',
        ],
    ],
    'columns' => [
        'email' => [
            'label' => 'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:tx_leadmagnet_lead.email',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'token' => [
            'label' => 'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:tx_leadmagnet_lead.token',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'readOnly' => true,
            ],
        ],
        'content_element' => [
            'label' => 'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:tx_leadmagnet_lead.content_element',
            'config' => [
                'type' => 'number',
            ],
        ],
        'downloaded' => [
            'label' => 'LLL:EXT:leadmagnet/Resources/Private/Language/locallang.xlf:tx_leadmagnet_lead.downloaded',
            'config' => [
                'type' => 'check',
                'readOnly' => true,
            ],
        ],
    ],
];
