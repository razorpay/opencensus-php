import inquirer, { type Answers, type DistinctQuestion } from 'inquirer';
import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '../../configs';
import path from 'path';
import { cacheManager } from '../../cache';
import { DASHBOARD_FEDERATED_MODULES, DASHBOARD_ROOT } from '../../constants';
import { TaskType } from '../openMultipleTerminalsWithCommands';
import { getOnboardedApps } from '@src/dashboard-cli/utils';

const promptModule = inquirer.createPromptModule();

/**
 * Prompts the user to select local remotes and saves the selection to a JSON file.
 * @returns {Promise<void>} A promise that resolves when the prompt and command execution is complete.
 */
export const promptUserForLocalRemotes = async (): Promise<TaskType[]> => {
  /**
   * DASHBOARD_FEDERATED_MODULES.SHELL_SERVER_STREAM is not a separate app, hence ignoring it. Will evaulate its usecase later.
   */



  const questions: DistinctQuestion[] = [
    {
      type: 'checkbox',
      name: 'LOCAL_REMOTES',
      message:
        'Do you want to point any of the following remotes to their respective local webpack development servers:\n',
      loop: false,
      required: true,
      choices: getOnboardedApps(),
    },
  ];

  const answers = await promptModule<Answers>(questions);

  const selectedRemotes: Partial<DASHBOARD_FEDERATED_MODULES>[] = answers.LOCAL_REMOTES.flat();

  cacheManager.onInit();
  cacheManager.saveData({
    selectedRemotes,
  });

  // @ts-ignore
  const tasksMapArr: TaskType[] = selectedRemotes
    .filter(
      (selectedRemote) =>
        DASHBOARD_FEDERATED_MODULE_CONFIGS[selectedRemote].appDirFromRoot &&
        DASHBOARD_FEDERATED_MODULE_CONFIGS[selectedRemote].devStartCommand,
    )
    .map((selectedRemote) => ({
      selectedRemote,
      serverPort: DASHBOARD_FEDERATED_MODULE_CONFIGS[selectedRemote].devServerPort,
      // @ts-ignore
      commandToRun: DASHBOARD_FEDERATED_MODULE_CONFIGS[selectedRemote].devStartCommand,
      targetModulePathFromRoot: path.resolve(
        DASHBOARD_ROOT,
        // @ts-ignore
        `./${DASHBOARD_FEDERATED_MODULE_CONFIGS[selectedRemote].appDirFromRoot}`,
      ),
    }));

  return tasksMapArr;
};
