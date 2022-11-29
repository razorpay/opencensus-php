import React from 'react';
import { render } from 'test-utils';
import QuickActionsCard from 'merchant/containers/Home/ProductOnboardingCard/partials/QuickActionsCard';
import { paymentHandleData } from './mocks/fixtures';
import { createMemoryHistory } from 'history';

let history;

describe('ProductOnboardingCard - QuickActionsCard', () => {
  const renderApp = ({ initialState, ...rest } = {}) =>
    render(
      <QuickActionsCard
        history={history}
        product="PH"
        paymentHandleData={paymentHandleData}
        {...rest}
      />,
    );

  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render component without errors', () => {
    expect(() => renderApp({ initialState: {} })).not.toThrowError();
  });
});
