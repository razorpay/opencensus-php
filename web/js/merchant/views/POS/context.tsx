import { createContext, Dispatch } from 'react';

import { PosStoreInitialState } from './constants';
import { PosDeviceStoreState, PosDeviceStoreActionType } from './types';

type PosDeviceStoreContext = {
  state: PosDeviceStoreState;
  dispatch: Dispatch<PosDeviceStoreActionType>;
};

export const PosDeviceStoreContext = createContext<PosDeviceStoreContext>({
  state: PosStoreInitialState,
  dispatch: () => {},
});
