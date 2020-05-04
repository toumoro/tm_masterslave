<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tm_masterslave', 'Configuration/TypoScript', 'tm_masterslave');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_tmmasterslave_domain_model_dummy', 'EXT:tm_masterslave/Resources/Private/Language/locallang_csh_tx_tmmasterslave_domain_model_dummy.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_tmmasterslave_domain_model_dummy');

    }
);
