// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen, waitFor } from '@testing-library/react';
import { BrowserRouter, Router as DefaultRouter, Route, Routes } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import userEvent from '@testing-library/user-event';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { COMPONENT_WRAPPER_TESTID } from './constants';

interface CustomRenderOptions {
  path?: string;
  initialEntries?: string[];
  renderWithBrowserRouter?: boolean;
  history?: any;
}

const createWrapper = ({
  path,
  history,
  renderWithBrowserRouter = false,
}): React.ComponentType<any> => {
  const AllTheProviders: React.FC<{
    children: ReactElement<any, any> | null;
  }> = ({ children }) => {
    const Router = renderWithBrowserRouter ? BrowserRouter : DefaultRouter;
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <Router navigator={history} location={history.location}>
          <Routes>
            <Route
              path={`${path as string}/*`}
              element={<div data-testid={COMPONENT_WRAPPER_TESTID}>{children}</div>}
            />
          </Routes>
        </Router>
      </BladeProvider>
    );
  };
  return AllTheProviders;
};
const customRender = (
  ui: React.ReactElement,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  {
    path = '/',
    initialEntries = ['/'],
    renderWithBrowserRouter,
    history = createMemoryHistory({ initialEntries }),
    ...restOptions
  }: CustomRenderOptions = {},
): Record<string, any> => {
  const AllTheProviders = createWrapper({
    path,
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
  waitForLoadingToFinishByLabel,
  delay,
  waitFor,
  userEvent,
  checkIfComponentIsEmpty,
  COMPONENT_WRAPPER_TESTID,
};
