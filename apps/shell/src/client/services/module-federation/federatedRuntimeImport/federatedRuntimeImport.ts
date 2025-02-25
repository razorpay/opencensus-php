// @ts-ignore
import { init, loadRemote } from '@module-federation/enhanced/runtime';

init({
  name: 'shell',
  alias: '@federated/apps/shell',
  remotes: [
    {
      alias: '@federated/cross-repo/x',
      entry: 'https://localhost:8880/dist/x.remoteEntry.js',
      name: 'x',
    },
  ],
});

export { init, loadRemote };
