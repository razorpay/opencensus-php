import React from 'react';

import type { ModeT } from 'common/services/mode';
import { walletPaths } from './constants';

export interface WalletSession {
  mode: ModeT;
  location: {
    pathname: string;
  };
  merchant_id: string;
}

export const SessionContext = React.createContext<WalletSession>({
  mode: 'test',
  location: {
    pathname: walletPaths.batchActions,
  },
  merchant_id: '',
});
