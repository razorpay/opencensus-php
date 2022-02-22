import React from 'react';
import Nudge from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/components/Nudge';
import { render, screen } from 'test-utils';

jest.mock('merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils', () => ({
  __esModule: true,
  getEsNudgeSeen: () => false,
  getNoOfDaysAfterEsPartialEnable: () => 10,
}));

const App = (props) => {
  return <Nudge {...props} />;
};

test('test full success nudge', () => {
  render(
    <App
      user={{
        isOndemandSettlementEnabled: true,
        isOndemandSettlementsRestricted: false,
        isAutomaticSettlementEnabled: true,
      }}
    />,
  );

  expect(screen.queryByText(/You can now settle your full balance./i)).toBeInTheDocument();
  expect(screen.queryByText(/Learn More/i)).toBeInTheDocument();
});

test('test close reason modal nudge', () => {
  render(
    <App
      user={{
        isOndemandSettlementEnabled: true,
        isOndemandSettlementsRestricted: true,
        isAutomaticSettlementEnabled: true,
      }}
      closeOrigin="OnDemand"
    />,
  );

  expect(
    screen.queryByText(/You are enjoying early access to Intant Settlements./i),
  ).toBeInTheDocument();
  expect(screen.queryByText(/Learn More/i)).toBeInTheDocument();
});

test('test close partial nudge when amount is invalid (> settlableAmount)', () => {
  render(
    <App
      user={{
        isOndemandSettlementEnabled: true,
        isOndemandSettlementsRestricted: true,
        isAutomaticSettlementEnabled: true,
      }}
      amount={1500}
      settlableAmount={1000}
    />,
  );

  expect(screen.queryByText(/Why can't I settle more money?/i)).toBeInTheDocument();
});

test('test close partial nudge when amount is valid', () => {
  render(
    <App
      user={{
        isOndemandSettlementEnabled: true,
        isOndemandSettlementsRestricted: true,
        isAutomaticSettlementEnabled: true,
      }}
      amount={500}
      settlableAmount={100000}
    />,
  );

  expect(screen.queryByText(/Why can't I settle more money?/i)).not.toBeInTheDocument();
});
