import React from 'react';
import {
  errorHandlers,
  fireEvent,
  render,
  screen,
  waitForLoadingToFinish,
  server,
  delay,
} from 'test-utils';

import TestModal from 'common/services/test/TestModal';
import { getFormattedAmountByParts } from 'common/utils/rzp-utils';
import BreakupModal from 'merchant/views/Settlements/Settlements/components/Modals/BreakupModal';

const onMount = jest.fn();
const onUnMount = jest.fn();

function App() {
  return (
    <TestModal
      component={
        <BreakupModal settlementId="settlementtest" onMount={onMount} onUnmount={onUnMount} />
      }
    />
  );
}

test('should handle network error', async () => {
  server.use(errorHandlers.internalServerError);

  const { queryByText } = render(<App />, { showModal: true });
  await waitForLoadingToFinish();

  expect(
    queryByText(/Total amount that has been credited to your account/i),
  ).not.toBeInTheDocument();
});

test('should render heading and action button', async () => {
  render(<App />, { showModal: true });

  await waitForLoadingToFinish();

  expect(screen.getByText(/Breakup for #settlementtest/i)).toBeInTheDocument();
  expect(screen.getByText(/Total settled amount/i)).toBeInTheDocument();
  expect(screen.getByText(/Close/i)).toBeInTheDocument();
});

test('should call mount and unmount functions', async () => {
  const { unmount } = render(<App />, { showModal: true });

  await waitForLoadingToFinish();

  expect(onMount).toBeCalledTimes(1);
  unmount();
  expect(onUnMount).toBeCalledTimes(1);
});

test('should show correct settlement amount', async () => {
  render(<App />, { showModal: true });

  await waitForLoadingToFinish();

  const settledAmount = 33691411;

  const expectedAmountObj = getFormattedAmountByParts(settledAmount);
  const expectedAmount = `${expectedAmountObj?.integer}${expectedAmountObj?.decimal}${expectedAmountObj?.fraction}`;

  expect(screen.getByText(/Total settled amount/i)).toHaveTextContent(
    new RegExp(expectedAmount, 'g'),
  );
});

test('should close modal when clicked on close', async () => {
  render(<App />, { showModal: true });

  await waitForLoadingToFinish();

  const closeButton = screen.getByRole('button', { name: 'Close' });

  fireEvent.click(closeButton);

  await delay();

  expect(screen.queryByText(/Breakup for #settlementtest/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Total settled amount/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Close/i)).not.toBeInTheDocument();
});
