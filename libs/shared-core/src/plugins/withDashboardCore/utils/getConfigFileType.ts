import path from 'path';

export enum ConfigFileType {
  WEBPACK = 'webpack.config.js',
  JEST = 'jest.config.js',
  PLAYWRIGHT = 'playwright.config.js',
  ESLINT = 'eslint.config.ts',
  RSPACK = 'rspack.config.js',
}

// Function to check if the current execution stack includes a specific file type
const isExecutedFromConfig = (fileType: string): boolean => {
  const stack = new Error().stack || '';
  const resolvedPath = path.resolve(process.cwd(), fileType);
  return stack.includes(resolvedPath);
};

// Function to detect the config file type based on the execution context
export const getConfigFileType = (): ConfigFileType | undefined => {
  const configFileMap: Record<string, ConfigFileType> = {
    'webpack.config.js': ConfigFileType.WEBPACK,
    'jest.config.js': ConfigFileType.JEST,
    'playwright.config.js': ConfigFileType.PLAYWRIGHT,
    'eslint.config.ts': ConfigFileType.ESLINT,
    'rspack.config.js': ConfigFileType.RSPACK,
  };

  // Find the config file type that matches the execution stack
  const matchedFile = Object.keys(configFileMap).find((file) => isExecutedFromConfig(file));

  // Return the corresponding ConfigFileType or undefined if not found
  return matchedFile ? configFileMap[matchedFile] : undefined;
};
