import React from 'react';

import type { ModeT } from 'common/services/mode';

export interface WalletSession {
  mode: ModeT;
}

export const SessionContext = React.createContext<WalletSession>({ mode: 'test' });
