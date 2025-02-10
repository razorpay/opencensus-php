import React from 'react';
import moment from 'moment';

import { OdsBanners } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/banners/OdsBanners';
import * as apiHandlers from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import {
  setEsBannerSeen,
  getEsBannerSeen,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';
import { render, screen, server, waitFor, queryClient } from 'test-utils';

const waitForLoader = async () => {
  expect(screen.getByTestId('loading')).toBeInTheDocument();
  await waitFor(() => {
    expect(screen.queryByTestId('loading')).not.toBeInTheDocument();
  });
};

const assertNoBanner = () => {
  expect(
    screen.queryByText(
      /Instant Settlements now come with a daily settlement limit. You will be able to withdraw only upto/i,
    ),
  ).not.toBeInTheDocument();
  expect(
    screen.queryByText(
      'We are temporarily limiting On-Demand Settlements due to exceptionally high usage. We understand the importance of timely settlements and regret any inconvenience this may cause. We expect this to be available the next working day.',
    ),
  ).not.toBeInTheDocument();
  expect(setEsBannerSeen).not.toBeCalled();
};

jest.mock('merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils', () => ({
  __esModule: true,
  ...(jest.requireActual(
    'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils',
  ) as object),
  setEsBannerSeen: jest.fn(),
  getEsBannerSeen: jest.fn(),
}));

const state = {
  session: {
    user: {
      isOndemandSettlementEnabled: true,
      isOndemandSettlementsRestricted: false,
      merchant: { currency: 'INR' },
    },
    mode: 'live',
    org: {},
  },
};

describe('ODS/Limit Info Banner', () => {
  beforeEach(() => {
    queryClient.clear();
    setEsBannerSeen.mockClear();
    getEsBannerSeen.mockClear();
  });

  test('should render global limit breached banner', async () => {
    server.use(apiHandlers.odsConfigGlobalLimitBreachedWithLimitHandler);
    render(<OdsBanners />, { initialState: state });
    await waitForLoader();
    await waitFor(() => {
      expect(
        screen.getByText(
          'We are temporarily limiting On-Demand Settlements due to exceptionally high usage. We understand the importance of timely settlements and regret any inconvenience this may cause. We expect this to be available the next working day.',
        ),
      ).toBeInTheDocument();
    });
  });

  test('should render merchant limit breached banner for non es restricted merchant', async () => {
    server.use(apiHandlers.odsConfigMerchantLimitBreachedHandler);
    getEsBannerSeen.mockImplementation(() => undefined);
    render(<OdsBanners />, { initialState: state });
    await waitForLoader();
    await waitFor(() => {
      expect(
        screen.getByText(
          /Instant Settlements now come with a daily settlement limit. You will be able to withdraw only upto/i,
        ),
      ).toBeInTheDocument();
    });
    expect(screen.getByText(/100/i)).toBeInTheDocument();
    expect(screen.getByText(/Learn More/i)).toBeInTheDocument();
    await waitFor(() => {
      expect(setEsBannerSeen).toBeCalledTimes(1);
    });
  });

  test('should not render merchant limit breached banner after 3 days of showing banner for non es restricted merchant', async () => {
    server.use(apiHandlers.odsConfigMerchantLimitBreachedHandler);
    getEsBannerSeen.mockImplementation(() => moment().subtract(4, 'days').toISOString());
    render(<OdsBanners />, { initialState: state });
    await waitForLoader();
    assertNoBanner();
  });

  test('should not render merchant/global limit breached banner', async () => {
    server.use(apiHandlers.odsConfigNoBreachHandler);
    getEsBannerSeen.mockImplementation(() => undefined);
    render(<OdsBanners />, { initialState: state });
    await waitForLoader();
    assertNoBanner();
  });

  test('Edge Case: should render not merchant limit breached banner for es restricted merchant', async () => {
    server.use(apiHandlers.odsConfigMerchantLimitBreachedHandler);
    state.session.user.isOndemandSettlementsRestricted = true;
    getEsBannerSeen.mockImplementation(() => undefined);
    render(<OdsBanners />, { initialState: state });
    await waitForLoader();
    assertNoBanner();
  });

  test('Edge Case: should not render banner incase of api failure', async () => {
    server.use(apiHandlers.odsConfigErrorHandler);
    state.session.user.isOndemandSettlementsRestricted = false;
    getEsBannerSeen.mockImplementation(() => undefined);
    render(<OdsBanners />, { initialState: state });
    await waitForLoader();
    assertNoBanner();
  });
});
