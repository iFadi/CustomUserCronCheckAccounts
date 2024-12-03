CustomUserCronCheckAccounts
============
![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/iFadi/CustomUserCronCheckAccounts?style=flat-square)
![GitHub closed issues](https://img.shields.io/github/issues-closed/iFadi/CustomUserCronCheckAccounts?style=flat-square&color=success)
[![GitHub issues](https://img.shields.io/github/issues/iFadi/CustomUserCronCheckAccounts?style=flat-square&color=yellow)](https://github.com/iFadi/CustomUserCronCheckAccounts/issues)
![GitHub closed pull requests](https://img.shields.io/github/issues-pr-closed/iFadi/CustomUserCronCheckAccounts?style=flat-square&color=success)
![GitHub pull requests](https://img.shields.io/github/issues-pr/iFadi/CustomUserCronCheckAccounts?style=flat-square&color=yellow)
[![GitHub forks](https://img.shields.io/github/forks/iFadi/CustomUserCronCheckAccounts?style=flat-square&color=blueviolet)](https://github.com/iFadi/CustomUserCronCheckAccounts/network)
[![GitHub stars](https://img.shields.io/github/stars/iFadi/CustomUserCronCheckAccounts?style=flat-square&color=blueviolet)](https://github.com/iFadi/CustomUserCronCheckAccounts/stargazers)
[![GitHub license](https://img.shields.io/github/license/iFadi/CustomUserCronCheckAccounts?style=flat-square)](https://github.com/iFadi/CustomUserCronCheckAccounts/blob/main/LICENSE)
CustomUserCronCheckAccounts is a plugin for the [ILIAS](https://www.ilias.de/) Learning Management System, which extends the default ILIAS "check user accounts" Cronjob,
to allow you to customize the email/notification message sent to the user.

## Version
v1.0.10

## Screenshots
* This is the default ILIAS Message
![Settings](screenshots/custom_acc_exp_cron_2.png)


* This is the one generated from the plugin
![Settings](screenshots/custom_acc_exp_cron_1.png)
![Settings](screenshots/custom_acc_exp_cron_3.png)


## Installation
In your {ILIAS Root} directory
```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/iFadi/CustomUserCronCheckAccounts.git
```

to list the plugin under "Administration" > "Plugins", you should run in your {ILIAS Root}:
```bash
composer du --no-dev
```

After activating the plugin, a new cronjob "Customizable - Check user accounts" should appear under "System Settings and Maintenance --> General Settings --> Cron Jobs"
. You should also deactivate the default ILIAS "Check user accounts" Cronjob.

#### Parameters which can be used in the text subject/body
* {USERNAME}
* {EMAIL}
* {FIRSTNAME}
* {LASTNAME}
* {EXPIRES}

## Tested on the following ILIAS Versions:
* v9.0
* v9.99

PS: For older ILIAS versions, you can choose to use older tags/versions of the plugin.

## Maintainer
[ZQS/elsa - Leibniz Universität Hannover](https://www.zqs.uni-hannover.de/de/zqs/team-kontakt/elsa/), [elearning@uni-hannover.de](mailto:elearning@uni-hannover.de)

