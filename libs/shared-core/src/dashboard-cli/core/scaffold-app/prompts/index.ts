import inquirer, { type Answers, type DistinctQuestion } from 'inquirer';
import chalk from 'chalk';
import { getOnboardedApps } from '@src/dashboard-cli/utils';
import { DASHBOARD_FEDERATED_MODULES } from '@src/constants';

export async function getUserInput() {
  const prompt = inquirer.createPromptModule();

  const questions: DistinctQuestion[] = [
    {
      type: 'input',
      name: 'appName',
      message: chalk.gray('📦 Enter your app name:\n'),
      default: 'dashboard-app',
    },
    {
      type: 'input',
      name: 'appDescription',
      message: chalk.gray('📝 Enter your app description (optional):\n'),
      default: '',
    },
    {
      type: 'checkbox',
      name: 'appRemotes',
      message: chalk.gray('🛠 Select the remotes you want to configure (use space to select):\n'),
      choices: getOnboardedApps(),
    },
    {
      type: 'input',
      name: 'sentryProjectName',
      message: chalk.gray('🛡 Enter your Sentry project name:\n'),
    },
    {
      type: 'input',
      name: 'sentryDsn',
      message: chalk.gray('🔗 Enter your Sentry DSN:\n'),
    },
  ];

  const answers = await prompt(questions);

  const appDescription =
    answers.appDescription && answers.appDescription.trim().length > 0
      ? answers.appDescription.trim()
      : `This is the ${answers.appName} dashboard app`;

  const appRemotes = answers.appRemotes.reduce((acc: any, curr: any) => {
    if (Array.isArray(curr) && curr.includes(DASHBOARD_FEDERATED_MODULES.SHELL)) {
      return [...acc, DASHBOARD_FEDERATED_MODULES.SHELL];
    } else {
      return [...acc, curr];
    }
  }, []);

  return {
    appName: answers.appName.trim(),
    appDescription,
    appRemotes,
    appSentryConfig: {
      project: answers.sentryProjectName,
      dsn: answers.sentryDsn,
    },
  };
}
