import * as NotificationsActions from 'merchant_common/reducers/notifications';
import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import SettlementDetails from 'merchant/views/Settlements/v3/SettlementDetailsRevamp';
import { fetchSettlementItem, fetchSettlementItemServerError } from './mocks/handlers';

jest.mock('merchant/views/Settlements/v3/screens/ErrorScreen', () => ({
  __esModule: true,
  default: ({ handleRefresh, type }) => (
    <div>
      <span>Error:{type}</span>
      {type === 'server_error' && <button onClick={handleRefresh}>Refresh Details</button>}
    </div>
  ),
}));

jest.mock(
  'merchant/views/Settlements/v3/screens/SettlementDetailView/SettlementDetailViewRevamp',
  () => ({
    __esModule: true,
    default: ({ settlementId }) => (
      <div>
        <h4>Settlement Details View</h4>
        <span>Settlement ID: {settlementId}</span>
      </div>
    ),
  }),
);

const defaultProps = {
  match: {
    params: { id: 'JCVHSjHRi9QHto' },
  },
};

const renderApp = () =>
  render(<SettlementDetails {...defaultProps} />, {
    renderViaRouteGuard: false,
  });

describe('SettlementDetails Revamp', () => {
  const showNotification = jest.spyOn(NotificationsActions, 'showNotification');

  afterEach(() => {
    showNotification.mockClear();
  });

  test('should render settlement details info when fetch item api success', async () => {
    server.use(fetchSettlementItem({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Settlement Details View')).toBeInTheDocument();
    });
    expect(screen.getByText(`Settlement ID: ${defaultProps.match.params.id}`)).toBeInTheDocument();
  });

  test('should render render error screen and show notification when fetch item api fails', async () => {
    server.use(fetchSettlementItem({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Error:invalid_id')).toBeInTheDocument();
    });
    expect(screen.queryByText('Refresh Details')).not.toBeInTheDocument();
    expect(showNotification).toHaveBeenCalledTimes(1);
    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: `${defaultProps.match.params.id} is not a valid id`,
    });
  });

  test('should be able to refresh api if api failed due to server error', async () => {
    server.use(fetchSettlementItemServerError());
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Error:server_error')).toBeInTheDocument();
    });
    const refreshBtn = screen.getByRole('button', { name: 'Refresh Details' });
    server.use(fetchSettlementItem({ type: 'success' }));
    await userEvent.click(refreshBtn);
    await waitFor(() => {
      expect(
        screen.getByText(`Settlement ID: ${defaultProps.match.params.id}`),
      ).toBeInTheDocument();
    });
  });

  test('should show go back button', async () => {
    server.use(fetchSettlementItem({ type: 'success' }));
    const { history } = renderApp();

    const goBackBtn = screen.getByRole('button', { name: 'Go Back' });

    await userEvent.click(goBackBtn);
    await waitFor(() => {
      expect(history.location.pathname).toEqual('/settlements');
    });
  });
});
