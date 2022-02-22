import React from 'react';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { POST_ENABLE_TYPES } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';
import { fireEvent, render, screen, delay } from 'test-utils';
import TestModal from 'common/services/test/TestModal';

jest.mock('merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils', () => ({
  __esModule: true,
  getInstantPricingPercentage: () => {
    return Promise.resolve({ data: null });
  },
  enableAutomaticSettlements: () => {
    return Promise.resolve({ data: null });
  },
  getEnableEsPartialAutomaticDate: () => {
    return '21/02/2022';
  },
  getNoOfDaysAfterEsPartialEnable: () => {
    return 10;
  },
  getAutomaticSettlementTime: () => {
    return '9 AM';
  },
  getDiscountPercentage: () => {
    return -50;
  },
}));

interface AppProps {
  enabled?: boolean;
  postModalType?: string;
}

function App({ enabled, postModalType }: AppProps) {
  return (
    <TestModal component={<ScheduledModal enabled={enabled} postModalType={postModalType} />} />
  );
}

test('test pre enablement', async () => {
  render(<App />, { showModal: true });

  await delay();

  expect(screen.getByRole('heading', { name: /Automate your settlements/i })).toBeInTheDocument();
  expect(
    screen.queryByText(/Get your daily revenue settled automatically on all working days/i),
  ).toBeInTheDocument();

  expect(screen.queryByText(/Morning Settlement/i)).toBeInTheDocument();
  expect(screen.queryByText(/Evening Settlement/i)).toBeInTheDocument();
  expect(screen.queryByText(/Get early access to Same-day Settlements/i)).toBeInTheDocument();
  expect(
    screen.queryByText(/Consistent cash flow with no delays between sales and cash in hand/i),
  ).toBeInTheDocument();
  expect(screen.queryByText(/Opt-out anytime/i)).toBeInTheDocument();

  const leftIndicator = screen.getByTestId('left-indicator');
  const rightIndicator = screen.getByTestId('right-indicator');

  expect(leftIndicator).toBeInTheDocument();
  expect(rightIndicator).toBeInTheDocument();

  fireEvent.mouseOver(leftIndicator);
  expect(leftIndicator).toHaveStyle(`cursor: pointer;`);
  fireEvent.mouseOver(rightIndicator);
  expect(rightIndicator).toHaveStyle(`cursor: pointer;`);

  fireEvent.click(rightIndicator);
  await delay();
  expect(screen.queryByText(/discount on Instant Settlements fees, forever!/i)).toBeInTheDocument();
  expect(
    screen.queryByText(
      /The discounted pricing for Instant Settlements will be effective once you enable Same-day Settlements/i,
    ),
  ).toBeInTheDocument();

  expect(screen.getByRole('button', { name: 'Enable Same-day Settlements' })).not.toBeDisabled();
});

test('test close click closes modal', async () => {
  render(<App />, { showModal: true });

  await delay();
  const closeButton = screen.getByTestId('close-button');
  expect(closeButton).toBeInTheDocument();

  fireEvent.click(closeButton);
  await delay();
  expect(screen.queryByText(/Morning Settlement/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Evening Settlement/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Get early access to Same-day Settlements/i)).not.toBeInTheDocument();
  expect(
    screen.queryByText(/Consistent cash flow with no delays between sales and cash in hand/i),
  ).not.toBeInTheDocument();
  expect(screen.queryByText(/Opt-out anytime/i)).not.toBeInTheDocument();
});

test('test post enablement', async () => {
  render(<App enabled />, {
    showModal: true,
  });

  await delay();

  expect(screen.queryByText(/Same-day Settlements activated/i)).not.toBeInTheDocument();
});

