import { Project, SyntaxKind } from 'ts-morph';
import path from 'path';
import { DASHBOARD_ROOT } from '@src/constants';
import { formatWithPrettier } from '@src/dashboard-cli/utils/formatWithPrettier';
import fs from 'fs/promises';

export const updateDashboardFederatedModuleConfigs = async ({
  appName,
  appPort,
}: {
  appName: string;
  appPort: number;
}) => {
  const project = new Project();
  const dashboardConfigFilePath = path.resolve(
    DASHBOARD_ROOT,
    'libs/shared-core/src/configs/DASHBOARD_FEDERATED_MODULE_CONFIGS.ts',
  );
  const sourceFile = project.addSourceFileAtPath(dashboardConfigFilePath);

  const varDec = sourceFile.getVariableDeclaration('DASHBOARD_FEDERATED_MODULE_CONFIGS');
  if (!varDec) {
    throw new Error('[@libs/shared-core] 🫣 Unable to locate DASHBOARD_FEDERATED_MODULE_CONFIGS.ts file.');
  }

  const configObj = varDec.getInitializerIfKindOrThrow(SyntaxKind.ObjectLiteralExpression);

  const federatedAppKey = appName.split('-').join('_');
  const enumMember = federatedAppKey.toUpperCase();

  const existingProperty = configObj.getProperty(`[DASHBOARD_FEDERATED_MODULES.${enumMember}]`);
  if (existingProperty) {
    throw new Error('[@libs/shared-core] 🫣 Provided app already exists.');
  }

  // Add the new configuration entry
  configObj.addPropertyAssignment({
    name: `[DASHBOARD_FEDERATED_MODULES.${enumMember}]`,
    initializer: `{
      appDirFromRoot: 'apps/${appName}',
      devStartCommand: 'pnpm nx start',
      devServerPort: ${appPort},
      buildType: DASHBOARD_APP_BUNDLE_TYPE.BROWSER,
      ...generateBaseMfeBaseMeta({
        moduleType: DASHBOARD_FEDERATED_MODULE_TYPE.APP,
        moduleName: DASHBOARD_FEDERATED_MODULES.${enumMember},
      }),
    }`,
  });

  const fileText = sourceFile.getFullText();
  const formattedContent = await formatWithPrettier(fileText, dashboardConfigFilePath);
  await fs.writeFile(dashboardConfigFilePath, formattedContent, 'utf8');
  console.log(`✅ Added Entry In DASHBOARD_FEDERATED_MODULE_CONFIGS.ts`);
};
