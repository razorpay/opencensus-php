// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

import { UTIL_WRAPPER_TESTID } from './constants';

const createWrapper = () => {
  const AllTheProviders: React.FC<{
    children: ReactElement<any, any> | null;
  }> = ({ children }) => {
    return (
      <BladeProvider themeTokens={paymentTheme}>
        <div data-testid={UTIL_WRAPPER_TESTID}>{children}</div>
      </BladeProvider>
    );
  };
  return AllTheProviders;
};
const customRender = (
  ui: React.ReactElement,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  { ...restOptions }: any = {},
) => {
  const AllTheProviders = createWrapper();
  const renderObj = render(ui, { wrapper: AllTheProviders, ...restOptions });
  return {
    ...renderObj,
    history,
  };
};

const waitForLoadingToFinish = (testID = 'spinner'): Promise<void> =>
  waitForElementToBeRemoved(screen.queryAllByTestId(testID));

const delay = (time = 1000): Promise<void> => new Promise((r) => setTimeout(r, time));
// re-export everything
export * from '@testing-library/react';

// override render method
export {
  customRender as render,
  waitForLoadingToFinish,
  delay,
  waitFor,
  userEvent,
  UTIL_WRAPPER_TESTID,
};
