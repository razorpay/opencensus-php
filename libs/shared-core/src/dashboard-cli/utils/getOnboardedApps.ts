import { DASHBOARD_FEDERATED_MODULE_CONFIGS } from '@src/configs';
import { DASHBOARD_FEDERATED_MODULES } from '@src/constants';

export const getOnboardedApps = () => {
  const SHELL_COMBINED_REMOTES = [
    DASHBOARD_FEDERATED_MODULES.SHELL,
    DASHBOARD_FEDERATED_MODULES.SHELL_SERVER,
    DASHBOARD_FEDERATED_MODULES.SHELL_SERVER_STREAM,
  ];

  const defaultEnabledOptions = [
    {
      name: DASHBOARD_FEDERATED_MODULES.SHELL.toUpperCase(),
      checked: true,
      disabled: false,
      value: SHELL_COMBINED_REMOTES,
    },
  ];

  // Prepare the choices for the prompt
  const remoteOptions = [
    defaultEnabledOptions,
    (Object.keys(DASHBOARD_FEDERATED_MODULE_CONFIGS) as DASHBOARD_FEDERATED_MODULES[])
      .filter((remote) => !SHELL_COMBINED_REMOTES.includes(remote))
      .map((remote) => ({
        name: remote.split('_').join(' ').toUpperCase(),
        checked: false,
        disabled: false,
        value: remote,
      })),
  ].flat();

  return remoteOptions;
};
