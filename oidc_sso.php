<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    throw new RuntimeException('OpenID Connect SSO dependencies are missing; run Composer install.');
}
require_once $autoload;
require_once __DIR__ . '/oidc_sso.civix.php';

/** Implements hook_civicrm_config(). */
function oidc_sso_civicrm_config(&$config): void
{
    _oidc_sso_civix_civicrm_config($config);
}

/** Implements hook_civicrm_install(). */
function oidc_sso_civicrm_install(): void
{
    _oidc_sso_civix_civicrm_install();
}

/** Implements hook_civicrm_enable(). */
function oidc_sso_civicrm_enable(): void
{
    _oidc_sso_civix_civicrm_enable();
}

/** Implements hook_civicrm_xmlMenu(). */
function oidc_sso_civicrm_xmlMenu(array &$files): void
{
    $files[] = __DIR__ . '/xml/Menu/oidc_sso.xml';
}

/** Implements hook_civicrm_buildForm(). */
function oidc_sso_civicrm_buildForm(string $formName, &$form): void
{
    if ($formName !== 'CRM_Admin_Form_Generic'
        || CRM_Utils_System::currentPath() !== 'civicrm/admin/setting/oidc_sso'
    ) {
        return;
    }
    Civi::resources()->addStyleFile('oidc_sso', 'css/settings.css');
}
