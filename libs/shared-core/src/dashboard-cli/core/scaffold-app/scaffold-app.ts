#!/usr/bin/env node
import { DASHBOARD_FEDERATED_MODULES, DASHBOARD_ROOT } from '@src/constants';
import { getUserInput } from './prompts';
import path from 'path';
import chalk from 'chalk';
import { appTemplateGenerator } from './generators/appTemplateGenerator';
import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '@src/configs';
import { integrateNewMicroapp } from './config-updater/integrateNewMicroapp';
import { execCommand } from '@src/scripts';
import { getAvailablePort } from './utils/getAvailablePort';

(async () => {
  try {
    console.log(`${chalk.bold.blue('[@libs/shared-core]')} 🚀  Welcome to the Dashboard CLI!\n`);
    
    const answers = await getUserInput();
    const assignedPort = getAvailablePort();
    
    const outputDir = path.resolve(DASHBOARD_ROOT, 'apps', answers.appName);
    const integratedAppName = answers.appName.split('-').join('_').toUpperCase();

    // Trim out shell server related remotes
    answers.appRemotes.reduce((acc: string[], curr: string | string[]) => {
      if (Array.isArray(curr) && curr.includes(DASHBOARD_FEDERATED_MODULES.SHELL)) {
        return [...curr, DASHBOARD_FEDERATED_MODULES.SHELL];
      } else {
        return [...acc, curr];
      }
    }, []);

    const integratedAppSentryConfig = {
      dsn: {
        key: `${integratedAppName}_SENTRY_DSN`,
        value: answers.appSentryConfig?.dsn || '',
      },
      project: {
        key: `${integratedAppName}_SENTRY_PROJECT`,
        value: answers.appSentryConfig?.project || '',
      },
    };

    // Note: Maintaining this config for easy reference, want to promote maintained template structure (.tpl) structure for future maintainability
    const config = {
      appPort: assignedPort,
      appName: answers.appName,
      appDescription: answers.appDescription,
      integratedAppName,
      integratedAppSentryConfig,
      integratedAppRemotes: `[${answers.appRemotes
        .map(
          (remote: DASHBOARD_FEDERATED_MODULES) =>
            `DASHBOARD_FEDERATED_MODULES.${remote.toUpperCase()}`,
        )
        .join(', ')}]`,
      webpackSentryConstants: integratedAppSentryConfig
        ? `const ${integratedAppSentryConfig.dsn.key} = \`\${process.env.${integratedAppSentryConfig.dsn.key}}\`;\nconst ${integratedAppSentryConfig.project.key} = \`\${process.env.${integratedAppSentryConfig.project.key}}\`;`
        : '',
      webpackSentryConfig: integratedAppSentryConfig
        ? //  prettier-ignore
          `sentryConfig: {
            dsn: ${integratedAppSentryConfig.dsn.key},
            project: ${integratedAppSentryConfig.project.key},
          },`
        : '',
      tsconfigExtendedReferences: answers.appRemotes
        .map((remote: DASHBOARD_FEDERATED_MODULES) =>
          JSON.stringify({
            path: `../../${DASHBOARD_FEDERATED_MODULE_CONFIGS[remote].appDirFromRoot}`,
          }),
        )
        .join(','),
    };

    console.log(`\n${chalk.bold.blue("[@libs/shared-core]")} ✨: Crafting New Microapp...\n`);

    await appTemplateGenerator(
      path.resolve(
        DASHBOARD_ROOT,
        'libs/shared-core/src/dashboard-cli/core/scaffold-app/templates',
      ),
      outputDir,
      ['rspack.config.js.tpl'],
      config,
    );

    console.log(`\n${chalk.bold.blue("[@libs/shared-core]")} ✨: Updating Core Configurations...\n`);

    await integrateNewMicroapp(config);

    console.log(`\n${chalk.bold.blue("[@libs/shared-core]")} ✨: Installing Deps...\n`);
    
    await execCommand("pnpm install", { cwd: DASHBOARD_ROOT });

    console.log(`\n${chalk.bold.blue("[@libs/shared-core]")} ✨: Port assigned for your microapp is ${assignedPort}.`);
    console.log(`\n${chalk.bold.blue("[@libs/shared-core]")} ✨: Successfully Scaffolded New Microapp! Happy Coding! 🚀`);
  } catch (error) {
    console.error('[@libs/shared-core] 😅 Error Scaffolding New MicroApp', error);
    process.exit(1);
  }
})();
