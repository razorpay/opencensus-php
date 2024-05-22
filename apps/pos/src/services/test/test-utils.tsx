// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen, waitFor } from '@testing-library/react';
import { BrowserRouter, Router as DefaultRouter, Route, Routes } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import userEvent from '@testing-library/user-event';

import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';

import { RouteGuard } from 'shell/components/ShowWhen';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
} from '@tanstack/react-query';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { server } from '../mocks/setup';
import { COMPONENT_WRAPPER_TESTID } from './constants';

interface CustomRenderOptions {
  path?: string;
  initialState?: any;
  showModal?: boolean;
  initialEntries?: string[];
  renderViaRouteGuard?: boolean;
  renderWithBrowserRouter?: boolean;
  history?: any;
}

export const queryClient = new QueryClient({});

const createWrapper = ({
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
      <ReactQueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={bladeTheme}>
          <Router navigator={history} location={history.location}>
            <>
              {showModal ? <ModalDialog /> : null}
              <Notifications />
              <Routes>
                <Route
                  path={`${path as string}/*`}
                  element={<div data-testid={COMPONENT_WRAPPER_TESTID}>{renderChildren()}</div>}
                />
              </Routes>
            </>
          </Router>
        </BladeProvider>
      </ReactQueryClientProvider>
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
    initialEntries = ['/'],
    renderViaRouteGuard,
    renderWithBrowserRouter,
    history = createMemoryHistory({ initialEntries }),
    ...restOptions
  }: CustomRenderOptions = {},
) => {
  const AllTheProviders = createWrapper({
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
