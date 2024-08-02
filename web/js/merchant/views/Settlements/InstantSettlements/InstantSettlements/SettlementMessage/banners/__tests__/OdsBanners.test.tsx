import React from 'react';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { OdsBanners } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/banners/OdsBanners';
import { odsConfigGlobalLimitBreachedWithLimitHandler } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import { render, screen, server, waitFor } from 'test-utils';

const state = {
  session: {
    user: { isOndemandSettlementEnabled: true, merchant: { currency: 'INR' } },
    mode: 'live',
    org: {},
  },
};

describe('OdsBanners', () => {
  beforeEach(() => {
    queryClient.clear();
  });

  test('should render banner for ODS merchants', async () => {
    server.use(odsConfigGlobalLimitBreachedWithLimitHandler);
    render(<OdsBanners />, { initialState: state });
    await waitFor(() => {
      expect(
        screen.getByText(
          'We are temporarily limiting On-Demand Settlements due to exceptionally high usage. We understand the importance of timely settlements and regret any inconvenience this may cause. We expect this to be available the next working day.',
        ),
      ).toBeInTheDocument();
    });
  });

  test('should not render banner for non ODS merchants', async () => {
    server.use(odsConfigGlobalLimitBreachedWithLimitHandler);
    state.session.user.isOndemandSettlementEnabled = false;
    render(<OdsBanners />, { initialState: state });
    await waitFor(() => {
      expect(
        screen.queryByText(
          'We are temporarily limiting On-Demand Settlements due to exceptionally high usage. We understand the importance of timely settlements and regret any inconvenience this may cause. We expect this to be available the next working day.',
        ),
      ).not.toBeInTheDocument();
    });
  });
});
