// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen, waitFor } from '@testing-library/react';
import { BrowserRouter, Router as DefaultRouter, Route, Routes } from 'react-router-dom';
import { Provider } from 'react-redux';
import { createMemoryHistory } from 'history';
import userEvent from '@testing-library/user-event';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';

import { Store } from 'redux';

import ModalDialog from '@libs/web-nexus/common/ui/ModalDialog';
import Notifications from '@libs/web-nexus/common/ui/Notifications';

import { RouteGuard } from '@libs/web-nexus/merchant/components/RouteGuard';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
} from '@tanstack/react-query';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { server } from '../mocks/setup';
import { COMPONENT_WRAPPER_TESTID } from './constants';
import Wrapper from 'apps/self-serve/src/bootstrap/Wrapper/Wrapper';
import { storeWithInitialState } from 'apps/self-serve/src/bootstrap/Store';

interface CustomRenderOptions {
  path?: string;
  initialState?: any;
  showModal?: boolean;
  reduxStore?: Store<any, any>;
  initialEntries?: string[];
  renderViaRouteGuard?: boolean;
  renderWithBrowserRouter?: boolean;
  history?: any;
}

export const queryClient = new QueryClient({});

const createWrapper = ({
  reduxStore,
  showModal,
  path,
  renderViaRouteGuard = false,
  history,
  renderWithBrowserRouter = false,
}: CustomRenderOptions): React.ComponentType<any> => {
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
      <Wrapper>
        <ReactQueryClientProvider client={queryClient}>
          <BladeProvider themeTokens={bladeTheme}>
            <ThemeProvider theme={theme}>
              <Provider store={reduxStore}>
                <Router navigator={history} location={history.location}>
                  <>
                    {showModal ? <ModalDialog /> : null}
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
              </Provider>
            </ThemeProvider>
          </BladeProvider>
        </ReactQueryClientProvider>
      </Wrapper>
    );
  };
  return AllTheProviders;
};
const customRender = (
  ui: React.ReactElement,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  {
    path = '/',
    initialState,
    // Remove after updating snapshots
    showModal,
    reduxStore = storeWithInitialState(initialState),
    initialEntries = ['/'],
    renderViaRouteGuard,
    renderWithBrowserRouter,
    history = createMemoryHistory({ initialEntries }),
    ...restOptions
  }: CustomRenderOptions = {},
) => {
  const AllTheProviders = createWrapper({
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

const waitForLoadingToFinish = (testID = 'spinner'): Promise<void> =>
  waitForElementToBeRemoved(screen.queryAllByTestId(testID));
const waitForLoadingToFinishByLabel = (label = 'spinner'): Promise<void> =>
  waitFor(
    () => {
      expect(screen.queryAllByLabelText(label)).toHaveLength(0);
    },
    { timeout: 10000 },
  );

const checkIfComponentIsEmpty = (): void =>
  expect(screen.getByTestId(COMPONENT_WRAPPER_TESTID)).toBeEmptyDOMElement();

const delay = (time = 1000): Promise<void> => new Promise((r) => setTimeout(r, time));
// re-export everything
export * from '@testing-library/react';

// override render method
export {
  customRender as render,
  waitForLoadingToFinish,
  server,
  delay,
  waitFor,
  userEvent,
  checkIfComponentIsEmpty,
  COMPONENT_WRAPPER_TESTID,
  waitForLoadingToFinishByLabel,
};
