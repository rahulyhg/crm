<?php


return array (    'defaultPermissions' =>
    array (
        'dir' => '0775',
        'file' => '0664',
        'user' => '',
        'group' => '',
    ),

    'permissionMap' => array(

        /** array('0664', '0775') */
        'writable' => array(
            'data',
            'custom',
        ),

        /** array('0644', '0755') */
        'readable' => array(
            'api',
            'application',
            'client',
            'vendor',
            'index.php',
            'cron.php',
            'rebuild.php',
            'main.html',
            'reset.html',
        ),
    ),
    'cron' => array(
        'maxJobNumber' => 15, /** Max number of jobs per one execution */
        'jobPeriod' => 7800, /** Period for jobs, ex. if cron executed at 15:35, it will execute all pending jobs for times from 14:05 to 15:35 */
        'minExecutionTime' => 50, /** to avoid too frequency execution */
        'attempts' => 3, /** attempts to run jobs */
    ),
    'crud' => array(
        'get' => 'read',
        'post' => 'create',
        'put' => 'update',
        'patch' => 'patch',
        'delete' => 'delete',
    ),
    'systemUser' => array(
        'id' => 'system',
        'userName' => 'system',
        'firstName' => '',
        'lastName' => 'System',
    ),
    'systemItems' =>
    array (
        'systemItems',
        'adminItems',
        'configPath',
        'cachePath',
        'database',
        'crud',
        'logger',
        'isInstalled',
        'defaultPermissions',
        'systemUser',
        'permissionMap',
        'permissionRules',
        'passwordSalt',
        'cryptKey',
        'restrictedMode',
        'userLimit',
        'portalUserLimit',
        'stylesheet',
        'userItems',
        'internalSmtpServer',
        'internalSmtpPort',
        'internalSmtpAuth',
        'internalSmtpUsername',
        'internalSmtpPassword',
        'internalSmtpSecurity',
        'internalOutboundEmailFromAddress'
    ),
    'adminItems' =>
    array (
        'devMode',
        'smtpServer',
        'smtpPort',
        'smtpAuth',
        'smtpSecurity',
        'smtpUsername',
        'smtpPassword',
        'cron',
        'authenticationMethod',
        'ldapHost',
        'ldapPort',
        'ldapSecurity',
        'ldapAuth',
        'ldapUsername',
        'ldapPassword',
        'ldapBindRequiresDn',
        'ldapBaseDn',
        'ldapUserLoginFilter',
        'ldapAccountCanonicalForm',
        'ldapAccountDomainName',
        'ldapAccountDomainNameShort',
        'ldapAccountFilterFormat',
        'ldapTryUsernameSplit',
        'ldapOptReferrals',
        'ldapCreateCoreUser',
        'ldapAccountDomainName',
        'ldapAccountDomainNameShort',
        'ldapUserNameAttribute',
        'ldapUserFirstNameAttribute',
        'ldapUserLastNameAttribute',
        'ldapUserTitleAttribute',
        'ldapUserEmailAddressAttribute',
        'ldapUserPhoneNumberAttribute',
        'ldapUserObjectClass',
        'maxEmailAccountCount',
        'massEmailMaxPerHourCount',
        'personalEmailMaxPortionSize',
        'inboundEmailMaxPortionSize',
        'authTokenLifetime',
        'authTokenMaxIdleTime',
        'ldapUserDefaultTeamId',
        'ldapUserDefaultTeamName',
        'ldapUserTeamsIds',
        'ldapUserTeamsNames',
        'cleanupJobPeriod',
        'cleanupActionHistoryPeriod'
    ),
    'userItems' =>
    array (
        'outboundEmailFromAddress',
        'outboundEmailFromName',
        'integrations',
        'googleMapsApiKey'
    ),
    'isInstalled' => false,
    'ldapUserNameAttribute' => 'sAMAccountName',
    'ldapUserFirstNameAttribute' => 'givenName',
    'ldapUserLastNameAttribute' => 'sn',
    'ldapUserTitleAttribute' => 'title',
    'ldapUserEmailAddressAttribute' => 'mail',
    'ldapUserPhoneNumberAttribute' => 'telephoneNumber',
    'ldapUserObjectClass' => 'person',
);

