const fs = require('fs');

export const removeDir = (dirPath: string) => {
  try {
    if (fs.existsSync(dirPath)) {
      fs.rmSync(dirPath, { recursive: true, force: true });
      console.log(`Removed directory: ${dirPath}`);
    } else {
      console.log(`Directory does not exist: ${dirPath}`);
    }
  } catch (err: any) {
    console.error(`Error while removing directory: ${err?.message}`);
  }
};
