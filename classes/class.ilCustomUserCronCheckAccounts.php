<?php

declare(strict_types=1);

use ILIAS\Cron\Schedule\CronJobScheduleType;

/**
 * Customizable variant of the core "Check user accounts" cron job.
 *
 * It notifies users whose (time-limited) account expires within the next two
 * weeks, using an admin-configurable subject/body per language. By extending
 * {@see ilUserCronCheckAccounts} it reuses the core behaviour for deleting
 * never-confirmed registrations ({@see checkNotConfirmedUserAccounts()}) and
 * the inherited $counter.
 *
 * @author Fadi Asbih <asbih@elsa.uni-hannover.de>
 */
class ilCustomUserCronCheckAccounts extends ilUserCronCheckAccounts
{
    public const CRON_JOB_ID = 'custom_acc_exp_cron';

    /** Languages that can be configured / are matched against the user pref. */
    public const SUPPORTED_LANGUAGES = ['de', 'en'];

    /** Default mail subjects per language (seeded on activation). */
    public const DEFAULT_SUBJECT = [
        'de' => 'Konto läuft ab',
        'en' => 'Limited Account',
    ];

    /** Default mail bodies per language (seeded on activation). */
    public const DEFAULT_BODY = [
        'de' => 'Hallo {FIRSTNAME} {LASTNAME}, Ihr Konto {USERNAME} ({EMAIL}) läuft ab am: {EXPIRES}',
        'en' => 'Hi {FIRSTNAME} {LASTNAME}, Your Account {USERNAME} ({EMAIL}) expires on: {EXPIRES}',
    ];

    private ilCustomUserCronCheckAccountsPlugin $plugin;
    private ilSetting $plugin_settings;

    public function __construct(ilCustomUserCronCheckAccountsPlugin $plugin)
    {
        // Initialises the core job's db/lng/log so the inherited
        // checkNotConfirmedUserAccounts() and $counter work exactly as in core.
        parent::__construct();

        $this->plugin = $plugin;
        $this->plugin_settings = new ilSetting(self::CRON_JOB_ID);
    }

    public function getId(): string
    {
        return self::CRON_JOB_ID;
    }

    public function getTitle(): string
    {
        return $this->plugin->txt('custom_check_user_accounts');
    }

    public function getDescription(): string
    {
        return $this->plugin->txt('cron_description');
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

    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }

    public function hasCustomSettings(): bool
    {
        return true;
    }

