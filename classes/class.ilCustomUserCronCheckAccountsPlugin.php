<?php

declare(strict_types=1);

/**
 * CustomUserCronCheckAccounts – ILIAS 10 plugin entry point.
 *
 * Extends the core "Check user accounts" cron job (id: user_check_accounts)
 * with freely configurable, per-language notification mails that support the
 * placeholders {FIRSTNAME} {LASTNAME} {USERNAME} {EMAIL} {EXPIRES}. The core
 * job can only send a fixed, language-variable based text without the actual
 * expiry date, which is why this plugin is still required under ILIAS 10.
 *
 * @author Fadi Asbih <asbih@elsa.uni-hannover.de>
 */
class ilCustomUserCronCheckAccountsPlugin extends ilCronHookPlugin
{
    public const PLUGIN_ID = 'custom_acc_exp_cron';
    public const PLUGIN_NAME = 'CustomUserCronCheckAccounts';

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @return ilCronJob[]
     */
    public function getCronJobInstances(): array
    {
        return [$this->getCronJobInstance(self::PLUGIN_ID)];
    }

    public function getCronJobInstance(string $jobId): ilCronJob
    {
        // The job receives the plugin so it can resolve language strings via
        // $this->plugin->txt() instead of a fragile static singleton.
        return new ilCustomUserCronCheckAccounts($this);
    }

    /**
     * Seed the per-language default mail texts the first time the plugin is
     * activated.
     *
     * NB: ilPlugin::txt() in ILIAS 10 takes no language argument – it always
     * returns the current UI language. Seeding the German defaults via txt()
     * (as the ILIAS 9 version did) therefore leaked the admin's UI language
     * into the German field. We seed from explicit, language-keyed constants
     * instead, so "de" is always German and "en" is always English.
     * Existing (already configured) values are never overwritten.
     */
    protected function afterActivation(): void
    {
        $settings = new ilSetting(self::PLUGIN_ID);

        foreach (ilCustomUserCronCheckAccounts::SUPPORTED_LANGUAGES as $lang) {
            if ((string) $settings->get('mail_subject_' . $lang, '') === '') {
                $settings->set('mail_subject_' . $lang, ilCustomUserCronCheckAccounts::DEFAULT_SUBJECT[$lang]);
            }
            if ((string) $settings->get('mail_body_' . $lang, '') === '') {
                $settings->set('mail_body_' . $lang, ilCustomUserCronCheckAccounts::DEFAULT_BODY[$lang]);
            }
        }
    }

    /**
     * Remove the plugin's settings on uninstall. The cron job row itself is
     * cleaned up by the ILIAS cron/plugin machinery.
     */
    protected function beforeUninstall(): bool
    {
        $settings = new ilSetting(self::PLUGIN_ID);

        foreach (ilCustomUserCronCheckAccounts::SUPPORTED_LANGUAGES as $lang) {
            $settings->delete('mail_subject_' . $lang);
            $settings->delete('mail_body_' . $lang);
        }

        return true;
    }
}
