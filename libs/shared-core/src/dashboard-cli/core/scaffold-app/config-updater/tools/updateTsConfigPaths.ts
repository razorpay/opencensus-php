import path from 'path';
import fs from 'fs/promises';
import { DASHBOARD_ROOT } from '@src/constants';
import { formatWithPrettier } from '@src/dashboard-cli/utils/formatWithPrettier';

export const updateTsConfigPaths = async ({ appName }: { appName: string }) => {
  const tsConfigFilePath = path.resolve(DASHBOARD_ROOT, 'tsconfig.base.json');

  try {
    const fileContent = await fs.readFile(tsConfigFilePath, 'utf8');
    const tsConfig = JSON.parse(fileContent);

    tsConfig.compilerOptions = tsConfig.compilerOptions || {};
    tsConfig.compilerOptions.paths = tsConfig.compilerOptions.paths || {};
    tsConfig.references = tsConfig.references || [];

    const newKey = `@federated/apps/${appName}/*`;
    const newValue = [`apps/${appName}/src/exposed/*`];

    if (tsConfig.compilerOptions.paths[newKey]) {
      console.log(`Path entry ${newKey} already exists.`);
      return;
    }

    tsConfig.compilerOptions.paths[newKey] = newValue;
    tsConfig.references.push({ path: `./apps/${appName}` });

    const finalContent = JSON.stringify(tsConfig, null, 2);
    const formattedContent = await formatWithPrettier(finalContent, tsConfigFilePath);

    await fs.writeFile(tsConfigFilePath, formattedContent, 'utf8');
    console.log(`✅ Updated tsconfig.base.json`);
  } catch (error) {
    console.error('[@libs/shared-core] 🫣 Error updating tsconfig.base.json', error);
  }
};
