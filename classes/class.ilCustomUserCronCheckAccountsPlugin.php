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

        // Pass the required arguments to the parent constructor.
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

    public function getLogger()
    {
        if ($this->logger === null) {
            global $DIC;

            // Make sure logger is initialized lazily
            $this->logger = $DIC->logger()->auth();
        }

        return $this->logger;
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

//        // Deactivate the cron job
//        $cron_manager = new ilCronManager($DIC->settings(), $DIC->logger()->root());
//        $cron_manager->deactivateJob($this->getCronJobInstance($this->getId()));

        // Access the cron services implementation and then the cron manager
        $cron_services = new ilCronServicesImpl($DIC);
        $cron_manager = $cron_services->manager();

        // Retrieve the cron job instance you wish to deactivate
        $cron_job_instance = $this->getCronJobInstance($this->getId());

        // Assuming you can get the current user from the global $DIC container
        $current_user = $DIC->user();

        // Now, deactivate the job with the correct arguments
        $cron_manager->deactivateJob($cron_job_instance, $current_user);


        // Manually remove cron job from the database
        $db = $DIC->database();
        $query = "DELETE FROM cron_job WHERE job_id = "
            . $db->quote($this->getId(), "text");
        $db->manipulate($query);

        $this->getLogger()->debug('Removing custom_acc_exp_cron from cron_job table');

        // Delete settings
        $settings = new ilSetting(self::PLUGIN_ID);
        $settings->delete('mail_subject_en');
        $settings->delete('mail_body_en');
        $settings->delete('mail_subject_de');
        $settings->delete('mail_body_de');

        $this->getLogger()->debug('Deleting the settings');

        return true;
    }

    protected function afterActivation(): void
    {
        // Define default settings
        $settings = new ilSetting(self::PLUGIN_ID);
        // For German
        $settings->set('mail_subject_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content", 'de'));
        $settings->set('mail_body_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content", 'de'));
        // For English
        $settings->set('mail_subject_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content", 'en'));
        $settings->set('mail_body_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content", 'en'));
    }

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
