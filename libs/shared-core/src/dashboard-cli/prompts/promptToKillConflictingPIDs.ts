import inquirer from 'inquirer';
import { execCommand } from '../../scripts';

const promptModule = inquirer.createPromptModule();

/**
 * Function to prompt the user whether they want to kill conflicting processes (PIDs) that are using the selected ports.
 * Provides a detailed explanation and executes a callback based on their response.
 */
export const promptToKillConflictingPIDs = async (portsInUse: string[]): Promise<void> => {
  console.error(
    `Some of the selected remote's port are already in use by other processes. This will cause conflicts with the current operation. \n`,
  );

  const answer = await promptModule([
    {
      type: 'confirm',
      name: 'killConflictingPIDs',
      message: 'Would you like to terminate the conflicting processes to proceed?\n',
      default: false,
    },
  ]);

  if (answer.killConflictingPIDs) {
    await execCommand(`kill -9 ${portsInUse.join(' ')}`);
  } else {
    process.exit(0);
  }
};
