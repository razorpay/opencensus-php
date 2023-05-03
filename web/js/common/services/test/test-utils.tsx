// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen, waitFor } from '@testing-library/react';
import { renderHook } from '@testing-library/react-hooks';
import { Router, Route } from 'react-router-dom';
// eslint-disable-next-line import/no-extraneous-dependencies
import { createMemoryHistory } from 'history';
import { Provider } from 'react-redux';
import { server } from '../../../../mocks/node';
import { errorHandlers } from '../../../../mocks/errorHandlers';
import { storeWithInitialState } from 'merchant/store';
import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import Wrapper from 'common/components/Bootstrap/Wrapper';
import userEvent from '@testing-library/user-event';
// eslint-disable-next-line
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { mockContext, COMPONENT_WRAPPER_TESTID } from 'common/services/test/constants';

const createWrapper = ({ context, reduxStore, history, showModal, path }) => {
  const AllTheProviders: React.FC<{
    children: ReactElement<any, any> | null;
  }> = ({ children }) => {
    return (
      <Wrapper context={context}>
        <Provider store={reduxStore}>
          <ConfirmModalProvider>
            <Router history={history}>
              <>
                {showModal && <ModalDialog />}
                <Notifications />
                <Route
                  path={path}
                  component={() => <div data-testid={COMPONENT_WRAPPER_TESTID}>{children}</div>}
                />
              </>
            </Router>
          </ConfirmModalProvider>
        </Provider>
      </Wrapper>
    );
  };
  return AllTheProviders;
};
const customRender = (
  ui,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  {
    path = '/',
    initialState,
    // Remove after updating snapshots
    showModal,
    reduxStore = storeWithInitialState(initialState),
    historyOptions = { initialEntries: ['/'] },
    history = createMemoryHistory(historyOptions),
    context = mockContext,
    ...restOptions
  }: any = {},
) => {
  const AllTheProviders = createWrapper({ context, reduxStore, history, showModal, path });
  const renderObj = render(ui, { wrapper: AllTheProviders, ...restOptions });
  return { ...renderObj, history };
};

const customRenderHook = (
  hook,
  {
    path = '/',
    initialState,
    // Remove after updating snapshots
    showModal,
    reduxStore = storeWithInitialState(initialState),
    historyOptions = { initialEntries: ['/'] },
    history = createMemoryHistory(historyOptions),
    context = mockContext,
    ...restOptions
  },
) => {
  const AllTheProviders = createWrapper({ context, reduxStore, history, showModal, path });
  return renderHook(hook, { wrapper: AllTheProviders, ...restOptions });
};

const waitForLoadingToFinish = (): Promise<void> =>
  waitForElementToBeRemoved(screen.queryAllByTestId('spinner'));

const checkIfComponentIsEmpty = () =>
  expect(screen.getByTestId(COMPONENT_WRAPPER_TESTID)).toBeEmptyDOMElement();

const delay = (time = 1000): Promise<void> => new Promise((r) => setTimeout(r, time));
// re-export everything
export * from '@testing-library/react';

// override render method
export {
  customRender as render,
  customRenderHook as renderHook,
  waitForLoadingToFinish,
  server,
  errorHandlers,
  delay,
  waitFor,
  userEvent,
  checkIfComponentIsEmpty,
  COMPONENT_WRAPPER_TESTID,
};
