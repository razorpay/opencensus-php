import { createDir } from './createDir';
import fs from 'fs';

export const createFile = (dirPath: string, fileName: string, content: any) => {
  try {
    createDir(dirPath);
    fs.writeFileSync(`${dirPath}/${fileName}`, content);
  } catch (err: any) {
    console.error(`Error while creating file: ${err?.message}`);
  }
};
