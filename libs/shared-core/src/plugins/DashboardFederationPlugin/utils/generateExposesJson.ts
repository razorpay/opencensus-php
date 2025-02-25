import fs from 'fs';
import path from 'path';

/**
 * Recursively reads the directory and creates an exposes object.
 * @param dirPath - The absolute path of the directory.
 * @param exposedDir - The relative path for creating keys in exposes object.
 * @returns The dynamically generated exposes object.
 */
const createExposesFromDirectory = (
  dirPath: string,
  exposedDir: string,
): Record<string, string> => {
  const exposes: Record<string, string> = {};
  const entries = fs.readdirSync(dirPath, { withFileTypes: true });

  entries.forEach((entry) => {
    const fullPath = path.join(dirPath, entry.name);
    const relativePath = path.relative(process.cwd(), fullPath);

    if (entry.isDirectory()) {
      const nestedExposes = createExposesFromDirectory(fullPath, exposedDir);
      Object.assign(exposes, nestedExposes);
    } else {
      if (entry.name === 'index.ts' || entry.name === 'index.js') {
        const parentKey = `./${path.relative(exposedDir, dirPath).replace(/\\/g, '/')}`;
        exposes[parentKey] = `./${relativePath.replace(/\\/g, '/')}`;
      } else {
        const fileKey = `./${path
          .relative(exposedDir, fullPath)
          .replace(/\\/g, '/')
          .replace(/\.[^/.]+$/, '')}`;
        exposes[fileKey] = `./${relativePath.replace(/\\/g, '/')}`;
      }
    }
  });

  return exposes;
};

/**
 * Parses the exposedDir and creates the exposes object dynamically.
 * @param exposedDir - Pass exposedDir.
 * @returns The parsed options with the dynamically generated exposes object.
 */
export const generateExposesJson = (exposedDir?: string) => {
  if (!exposedDir || typeof exposedDir !== 'string') {
    throw new Error('exposedDir must be a valid string pointing to a directory.');
  }

  const resolvedExposedDir = path.resolve(exposedDir);
  if (!fs.existsSync(resolvedExposedDir)) {
    throw new Error(`The directory ${resolvedExposedDir} does not exist.`);
  }

  const exposes = createExposesFromDirectory(resolvedExposedDir, exposedDir);

  return exposes;
};