    public function run(): ilCronJobResult
    {
        global $DIC;

        $db = $DIC->database();
        $log = $DIC->logger()->root();

        $status = ilCronJobResult::STATUS_NO_ACTION;

        $now = time();
        $two_weeks = $now + (60 * 60 * 24 * 14); // #14630

        // All active users whose limited account expires within the next two
        // weeks, joined with their language preference so we can pick the right
        // mail text. Users not yet notified (time_limit_message = 0) only.
        $query = 'SELECT ud.usr_id, ud.login, ud.firstname, ud.lastname, ud.email, '
            . 'ud.time_limit_until, up.value AS lang_key '
            . 'FROM usr_data ud '
            . 'JOIN usr_pref up ON up.usr_id = ud.usr_id AND up.keyword = ' . $db->quote('language', 'text') . ' '
            . 'WHERE ud.time_limit_message = ' . $db->quote(0, 'integer') . ' '
            . 'AND ud.time_limit_unlimited = ' . $db->quote(0, 'integer') . ' '
            . 'AND ud.time_limit_from < ' . $db->quote($now, 'integer') . ' '
            . 'AND ud.time_limit_until > ' . $db->quote($now, 'integer') . ' '
            . 'AND ud.time_limit_until < ' . $db->quote($two_weeks, 'integer');

        $res = $db->query($query);

        $mail = new ilMail(ANONYMOUS_USER_ID);

        while ($row = $db->fetchObject($res)) {
            $data = [
                'firstname' => (string) $row->firstname,
                'lastname' => (string) $row->lastname,
                'expires' => (int) $row->time_limit_until,
                'email' => (string) $row->email,
                'login' => (string) $row->login,
                'usr_id' => (int) $row->usr_id,
                'language' => (string) $row->lang_key,
            ];

            if ($data['email'] === '') {
                $log->write('Cron: (customCheckUserAccounts) skipped ' . $data['login'] . ' – no email address.');
                continue;
            }

            $mail->enqueue(
                $data['email'],
                '',
                '',
                $this->buildSubject($data),
                $this->buildBody($data),
                []
            );

            // Flag the user as notified so neither this nor the core job re-sends.
            $db->manipulateF(
                'UPDATE usr_data SET time_limit_message = %s WHERE usr_id = %s',
                ['integer', 'integer'],
                [1, $data['usr_id']]
            );

            $log->write('Cron: (customCheckUserAccounts) sent expiry notice to ' . $data['login'] . '.');
            $this->counter++;
        }

        // Reuse the core behaviour: delete users who never confirmed their
        // registration within the configured hash lifetime.
        $this->checkNotConfirmedUserAccounts();

        if ($this->counter > 0) {
            $status = ilCronJobResult::STATUS_OK;
        }

        $result = new ilCronJobResult();
        $result->setStatus($status);

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildSubject(array $data): string
    {
        $lang = $this->normalizeLanguage($data['language']);
        $template = (string) $this->plugin_settings->get('mail_subject_' . $lang, self::DEFAULT_SUBJECT[$lang]);

        return $this->applyPlaceholders($template, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildBody(array $data): string
    {
        $lang = $this->normalizeLanguage($data['language']);
        $template = (string) $this->plugin_settings->get('mail_body_' . $lang, self::DEFAULT_BODY[$lang]);

        return $this->applyPlaceholders($template, $data);
    }

    /**
     * Map an arbitrary user language key onto a supported language, defaulting
     * to English for anything we do not explicitly translate.
     */
    private function normalizeLanguage(string $language): string
    {
        return in_array($language, self::SUPPORTED_LANGUAGES, true) ? $language : 'en';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyPlaceholders(string $text, array $data): string
    {
        // strftime() was deprecated in PHP 8.1 and removed in 8.4 – use date().
        return strtr($text, [
            '{USERNAME}' => (string) $data['login'],
            '{EMAIL}' => (string) $data['email'],
            '{FIRSTNAME}' => (string) $data['firstname'],
            '{LASTNAME}' => (string) $data['lastname'],
            '{EXPIRES}' => date('Y-m-d H:i', (int) $data['expires']),
        ]);
    }

    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form): void
    {
        $section = new ilFormSectionHeaderGUI();
        $section->setTitle($this->plugin->txt('language_selection'));
        $a_form->addItem($section);

        $language_switch = new ilRadioGroupInputGUI($this->plugin->txt('language'), 'language');

        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            $option = new ilRadioOption(
                $this->plugin->txt($lang === 'de' ? 'german' : 'english'),
                $lang
            );

            $subject = new ilTextInputGUI($this->plugin->txt('mail_subject_caption'), 'mail_subject_' . $lang);
            $subject->setInfo($this->plugin->txt('mail_subject_info'));
            $subject->setValue((string) $this->plugin_settings->get('mail_subject_' . $lang, self::DEFAULT_SUBJECT[$lang]));
            $subject->setSize(80);

            $body = new ilTextAreaInputGUI($this->plugin->txt('mail_body_caption'), 'mail_body_' . $lang);
            $body->setInfo($this->plugin->txt('mail_body_info'));
            $body->setValue((string) $this->plugin_settings->get('mail_body_' . $lang, self::DEFAULT_BODY[$lang]));
            $body->setRows(10);
            $body->setCols(80);

            $option->addSubItem($subject);
            $option->addSubItem($body);
            $language_switch->addOption($option);
        }

        // Default selection only governs which block is expanded first; both
        // languages' fields are always submitted and saved.
        $language_switch->setValue('de');
        $a_form->addItem($language_switch);
    }

    public function saveCustomSettings(ilPropertyFormGUI $a_form): bool
    {
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            $this->plugin_settings->set('mail_subject_' . $lang, (string) $a_form->getInput('mail_subject_' . $lang));
            $this->plugin_settings->set('mail_body_' . $lang, (string) $a_form->getInput('mail_body_' . $lang));
        }

        return true;
    }
}
