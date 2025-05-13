// @ts-ignore
import { init, loadRemote } from '@module-federation/enhanced/runtime';

init({
  name: 'shell',
  alias: '@federated/apps/shell',
  remotes: [
    {
      alias: '@federated/cross-repo/x',
      entry: window.X_BANKING_REMOTE_ENTRY,
      name: 'x',
    },
  ],
});

export { init, loadRemote };
