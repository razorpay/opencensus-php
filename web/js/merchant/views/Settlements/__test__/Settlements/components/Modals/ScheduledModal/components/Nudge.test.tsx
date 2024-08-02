import React from 'react';
import { userEvent, render, screen } from 'test-utils';

import Nudge from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/components/Nudge';

jest.mock('merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils', () => ({
  __esModule: true,
  getEsNudgeSeen: () => false,
  getNoOfDaysAfterEsPartialEnable: () => 10,
}));

const App = (props) => {
  return <Nudge {...props} />;
};

describe('IS - Nudge', () => {
  test('should render ES full access with same-day settlements activated banner - location(ODS Modal)', async () => {
    let params;
    const mockFnc = jest.fn((data) => (params = data));
    render(
      <App
        user={{
          isOndemandSettlementEnabled: true,
          isOndemandSettlementsRestricted: false,
          isAutomaticSettlementEnabled: true,
        }}
        openModal={mockFnc}
      />,
    );
    expect(screen.getByText(/You can now settle your full balance./i)).toBeInTheDocument();
    expect(screen.getByText(/Learn More/i)).toBeInTheDocument();
    const user = userEvent.setup();
    await user.click(screen.getByText(/Learn More/i));
    expect(params.component.props.postModalType).toBe('SAMEDAY_FULL_SUCCESS_SHIFT');
    expect(params.component.props.enabled).toBe(true);
  });

  test('should not render ES full access with same-day settlements activated banner - location(ODS Modal)', () => {
    render(
      <App
        user={{
          isOndemandSettlementEnabled: true,
          isOndemandSettlementsRestricted: false,
          isAutomaticSettlementEnabled: true,
        }}
        hasMIDLevelLimit
      />,
    );
    expect(screen.queryByText(/You can now settle your full balance./i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Learn More/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Why can't I settle more money?/i)).not.toBeInTheDocument();
  });

  test('should render es restricted banner for es restriced merchants - location(close reasons modal)', async () => {
    let params;
    const mockFnc = jest.fn((data) => (params = data));
    render(
      <App
        user={{
          isOndemandSettlementEnabled: true,
          isOndemandSettlementsRestricted: true,
        }}
        closeOrigin="OnDemand"
        openModal={mockFnc}
      />,
    );

    expect(
      screen.getByText(/You are enjoying early access to Intant Settlements./i),
    ).toBeInTheDocument();
    expect(screen.getByText(/Learn More/i)).toBeInTheDocument();
    const user = userEvent.setup();
    await user.click(screen.getByText(/Learn More/i));
    expect(params.component.props.postModalType).toBe('SAMEDAY_FULL_SHIFT_PROGRESS');
    expect(params.component.props.enabled).toBe(true);
  });

  test('should render merchant level limit banner - location(close reasons modal)', async () => {
    let params;
    const mockFnc = jest.fn((data) => (params = data));
    render(
      <App
        user={{
          isOndemandSettlementEnabled: true,
          isOndemandSettlementsRestricted: false,
        }}
        closeOrigin="OnDemand"
        hasMIDLevelLimit
        openModal={mockFnc}
      />,
    );

    expect(screen.getByText(/Why can't I settle more money?/i)).toBeInTheDocument();
    const user = userEvent.setup();
    await user.click(screen.getByText(/Why can't I settle more money?/i));
    expect(params.component.props.postModalType).toBe('ODS_MERCHANT_LEVEL_LIMIT');
    expect(params.component.props.enabled).toBe(true);
  });
});
