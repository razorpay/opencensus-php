import React from 'react';

import type { ModeT } from 'common/services/mode';

export interface GCMSSession {
  mode: ModeT;
  merchantId: string;
}

export const SessionContext = React.createContext<GCMSSession>({
  mode: 'test',
  merchantId: '',
});
