import path from 'path';
import { DASHBOARD_ROOT, execCommand } from '../';

/**
 * Opens a new terminal window on the user's system and runs a specified command.
 * The terminal opens in the root directory and supports Windows, macOS, and Linux.
 *
 * @param commandToRun - The command to execute in the newly opened terminal.
 * @param targetModulePathFromRoot - The target module's path from root, example: `apps/self-serve`
 */
export const openNewTerminalWithCommand = async (
  commandToRun: string,
  targetModulePathFromRoot: string,
): Promise<void> => {
  const platform: NodeJS.Platform = process.platform;
  const targetModuleDirRelativeToRoot = path.resolve(DASHBOARD_ROOT, targetModulePathFromRoot);
  let command: string;

  // Ensure the path is correctly formatted for each platform
  const formattedTargetPath =
    platform === 'win32'
      ? targetModuleDirRelativeToRoot.replace(/\//g, '\\')
      : targetModuleDirRelativeToRoot;

  switch (platform) {
    // TODO: Disabling win32, linux for now. Will implement this logic as per usecase. Need to test it properly to make it work.
    // case 'win32': {
    //   const escapedCommandToRun = commandToRun.replace(/(["\s'$`\\])/g, '\\$1'); // Escapes dangerous characters
    //   command = `start cmd.exe /K "cd /d ${formattedTargetPath} && ${escapedCommandToRun}"`;
    //   break;
    // }
    // case 'linux': {
    //   command = `gnome-terminal --working-directory=${targetModuleDirRelativeToRoot} -- bash -c "${commandToRun}; exec bash"`;
    //   break;
    // }
    case 'darwin': {
      const escapedCommandToRun = commandToRun.replace(/"/g, '\\"');
      // TODO: remove unset npm_config_prefix && source ~/.zshrc after properly testing
      command = `osascript -e 'tell application "Terminal" to do script "cd \\"${formattedTargetPath}\\" && trap exit SIGHUP && NODE_TLS_REJECT_UNAUTHORIZED=0 ${escapedCommandToRun}"' -e 'tell application "Terminal" to activate'`;
      break;
    }
    default:
      command = ``;
  }

  try {
    await execCommand(command, {
      // No need for output logs in main. Should be visible in the opened terminal directly.
      stdio: 'ignore',
      detached: true,
    });

    console.log(
      `New terminal opened in ${targetModulePathFromRoot} and running command: ${commandToRun}.`,
    );
  } catch (error) {
    console.error(`Error opening new terminal: ${(error as Error).message}`);
  }
};
