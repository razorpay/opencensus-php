const fs = require('fs');

export const createDir = (dirPath: string) => {
  try {
    if (!fs.existsSync(dirPath)) {
      fs.mkdirSync(dirPath, { recursive: true });
    }
  } catch (err: any) {
    console.error(`Error while creating directory: ${err?.message}`);
  }
};
