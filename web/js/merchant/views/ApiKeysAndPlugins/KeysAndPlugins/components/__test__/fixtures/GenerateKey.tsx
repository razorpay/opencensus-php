import React from 'react';
import { Provider } from 'react-redux';

import { KEYS } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/__test__/mocks/fixtures';
import { storeWithInitialState } from 'merchant/store';

import GenerateKey, {
  GenerateKeyProps,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/GenerateKey';
import { render } from 'test-utils';

export const stateWithNoKeys = {
  keys: {
    loading: false,
    isLoaded: true,
    keys: [],
    count: 0,
  },
};

export const stateWithKeys = {
  keys: {
    loading: false,
    isLoaded: true,
    keys: Object.values(KEYS),
    count: Object.keys(KEYS).length,
  },
};

interface RenderAppProps extends Partial<GenerateKeyProps> {
  initialState: any;
}
export const renderApp = ({ initialState = {}, ...rest }: RenderAppProps) =>
  render(
    <Provider store={storeWithInitialState(initialState)}>
      <GenerateKey {...rest} />
    </Provider>,
  );
