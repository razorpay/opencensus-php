// test-utils.js
import React, { ReactElement } from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import {
  render,
  waitForElementToBeRemoved,
  screen,
  waitFor,
  RenderResult,
} from '@testing-library/react';
import { RenderHookResult, renderHook } from '@testing-library/react-hooks';
import userEvent from '@testing-library/user-event';
import { createMemoryHistory } from 'history';
import { Provider } from 'react-redux';
import { BrowserRouter, Router as DefaultRouter, Route, Routes } from 'react-router-dom';

import Wrapper from 'common/components/Bootstrap/Wrapper';
import * as commonI18 from 'common/i18';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { mockContext, COMPONENT_WRAPPER_TESTID } from 'common/services/test/constants';
// eslint-disable-next-line
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { storeWithInitialState } from 'merchant/store';

import { errorHandlers } from '../../../../mocks/errorHandlers';
import { server } from '../../../../mocks/node';
import { TextEncoder, TextDecoder } from 'util';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

Object.assign(global, { TextDecoder, TextEncoder });

export const queryClientMock = new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
    },
  },
});

const createWrapper = ({
  context,
  reduxStore,
  showModal,
  path,
  renderViaRouteGuard = true,
  history,
  renderWithBrowserRouter = false,
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
    const Router = renderWithBrowserRouter ? BrowserRouter : DefaultRouter;
    return (
      <Wrapper context={context}>
        <Provider store={reduxStore}>
          <QueryClientProvider client={queryClientMock}>
            <BladeProvider themeTokens={bladeTheme}>
              <ConfirmModalProvider>
                <Router navigator={history} location={history.location}>
                  <>
                    {showModal && <ModalDialog />}
                    <Notifications />
                    <Routes>
                      <Route
                        path={`${path as string}/*`}
                        element={
                          <div data-testid={COMPONENT_WRAPPER_TESTID}>{renderChildren()}</div>
                        }
                      />
                    </Routes>
                  </>
                </Router>
              </ConfirmModalProvider>
            </BladeProvider>
          </QueryClientProvider>
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
    renderWithBrowserRouter,
    history = createMemoryHistory({ initialEntries }),
    ...restOptions
  }: any = {},
): RenderResult & { history: any } => {
  const AllTheProviders = createWrapper({
    context,
    reduxStore,
    showModal,
    path,
    renderViaRouteGuard,
    history,
    renderWithBrowserRouter,
  });
  const renderObj = render(ui, { wrapper: AllTheProviders, ...restOptions });
  return {
    ...renderObj,
    history,
  };
};

const customRenderHook = (
  hook: any,
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
): RenderHookResult<any, any> => {
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

const waitForLoadingToFinish = async (
  testID: 'spinner' | 'table-spinner' = 'spinner',
): Promise<void> => {
  const el = screen.queryAllByTestId(testID);
  if (el) {
    try {
      await waitForElementToBeRemoved(el);
    } catch (e) {
      console.log(e);
    }
  }
};

const waitForLoadingToFinishByLabel = async (label = 'spinner'): Promise<void> =>
  await waitFor(
    () => {
      expect(screen.queryAllByLabelText(label)).toHaveLength(0);
    },
    { timeout: 10000 },
  );

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

const renderWithSuspense = (children: React.ReactNode) =>
  customRender(<SuspenseWithLoader>{children}</SuspenseWithLoader>);

// override render method
export {
  customRender as render,
  customRenderHook as renderHook,
  waitForLoadingToFinish,
  waitForLoadingToFinishByLabel,
  server,
  errorHandlers,
  delay,
  waitFor,
  userEvent,
  checkIfComponentIsEmpty,
  COMPONENT_WRAPPER_TESTID,
  updateUseI18ServiceSpy,
  renderWithSuspense,
  queryClientMock as queryClient,
};
