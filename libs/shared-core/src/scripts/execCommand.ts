import { spawn, type SpawnOptions, type ChildProcess } from 'child_process';

/**
 * Custom type to extend SpawnOptions and allow for more options like stdio and shell.
 */
type ExecCommandOptions = Omit<SpawnOptions, 'shell'> & {
  initConfig?: (child: ChildProcess) => void;
};

/**
 * Executes a shell command using the spawn function and returns a promise.
 * @param command - The shell command to execute.
 * @param options - Additional spawn options.
 * @returns A Promise that resolves when the command completes successfully.
 */
const execCommand = (command: string, options?: ExecCommandOptions): Promise<void> => {
  return new Promise((resolve, reject) => {
    const initConfig = options?.initConfig;

    if (initConfig) options.initConfig = undefined;

    const child = spawn(command, {
      shell: true,
      // Log outputs of the process to main (Keep this default).
      stdio: 'inherit',
      ...options,
    });

    initConfig?.(child);

    child.on('error', (err) => {
      console.log(err);
      reject(err);
    });

    child.on('exit', (code) => {
      if (code !== 0) {
        reject('');
      } else {
        resolve();
      }
    });

    if (options?.detached) {
      // For keeping it detached (resource optimization)
      child.unref();
    }
  });
};

export { execCommand };
