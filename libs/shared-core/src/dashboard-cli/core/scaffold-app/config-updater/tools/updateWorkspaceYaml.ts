import { parseDocument, YAMLSeq } from 'yaml';
import path from 'path';
import fs from 'fs/promises';
import { DASHBOARD_ROOT } from '@src/constants';

export const updateWorkspacePackages = async ({ appName }: { appName: string }) => {
  const workspaceFilePath = path.resolve(DASHBOARD_ROOT, 'pnpm-workspace.yaml');
  try {
    const fileContent = await fs.readFile(workspaceFilePath, 'utf8');
    const doc = parseDocument(fileContent);
    const newEntry = `apps/${appName}`;

    // Get or initialize the packages node as a YAMLSeq
    let packagesNode = doc.get('packages') as any;
    if (!(packagesNode instanceof YAMLSeq)) {
      packagesNode = new YAMLSeq();
      doc.set('packages', packagesNode);
    }

    // Check if newEntry already exists
    const entries = packagesNode.items.map((item: any) => item.toString());
    if (!entries.includes(newEntry)) {
      packagesNode.add(newEntry);
      await fs.writeFile(workspaceFilePath, String(doc), 'utf8');
      console.log(`✅ Added App To Workspace (pnpm-workspace.yaml)`);
    } else {
      console.error(`[@libs/shared-core] 🫣 ${newEntry} already exists.`);
    }
  } catch (error) {
    console.error('[@libs/shared-core] 🫣 Error Updating Workspace:', error);
  }
};
