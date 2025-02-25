const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const moveFile = (oldPath, newPath) => {
  try {
    // Ensure the destination directory exists
    const dir = path.dirname(newPath);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }

    // Move the file
    fs.renameSync(oldPath, newPath);
    console.log(`File moved successfully moved from ${oldPath} to ${newPath}`);
  } catch (err) {
    console.log('Error moving file:', err.message);
    process.exit(1);
  }
};

const execCommand = (command, options = {}) => {
  return new Promise((resolve, reject) => {
    const child = spawn(command, {
      shell: true,
      stdio: 'inherit',
      ...options,
    });

    child.on('error', (err) => {
      console.log('Error:', err);
      reject(err);
    });

    child.on('exit', (code) => {
      if (code !== 0) {
        reject(new Error(`Process exited with code ${code}`));
      } else {
        resolve();
      }
    });
  });
};

module.exports = { execCommand, moveFile };
