import { openNewTerminalWithCommand } from './openNewTerminalWithCommand';
import { DASHBOARD_FEDERATED_MODULES } from '../constants';

type TaskType = {
  commandToRun: string;
  targetModulePathFromRoot: string;
  serverPort: number;
  selectedRemote: DASHBOARD_FEDERATED_MODULES;
};

/**
 * Opens multiple new terminal windows and runs the specified commands in their respective directories.
 * Each command will be executed in its target directory.
 *
 * @param tasks - Array of objects containing the command and target module path from root.
 * Example:
 * [
 *   { commandToRun: 'npm start', targetModulePathFromRoot: 'apps/self-serve' },
 *   { commandToRun: 'npm run build', targetModulePathFromRoot: 'apps/admin' }
 * ]
 */
const openMultipleTerminalsWithCommands = async (tasks: TaskType[]): Promise<void> => {
  try {
    const localDevRemotes = tasks.map((task) => task.selectedRemote).join(',');
    await Promise.all(
      tasks.map((task) => {
        const targetCommandToRun = `LOCAL_DEV_REMOTES=${localDevRemotes} ${task.commandToRun}`;

        return openNewTerminalWithCommand(targetCommandToRun, task.targetModulePathFromRoot);
      }),
    );
    console.log('All terminals opened and commands executed successfully.');
  } catch (error) {
    console.error(`Error executing one of the commands: ${(error as Error).message}`);
  }
};

export { type TaskType, openMultipleTerminalsWithCommands };
