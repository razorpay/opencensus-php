import React from 'react';
import SettlementInfo from 'merchant/views/Settlements/v2/components/SettlementInfo';
import { render, waitForLoadingToFinish, errorHandlers, server, screen } from 'test-utils';

test('should load settlements info on mount', async () => {
  render(<SettlementInfo settlementId="settlmenttest" />, {});

  await waitForLoadingToFinish();

  expect(screen.getByText(/Status/i)).toBeInTheDocument();
  expect(screen.getByText(/Created At/i)).toBeInTheDocument();
  expect(screen.getByText(/Fees/i)).toBeInTheDocument();
  expect(screen.getByText(/Tax/i)).toBeInTheDocument();
});

test('should show notification on network error', async () => {
  server.use(errorHandlers.internalServerError);

  const { container } = render(<SettlementInfo settlementId="settlmenttest" />, {});
  await waitForLoadingToFinish();

  expect(container.querySelector('.Notification')).toBeInTheDocument();

  expect(screen.queryByText(/Status/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Created At/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Fees/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Tax/i)).not.toBeInTheDocument();
});
