import React from 'react';
import { delay, render, screen, server, waitFor } from 'test-utils';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { SpiltzContextState } from 'common/splitz/types';
import { pricingBreakupHandler } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import Upselling from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/components/Upselling';

const mockOfferExpActive: any = { variables: { result: 'on' } };
const mockOfferExpActiveWithPrice: any = { variables: { result: 'on', price: '13' } };
const mockOfferExp: any = { value: undefined };

jest.mock('common/splitz', () => ({
  useSplitzService: () =>
    ({
      abExperiments: {
        capital_is_auto_offer: mockOfferExp.value,
      },
    } as unknown as SpiltzContextState),
}));

const renderApp = () => {
  return render(<Upselling showDiscount />, {
    showModal: true,
    initialState: {
      session: {
        user: {
          isOndemandSettlementEnabled: true,
          isOndemandSettlementsRestricted: false,
          merchant: { currency: 'INR' },
          isOrgRZP: true,
        },
        mode: 'live',
        org: {},
      },
    },
  });
};

beforeEach(() => {
  queryClient.clear();
  server.use(pricingBreakupHandler);
});

afterEach(() => {
  queryClient.clear();
  mockOfferExp.value = undefined;
});

describe('Capital/IS/Upselling', () => {
  test('should render banner without pricing offer - splitzoff', async () => {
    renderApp();
    expect(screen.getByText('Did you know?')).toBeInTheDocument();
    expect(screen.getByText(/on all working days with Same-day Settlements/i)).toBeInTheDocument();
    expect(screen.getByText('Know More')).toBeInTheDocument();
    expect(screen.getByText('Enable Now')).toBeInTheDocument();
    expect(
      screen.queryByText('Discount on your Instant Settlements fee, forever!'),
    ).not.toBeInTheDocument();
    await delay(); // delay added to ensure api call was not invoked
    expect(
      screen.queryByText('Discount on your Instant Settlements fee, forever!'),
    ).not.toBeInTheDocument();
  });

  test('should render banner with pricing offer - splitzon and no price', async () => {
    mockOfferExp.value = mockOfferExpActive;
    renderApp();
    expect(screen.getByText('Did you know?')).toBeInTheDocument();
    expect(
      screen.queryByText('Discount on your Instant Settlements fee, forever!'),
    ).not.toBeInTheDocument();
    await delay(); // delay added to ensure api call was resolved
    expect(
      screen.queryByText('Discount on your Instant Settlements fee, forever!'),
    ).not.toBeInTheDocument();
  });

  test('should render banner with pricing offer - splitzon and with price', async () => {
    mockOfferExp.value = mockOfferExpActiveWithPrice;
    renderApp();
    expect(screen.getByText('Did you know?')).toBeInTheDocument();
    expect(
      screen.queryByText('Discount on your Instant Settlements fee, forever!'),
    ).not.toBeInTheDocument();
    await waitFor(() => {
      expect(
        screen.getByText('Discount on your Instant Settlements fee, forever!'),
      ).toBeInTheDocument();
    });
    expect(screen.getByText('-59%')).toBeInTheDocument();
    expect(screen.getByText('0.32%')).toBeInTheDocument();
    expect(screen.getByText('0.13% / settlement')).toBeInTheDocument();
  });
});