test('test post enablement - partial success', async () => {
  render(<App enabled postModalType={POST_ENABLE_TYPES.SAMEDAY_PARTIAL_SUCCESS} />, {
    showModal: true,
  });

  await delay();

  expect(
    screen.getByRole('heading', { name: /Same-day Settlements activated/i }),
  ).toBeInTheDocument();

  expect(screen.queryByText('While you enjoy your benefits...')).toBeInTheDocument();
  expect(
    screen.queryByText(
      'To unlock 100% settlements, keep up your sales cycle and follow the eligibility criteria given below',
    ),
  ).toBeInTheDocument();

  const understoodButton = screen.getByRole('button', { name: 'Understood' });
  expect(understoodButton).not.toBeDisabled();

  fireEvent.click(understoodButton);
  await delay();
  expect(
    screen.queryByText('It is taking us longer to bring you full benefits of Same-day Settlements'),
  ).not.toBeInTheDocument();
});

test('test post enablement - full success', async () => {
  render(<App enabled postModalType={POST_ENABLE_TYPES.SAMEDAY_FULL_SUCCESS} />, {
    showModal: true,
  });

  await delay();

  expect(
    screen.getByRole('heading', { name: /Same-day Settlements activated/i }),
  ).toBeInTheDocument();

  expect(screen.queryByText('Enjoy all your benefits!')).toBeInTheDocument();
  expect(screen.queryByText('Instant and Same-day Settlements, forever!')).toBeInTheDocument();
  expect(screen.queryByText('100%')).toBeInTheDocument();
  expect(screen.queryByText('Discount on your Instant Settlements')).toBeInTheDocument();

  expect(screen.getByRole('button', { name: 'Understood' })).not.toBeDisabled();
});

test('test post enablement - full shift success', async () => {
  render(<App enabled postModalType={POST_ENABLE_TYPES.SAMEDAY_FULL_SUCCESS_SHIFT} />, {
    showModal: true,
  });

  await delay();

  expect(screen.getByRole('heading', { name: /Congratulations/i })).toBeInTheDocument();

  expect(
    screen.queryByText('You can now settle your full balance via Instant and Same-day Settlements'),
  ).toBeInTheDocument();
  expect(screen.queryByText('Enjoy all your benefits!')).toBeInTheDocument();
  expect(screen.queryByText('Instant and Same-day Settlements, forever!')).toBeInTheDocument();
  expect(screen.queryByText('100%')).toBeInTheDocument();
  expect(screen.queryByText('Discount on your Instant Settlements')).toBeInTheDocument();

  expect(screen.getByRole('button', { name: 'Understood' })).not.toBeDisabled();
});

test('test post enablement - full shift success w/o automatic', async () => {
  render(<App enabled postModalType={POST_ENABLE_TYPES.FULL_SUCCESS_SHIFT_WITHOUT_SAMEDAY} />, {
    showModal: true,
  });

  await delay();

  expect(screen.getByRole('heading', { name: /Congratulations/i })).toBeInTheDocument();

  expect(
    screen.queryByText('You can now settle your full balance via Instant Settlements'),
  ).toBeInTheDocument();
  expect(screen.queryByText('Enjoy all your benefits!')).toBeInTheDocument();
  expect(screen.queryByText('Instant and Same-day Settlements, forever!')).toBeInTheDocument();
  expect(screen.queryByText('100%')).toBeInTheDocument();
  expect(screen.queryByText('₹0')).toBeInTheDocument();
  expect(screen.queryByText('Additional annual maintenance fees')).toBeInTheDocument();

  expect(screen.getByRole('button', { name: 'Understood' })).not.toBeDisabled();
});

test('test post enablement - full shift failure', async () => {
  render(<App enabled postModalType={POST_ENABLE_TYPES.SAMEDAY_FULL_FAILURE} />, {
    showModal: true,
  });

  await delay();

  expect(screen.getByRole('heading', { name: /Hold on tight../i })).toBeInTheDocument();

  expect(
    screen.queryByText('It is taking us longer to bring you full benefits of Same-day Settlements'),
  ).toBeInTheDocument();
  expect(screen.queryByText('To unlock 100% settlements')).toBeInTheDocument();

  expect(
    screen.queryByText('Keep up your sales cycle and follow the eligibility criteria given below'),
  ).toBeInTheDocument();
  expect(screen.queryByText('Maintain daily payment gateway transactions')).toBeInTheDocument();
  expect(screen.queryByText('Keep refunds low')).toBeInTheDocument();
  expect(screen.queryByText('Minimise bank chargebacks')).toBeInTheDocument();
});
