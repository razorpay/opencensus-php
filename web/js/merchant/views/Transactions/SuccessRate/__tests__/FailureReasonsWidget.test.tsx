import React from 'react';
import { render, server, screen, waitFor, userEvent } from 'test-utils';
import { ongoingDowntimesHandler, resolvedDowntimesHandler } from './mocks/handlers';
import store from 'merchant/store';
import FailureReasonsWidget from 'merchant/views/Transactions/SuccessRate/containers/FailureReasonsWidget';
import { getErrorResponse, getInitialState } from './mocks/fixtures';

jest.mock('common/ui/Forms/SwitchField', () => ({
  __esModule: true,
  default: ({ defaultChecked, onChange }) => (
    <button
      aria-label="failure-reason-toggle"
      onClick={() => onChange(defaultChecked ? 0 : 1, jest.fn())}
      type="button"
    >
      Switch field toggle
    </button>
  ),
}));

const state = store.getState();

const renderApp = (initialState = state) => {
  render(<FailureReasonsWidget />, {
    initialState,
  });
};

const payload = {
  entity: 'payments',
  from: 1685248200,
  to: 1685273399,
  interval: 60,
  mode: 'razorpay',
  filters: { method: ['card'], type: ['credit'] },
  group_by: { limit: 4 },
};

describe('<SuccessRate/>', () => {
  const renderAppWithInitalApp = () => {
    const { successRate } = state;
    const errorResponse = getErrorResponse(payload);
    const initState = getInitialState({ successRate, errorResponse });
    renderApp(initState);
  };

  beforeEach(() => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
    );
  });

  test('should render failure reasons widget on screen', () => {
    renderApp();
    expect(screen.getByTestId('failure-reasons-widget')).toBeVisible();
  });

  test('should render tabs on screen', async () => {
    renderAppWithInitalApp();
    expect(screen.getByLabelText('customer-tab-button')).toBeVisible();
    await userEvent.click(screen.getByLabelText('bank-tab-button'));
    await waitFor(() => {
      expect(screen.getByTestId('failure-reasons-header')).toHaveTextContent(
        'Top payment failure reasons: Banking-related',
      );
    });
  });

  test('should render toggle button if failure type exists', async () => {
    renderAppWithInitalApp();

    await waitFor(() => {
      expect(screen.getByLabelText('failure-reason-toggle')).toBeVisible();
    });
  });

  test('should stay in same tab + tab pane if toggle button changed', async () => {
    renderAppWithInitalApp();
    expect(screen.getByLabelText('customer-tab-button')).toBeVisible();
    await userEvent.click(screen.getByLabelText('bank-tab-button'));
    await waitFor(() => {
      expect(screen.getByLabelText('failure-reason-toggle')).toBeVisible();
    });

    expect(screen.getByLabelText('bank-tab-button')).toHaveClass('selected');
  });
});
