# CustomUserCronCheckAccounts
CustomUserCronCheckAccounts is a plugin for the [ILIAS](https://www.ilias.de/) Learning Management System, which extends the default ILIAS "check user accounts" Cronjob,
to allow you to customize the email/notification message sent to the user.

## Version
1.0.1

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
```
```bash
cd Customizing/global/plugins/Services/Cron/CronHook
```
```bash
git clone https://github.com/iFadi/CustomUserCronCheckAccounts.git
```

After cloning the plugin, ensure you run on the ILIAS root folder:
```bash
composer du
```

After activating the plugin, a new cronjob "Customizable - Check user accounts" should appear. You should also deactivate the default ILIAS "Check user accounts" Cronjob.

### Configuration

You can define an English and a German Message, here are the variables that can be used. 
#### Variables which can be used in the message subject/body
* {USERNAME}
* {EMAIL}
* {FIRSTNAME}
* {LASTNAME}
* {EXPIRES}

## Changelog

#### v1.0.1:
* Refactoring for ILIAS 8

## Tested on the following ILIAS Versions:
* v8.6

## Maintainer
[ZQS/elsa - Leibniz Universität Hannover](https://www.zqs.uni-hannover.de/de/zqs/team-kontakt/elsa/), [elearning@uni-hannover.de](mailto:elearning@uni-hannover.de)

