import React from 'react';
import { fireEvent, render, screen, waitFor, server } from 'test-utils';

import { pricingBreakupHandler } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import ScheduledBanner from 'merchant/views/Settlements/Settlements/components/ScheduledBanner';

let shouldRestrictUser = false;
let isAutomaticSettlementEnabledToggle = false;

jest.mock('merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils', () => ({
  __esModule: true,
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
}));

const mockUserReducer = {
  get isAutomaticSettlementEnabled() {
    return isAutomaticSettlementEnabledToggle;
  },
  isFeatureEnabled: (name: string) => {
    if (shouldRestrictUser) {
      return true;
    }

    if (name === 'es_on_demand_restricted') {
      return false;
    }

    return true;
  },
};

type AppProps = { openAutoModal?: boolean };

function App(props: AppProps) {
  return <ScheduledBanner {...props} />;
}

jest.mock('merchant/reducers/session.js', () => {
  return {
    __esModule: true,
    default: () => ({ user: mockUserReducer }),
  };
});

beforeEach(() => {
  server.use(pricingBreakupHandler);
});

test('should render default texts', () => {
  render(<App />, { showModal: true });

  expect(
    screen.getByText(/Get your settlements on the same day, automatically/i),
  ).toBeInTheDocument();
});

test('should open modal automatically if openAutoModal is passed', () => {
  render(<App openAutoModal />, { showModal: true });

  expect(screen.getByRole('button', { name: /Enable Same-day Settlements/i })).toBeInTheDocument();
});

test('should open modal on clicking enable now', async () => {
  render(<App />, { showModal: true });

  fireEvent.click(screen.getByRole('button', { name: 'Enable Now' }));

  // Check if modal is open
  await waitFor(() =>
    expect(
      screen.getByRole('button', { name: /Enable Same-day Settlements/i }),
    ).toBeInTheDocument(),
  );
});

test('should restrict enable now button for restricted user', async () => {
  shouldRestrictUser = true;

  render(<App />, { showModal: true });

  fireEvent.click(screen.getByRole('button', { name: 'Enable Now' }));

  await waitFor(() =>
    expect(
      screen.queryByRole('button', { name: /Enable Same-day Settlements/i }),
    ).toBeInTheDocument(),
  );
});

test('return null if AutomaticSettlementEnabledToggle is enabled', () => {
  isAutomaticSettlementEnabledToggle = true;

  render(<App />, { showModal: true });

  expect(
    screen.queryByText(/Get your settlements on the same day, automatically/i),
  ).toBeInTheDocument();
});
