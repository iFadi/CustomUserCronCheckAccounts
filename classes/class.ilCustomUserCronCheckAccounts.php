<?php

use ILIAS\Cron\Schedule\CronJobScheduleType;

require_once 'class.ilCustomUserCronCheckAccounts.php';
require_once './Services/Cron/classes/class.ilCronJob.php';
require_once './Services/Logging/classes/class.ilLog.php';


class ilCustomUserCronCheckAccounts extends ilCronJob
{
    public const CRON_NAME = 'custom_check_user_accounts';
    public const PLUGIN_ID = 'custom_acc_exp_cron';
    protected $settings;

    public function __construct(ilSetting $settings)
    {
        $this->settings = $settings;
    }

    public function getId(): string
    {
        return self::PLUGIN_ID;
    }

    public function getTitle(): string
    {
        return ilCustomUserCronCheckAccountsPlugin::getInstance()->txt(self::CRON_NAME);
    }

    public function getDescription(): string
    {
        return ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("cron_description");
    }

    public function run(): ilCronJobResult
    {
        $customUserCronCheckAccounts = new CustomUserCronCheckAccounts($this->settings);
        return $customUserCronCheckAccounts->run();
    }

    public function hasAutoActivation(): bool
    {
        return false;
    }

    public function hasFlexibleSchedule(): bool
    {
        return false;
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue(): int
    {
        return 1;
    }

    public function hasCustomSettings(): bool
    {
        return true;
    }

    /**
     * Add custom settings to form
     *
     * @param ilPropertyFormGUI $a_form
     * @throws ilDateTimeException
     */
    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form): void
    {
        // Language selection
        $language_section = new ilFormSectionHeaderGUI();
        $language_section->setTitle(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("language_selection"));
        $a_form->addItem($language_section);

        $language_switch = new ilRadioGroupInputGUI(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("language"), "language");
        $english_option = new ilRadioOption(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("english"), "en");
        $german_option = new ilRadioOption(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("german"), "de");

        // Add English fields
        $english_subject = new ilTextInputGUI(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_caption"), "mail_subject_en");
        $english_subject->setValue($this->settings->get('mail_subject_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content")));

        // ...
        $english_body = new ilTextAreaInputGUI(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_caption"), "mail_body_en");
        $english_body->setValue($this->settings->get('mail_body_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content")));

        // Set the size of the textarea field (rows and columns)
        $english_body->setRows(10); // Set the number of rows to 10 (or any desired value)
        $english_body->setCols(50); // Set the number of columns to 50 (or any desired value)
        // ...

        $english_option->addSubItem($english_subject);
        $english_option->addSubItem($english_body);

        // Add German fields
        $german_subject = new ilTextInputGUI(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_caption"), "mail_subject_de");
        $german_subject->setValue($this->settings->get('mail_subject_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content")));

        // ...
        $german_body = new ilTextAreaInputGUI(ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_caption"), "mail_body_de");
        $german_body->setValue($this->settings->get('mail_body_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content")));

        // Set the size of the textarea field (rows and columns)
        $german_body->setRows(10); // Set the number of rows to 10 (or any desired value)
        $german_body->setCols(50); // Set the number of columns to 50 (or any desired value)

        // ...

        $german_option->addSubItem($german_subject);
        $german_option->addSubItem($german_body);

        $language_switch->addOption($english_option);
        $language_switch->addOption($german_option);
        $language_switch->setValue("de"); // Set the default selected option

        $a_form->addItem($language_switch);
    }



    public function saveCustomSettings(ilPropertyFormGUI $a_form): bool
    {
        $this->settings->set('mail_subject_de', $a_form->getInput('mail_subject_de'));
        $this->settings->set('mail_body_de', $a_form->getInput('mail_body_de'));
        $this->settings->set('mail_subject_en', $a_form->getInput('mail_subject_en'));
        $this->settings->set('mail_body_en', $a_form->getInput('mail_body_en'));
        return true;
    }
}


class CustomUserCronCheckAccounts extends ilUserCronCheckAccounts
{
    public function run(): ilCronJobResult
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilLog = $DIC['ilLog'];

        $status = ilCronJobResult::STATUS_NO_ACTION;

        $now = time();
        $two_weeks_in_seconds = $now + (60 * 60 * 24 * 14); // #14630

        // all users who are currently active and expire in the next 2 weeks
        $query = "SELECT * FROM usr_data, usr_pref " .
            "WHERE time_limit_message = '0' " .
            "AND time_limit_unlimited = '0' " .
            "AND time_limit_from < " . $ilDB->quote($now, "integer") . " " .
            "AND time_limit_until > " . $ilDB->quote($now, "integer") . " " .
            "AND time_limit_until < " . $ilDB->quote($two_weeks_in_seconds, "integer") . " " .
            "AND usr_data.usr_id = usr_pref.usr_id " .
            "AND keyword = " . $ilDB->quote("language", "text");

        $res = $ilDB->query($query);

        $mailService = new ilMail(ANONYMOUS_USER_ID);

        while ($row = $ilDB->fetchObject($res)) {
            $data = [
                "firstname" => $row->firstname,
                "lastname" => $row->lastname,
                "expires" => $row->time_limit_until,
                "email" => $row->email,
                "login" => $row->login,
                "usr_id" => $row->usr_id,
                "language" => $row->value,
                "owner" => $row->time_limit_owner,
            ];

            $subject = $this->getCustomEmailSubject($data);
            $body = $this->getCustomEmailBody($data);

            // Send mail using ilMail enqueue (which is CLI/cron safe)
            $mailService->enqueue(
                $data['email'], // to
                '',             // cc
                '',             // bcc
                $subject,
                $body,
                []              // attachments
            );

            // set status 'mail sent'
            $update = "UPDATE usr_data SET time_limit_message = '1' WHERE usr_id = " . $ilDB->quote($data['usr_id'], 'integer');
            $ilDB->query($update);

            // Log mail activity
            $ilLog->write('Cron: (checkUserAccounts()) sent message to ' . $data['login'] . '.');

            $this->counter++;
        }

        $this->checkNotConfirmedUserAccounts();

        if ($this->counter) {
            $status = ilCronJobResult::STATUS_OK;
        }

        $result = new ilCronJobResult();
        $result->setStatus($status);
        return $result;
    }

    protected function getCustomEmailBody($data)
    {
        $settings = new ilSetting(ilCustomUserCronCheckAccountsPlugin::PLUGIN_ID);

        // Get the content based on the user's language
        if ($data['language'] == 'de') {
            $emailBody = $settings->get('mail_body_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content"));
        } else {
            $emailBody = $settings->get('mail_body_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_body_content"));
        }

        $emailBody = str_replace('{USERNAME}', $data['login'], $emailBody);
        $emailBody = str_replace('{EMAIL}', $data['email'], $emailBody);
        $emailBody = str_replace('{FIRSTNAME}', $data['firstname'], $emailBody);
        $emailBody = str_replace('{LASTNAME}', $data['lastname'], $emailBody);
        $emailBody = str_replace('{EXPIRES}', strftime('%Y-%m-%d %R', $data['expires']), $emailBody);

        //$emailBody .= " " . strftime('%Y-%m-%d %R', $data['expires']);

        return $emailBody;
    }

    protected function getCustomEmailSubject($data)
    {
        $settings = new ilSetting(ilCustomUserCronCheckAccountsPlugin::PLUGIN_ID);

        if ($data['language'] == 'de') {
            $emailSubject = $settings->get('mail_subject_de', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content"));
        } else {
            $emailSubject = $settings->get('mail_subject_en', ilCustomUserCronCheckAccountsPlugin::getInstance()->txt("mail_subject_content"));
        }

        $emailSubject = str_replace('{USERNAME}', $data['login'], $emailSubject);
        $emailSubject = str_replace('{EMAIL}', $data['email'], $emailSubject);
        $emailSubject = str_replace('{FIRSTNAME}', $data['firstname'], $emailSubject);
        $emailSubject = str_replace('{LASTNAME}', $data['lastname'], $emailSubject);
        $emailSubject = str_replace('{EXPIRES}', strftime('%Y-%m-%d %R', $data['expires']), $emailSubject);

        return $emailSubject;
    }
}



