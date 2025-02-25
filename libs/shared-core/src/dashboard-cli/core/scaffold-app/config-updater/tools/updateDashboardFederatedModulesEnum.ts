import { Project } from 'ts-morph';
import path from 'path';
import fs from 'fs/promises';
import { DASHBOARD_ROOT } from '@src/constants';
import { formatWithPrettier } from '@src/dashboard-cli/utils/formatWithPrettier';

export const updateDashboardFederatedModulesEnum = async ({
  appName,
}: {
  appName: string;
}) => {
  const project = new Project();
  const dashboardFederatedModulesEnumFilePath = path.resolve(
    DASHBOARD_ROOT,
    'libs/shared-core/src/constants/DASHBOARD_FEDERATED_MODULES.ts'
  );
  const sourceFile = project.addSourceFileAtPath(dashboardFederatedModulesEnumFilePath);

  const enumDec = sourceFile.getEnum('DASHBOARD_FEDERATED_MODULES');

  const federatedAppKey = appName.split('-').join('_');

  if (enumDec) {
    enumDec.addMember({
      name: federatedAppKey.toUpperCase(),
      initializer: `'${federatedAppKey}'`,
    });

    const fileText = sourceFile.getFullText();
    const formattedContent = await formatWithPrettier(fileText, dashboardFederatedModulesEnumFilePath);
    await fs.writeFile(dashboardFederatedModulesEnumFilePath, formattedContent, 'utf8');

    console.log(`✅ Added Entry In DASHBOARD_FEDERATED_MODULES.ts`);
  } else {
    console.error('[@libs/shared-core] 🫣 Enum DASHBOARD_FEDERATED_MODULES not found.');
  }
};
