<?php


class ilCronJobRepositoryClass implements ilCronJobRepository
{
    /**
     * Get all cron jobs managed by this repository.
     *
     * @return ilCronJob[]
     */
    public function getCronJobs(): array
    {
        // Implement logic to return an array of your custom cron jobs
        $settings = new ilSetting('custom_acc_exp_cron');
        $customCronJob = new ilCustomUserCronCheckAccounts($settings);

        return [$customCronJob];
    }
}
