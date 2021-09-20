// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen } from '@testing-library/react';
import { Router, Route } from 'react-router-dom';
// eslint-disable-next-line import/no-extraneous-dependencies
import { createMemoryHistory } from 'history';
import { Provider } from 'react-redux';
import { server } from '../../../../mocks/node';
import { errorHandlers } from '../../../../mocks/errorHandlers';
import store from '../../../merchant/store';
import Notifications from 'common/ui/Notifications';
import Wrapper from '../../components/Bootstrap/Wrapper';

const AllTheProviders: React.FC<{ children: ReactElement<any, any> | null }> = ({ children }) => {
  const mockRazorXExp = {
    isInstantActivationEnabled: true,
    canSkipPoiValidation: false,
    canGenerateTnCPage: true,
    isBDAndAovEnabled: true,
    isAadharEkycMandatory: true,
  };
  return (
    <Provider store={store}>
      <Wrapper
        context={{
          mode: 'test',
          org: { id: '123' },
          user: { contact_name: 'prashant' },
          experiments: mockRazorXExp,
        }}
      >
        <Notifications />
        <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
          <Route path="/" component={() => children} />
        </Router>
      </Wrapper>
    </Provider>
  );
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const customRender = (ui, options) => render(ui, { wrapper: AllTheProviders, ...options });

const waitForLoadingToFinish = (): Promise<void> =>
  waitForElementToBeRemoved(screen.queryAllByTestId('spinner'));

// re-export everything
export * from '@testing-library/react';

// override render method
export { customRender as render, waitForLoadingToFinish, server, errorHandlers };
