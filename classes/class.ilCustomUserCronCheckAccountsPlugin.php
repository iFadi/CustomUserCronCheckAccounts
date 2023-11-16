<?php

require_once 'class.ilCustomUserCronCheckAccounts.php';

class ilCustomUserCronCheckAccountsPlugin extends ilCronHookPlugin
{
    public const PLUGIN_CLASS_NAME = ilCustomUserCronCheckAccountsPlugin::class;
    public const PLUGIN_ID = 'custom_acc_exp_cron';
    public const PLUGIN_NAME = 'CustomUserCronCheckAccounts';

    /**
     * @var null | \ilLogger
     */
    private $logger = null;

    protected static $instance = null;

    public function __construct(\ilDBInterface $db, \ilComponentRepositoryWrite $component_repository, string $id)
    {
        global $DIC;

        $this->settings = $DIC->settings();

        parent::__construct($db, $component_repository, $id);
        self::$instance = $this;
    }

    public static function getInstance(): ?ilCustomUserCronCheckAccountsPlugin
    {
        if (self::$instance === null) {
            self::$instance = new ilCustomUserCronCheckAccountsPlugin();
        }

        return self::$instance;
    }


    public function getId(): string
    {
        return self::PLUGIN_ID;
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function getCronJobInstances(): array
    {
        global $DIC;
        $settings = new ilSetting(self::PLUGIN_ID);
        return [new ilCustomUserCronCheckAccounts($settings)];
    }

    public function getCronJobInstance($a_job_id): ilCustomUserCronCheckAccounts
    {
        global $DIC;
        $settings = new ilSetting(self::PLUGIN_ID);
        return new ilCustomUserCronCheckAccounts($settings);
    }

    protected function beforeUninstall(): bool
    {
        global $DIC;

        // Deactivate the cron job
        //$cron_manager = new ilCronManager($DIC->settings(), $DIC->logger()->root());;
        ilCronManager::deactivateJob($this->getCronJobInstance($this->getId()));


        // Manually remove cron job from the database
        $db = $DIC->database();
        $query = "DELETE FROM cron_job WHERE job_id = "
            . $db->quote($this->getId(), "text");
        $db->manipulate($query);

        ilLoggerFactory::getRootLogger()->debug('Removing custom_acc_exp_cron from cron_job table');

        // Delete settings
        $settings = new ilSetting(self::PLUGIN_ID);
        $settings->delete('mail_subject_en');
        $settings->delete('mail_body_en');
        $settings->delete('mail_subject_de');
        $settings->delete('mail_body_de');

        ilLoggerFactory::getRootLogger()->debug('Deleting the settings');

        return true;
    }

    protected function afterActivation(): void
    {
        global $DIC;

        // Activate the cron job
        //$cron_manager = new ilCronManager($DIC->settings(), $DIC->logger()->root());
        ilCronManager::activateJob($this->getCronJobInstance($this->getId()));


        // Define default settings
        $settings = new ilSetting(self::PLUGIN_ID);
        // For German
        $settings->set('mail_subject_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content", 'de'));
        $settings->set('mail_body_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content", 'de'));
        // For English
        $settings->set('mail_subject_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content", 'en'));
        $settings->set('mail_body_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content", 'en'));

        ilLoggerFactory::getRootLogger()->debug('Installing: loading plugin settings: mail_subject_de, mail_body_de, mail_subject_en, mail_body_de');

    }

//public function activate()
//{
    // Deactivate the default cronjob with job ID 'user_check_accounts'
//    $this->deactivateDefaultCronJob();
//}

    private function deactivateDefaultCronJob()
    {
        global $DIC;

        $job_id = 'user_check_accounts';

        // Get the cron manager
        $cron_manager = new ilCronManager($DIC->settings(), $DIC->logger()->root());

//    $cron_manager = $DIC->cronManager();

        // Get the cronjob object
        $cronjob = $cron_manager->getJobInstanceById($job_id);
//    $isJobActive = $cron_manager->isJobActive($job_id);

        if ($cronjob) {
            // Deactivate the cronjob
            //$cronjob->setActivation(false);
            //$cronjob->update();
            $cron_manager->deactivateJob($cronjob);
        }
    }
}
