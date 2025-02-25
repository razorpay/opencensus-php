import fs from 'fs';
import { DEVSTACK_FILE_FOR_E2E_RUN } from '../constants';

export const getDevstackConfigContent = () => {
  const configContent = fs.readFileSync(DEVSTACK_FILE_FOR_E2E_RUN, 'utf-8');
  const depCommits = JSON.parse(configContent);
  return depCommits;
};
