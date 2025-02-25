import { execCommand } from '../scripts';
import { spawn } from 'child_process';

export const checkPortInUse = async (ports: number[]) => {
  try {
    console.log('\n');
    await execCommand(`lsof -i tcp:${ports.join(',')}`);

    const pidRefsForConflictingPorts = await new Promise<string[]>((resolve, reject) => {
      const child = spawn(`lsof -t -i tcp:${ports.join(',')}`, {
        shell: true,
        stdio: ['ignore', 'pipe', 'pipe'], // Capture stdout and stderr via pipe
      });

      let output = '';

      // Listen for data on stdout (standard output)
      child.stdout.on('data', (data) => {
        output += data.toString(); // Accumulate the output data
      });

      // Listen for errors
      child.stderr.on('data', (data) => {
        console.error(`Error output: ${data}`);
      });

      child.on('error', (err) => {
        reject(err);
      });

      child.on('exit', (code) => {
        if (code !== 0) {
          reject('');
        } else {
          // Ensure only valid numeric PIDs are returned
          const pids = output
            .trim()
            .split('\n')
            .map((pid) => pid.trim())
            .filter((pid) => /^\d+$/.test(pid)); // Filter out non-numeric values

          resolve(pids);
        }
      });
    });

    console.log('\nConflicting Processes (PIDs):', pidRefsForConflictingPorts);
    return pidRefsForConflictingPorts;
  } catch (error) {
    console.log(error);
  }
};
