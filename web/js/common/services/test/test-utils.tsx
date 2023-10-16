// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen, waitFor } from '@testing-library/react';
import { renderHook } from '@testing-library/react-hooks';
import { Router, Route, Routes } from 'react-router-dom';
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
import { RouteGuard } from 'merchant/components/ShowWhen';
import { createMemoryHistory } from 'history';
import * as commonI18 from 'common/i18';

const createWrapper = ({
  context,
  reduxStore,
  showModal,
  path,
  renderViaRouteGuard = true,
  history,
}) => {
  const AllTheProviders: React.FC<{
    children: ReactElement<any, any> | null;
  }> = ({ children }) => {
    const renderChildren = () => {
      if (renderViaRouteGuard) {
        return <RouteGuard>{children}</RouteGuard>;
      } else {
        return children;
      }
    };

    return (
      <Wrapper context={context}>
        <Provider store={reduxStore}>
          <ConfirmModalProvider>
            <Router navigator={history} location={history.location}>
              <>
                {showModal && <ModalDialog />}
                <Notifications />
                <Routes>
                  <Route
                    path={`${path as string}/*`}
                    element={<div data-testid={COMPONENT_WRAPPER_TESTID}>{renderChildren()}</div>}
                  />
                </Routes>
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
    initialEntries = ['/'],
    context = mockContext,
    renderViaRouteGuard,
    history = createMemoryHistory({ initialEntries }),
    ...restOptions
  }: any = {},
) => {
  const AllTheProviders = createWrapper({
    context,
    reduxStore,
    showModal,
    path,
    renderViaRouteGuard,
    history,
  });
  const renderObj = render(ui, { wrapper: AllTheProviders, ...restOptions });
  return {
    ...renderObj,
    history,
  };
};

const customRenderHook = (
  hook,
  {
    path = '/',
    initialState,
    // Remove after updating snapshots
    showModal,
    reduxStore = storeWithInitialState(initialState),
    initialEntries = ['/'],
    context = mockContext,
    renderViaRouteGuard,
    history = createMemoryHistory({ initialEntries }),
    ...restOptions
  },
) => {
  const AllTheProviders = createWrapper({
    context,
    reduxStore,
    showModal,
    path,
    renderViaRouteGuard,
    history,
  });
  return renderHook(hook, { wrapper: AllTheProviders, ...restOptions });
};

const waitForLoadingToFinish = (): Promise<void> =>
  waitForElementToBeRemoved(screen.queryAllByTestId('spinner'));

const checkIfComponentIsEmpty = () =>
  expect(screen.getByTestId(COMPONENT_WRAPPER_TESTID)).toBeEmptyDOMElement();

const delay = (time = 1000): Promise<void> => new Promise((r) => setTimeout(r, time));
// re-export everything
export * from '@testing-library/react';

const updateUseI18ServiceSpy = (configPath = '') => {
  jest.spyOn(commonI18, 'useI18Service').mockImplementation(() => ({
    isConfigTagEnabled: jest.fn((path) => path === configPath),
  }));
};

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
  updateUseI18ServiceSpy,
};
