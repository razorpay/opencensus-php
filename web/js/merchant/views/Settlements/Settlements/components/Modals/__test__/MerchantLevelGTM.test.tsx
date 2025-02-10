import React, { useEffect } from 'react';
import { render, screen, server, waitFor, userEvent, queryClient } from 'test-utils';
import { SpiltzContextState } from 'common/splitz/types';
import * as apiHandlers from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import * as modals from 'merchant_common/reducers/modals';
import { waitForOdsModal } from './mocks/fixtures/OnDemandModalV2';

const mockActiveExp = { variables: { result: 'on' } };
const mockGTMExpActive: any = { value: undefined };
const mockSameDaySettlementDisabledExp: any = { value: undefined };
const mockIsSmartSettlementEnabledExp: any = { value: undefined };

jest.mock('common/splitz', () => ({
  useSplitzService: () =>
    ({
      abExperiments: {
        capital_is_gtm: mockGTMExpActive.value,
        is_managed_merchant_account: mockSameDaySettlementDisabledExp.value,
        capital_is_smart_settlement: mockIsSmartSettlementEnabledExp.value,
      },
    } as unknown as SpiltzContextState),
}));

const mockGTMSeen = { value: { isViewed: () => false, setViewed: jest.fn() } };

jest.mock('merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers', () => ({
  ...(jest.requireActual(
    'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers',
  ) as object),
  __esModule: true,
  midLimitGTMViewedStatus: mockGTMSeen.value,
}));

const TestComp = () => {
  const openOds = () => {
    modals.openModal({
      component: <OnDemandModalEntry />,
      isNew: true,
      size: 'small',
      disableClose: true,
    });
  };

  useEffect(() => {
    openOds();
  }, []);

  return null;
};

const renderApp = ({ user }: { user?: any } = {}) => {
  return render(<TestComp />, {
    showModal: true,
    initialState: {
      session: {
        user: {
          isOndemandSettlementEnabled: true,
          isOndemandSettlementsRestricted: false,
          isAutomaticSettlementEnabled: true,
          merchant: { currency: 'INR' },
          ...user,
        },
        mode: 'live',
        org: {},
      },
    },
  });
};

jest.setTimeout(20 * 1000);
/** Integration testing - PO flows */

describe('IS Merchant level limit GTM', () => {
  beforeEach(() => {
    queryClient.clear();
    server.use(
      apiHandlers.pgBalanceHandler,
      apiHandlers.pricingBreakupHandler,
      apiHandlers.odsRestrictedConfigHandler,
      apiHandlers.odsConfigNoBreachWithLimitHandler,
      apiHandlers.postODSHandler,
    );
  });
  afterEach(() => {
    mockGTMExpActive.value = undefined;
  });
  test('should render GTM when mid limit present & non ODS restricted & experiment active & has not seen GTM', async () => {
    mockGTMExpActive.value = mockActiveExp;
    const user = userEvent.setup();
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('progressbar')).toBeInTheDocument();
    });
    // Info screen
    await waitFor(() => {
      expect(screen.getByText('Reliable')).toBeInTheDocument();
    });
    expect(screen.getByText('Daily Limits')).toBeInTheDocument();
    expect(screen.getByText('Learn More')).toBeInTheDocument();
    user.click(screen.getByRole('button', { name: 'Next' }));
    // Analyzing screen
    await waitFor(() => {
      expect(screen.getByText('Analyzing your transaction history...')).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(screen.getByText('Analyzing your business details...')).toBeInTheDocument();
    });
    // Offer screen
    await waitFor(() => {
      expect(screen.getByText('Maximum Daily Withdrawal Limit')).toBeInTheDocument();
    });
    expect(screen.getByText(/39,800/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Back' })).toBeInTheDocument();
    // Hide GTM and should show amount screen
    user.click(screen.getByRole('button', { name: 'Settle now' }));
    await waitFor(() => {
      expect(screen.getByText('Instant Settlements')).toBeInTheDocument();
    });
    expect(mockGTMSeen.value.setViewed).toHaveBeenCalled();
    expect(screen.queryByText('Maximum Daily Withdrawal Limit')).not.toBeInTheDocument();
  });
  /** GTM disabled cases */
  test('should not render GTM when it was already viewed', async () => {
    mockGTMExpActive.value = mockActiveExp;
    mockGTMSeen.value.isViewed = () => true;
    renderApp();
    await waitForOdsModal();
    expect(screen.queryByText('Reliable')).not.toBeInTheDocument();
    mockGTMSeen.value.isViewed = () => false;
  });

  test('should not render GTM when exp not active', async () => {
    mockGTMExpActive.value = undefined;
    renderApp();
    await waitForOdsModal();
    expect(screen.queryByText('Reliable')).not.toBeInTheDocument();
  });

  test('should not render GTM for IS restricted merchant', async () => {
    mockGTMExpActive.value = mockActiveExp;
    renderApp({ user: { isOndemandSettlementsRestricted: true } });
    await waitForOdsModal();
    expect(screen.getByText(/Withdraw upto 60% of your balance upto/i)).toBeInTheDocument();
  });

  test('should not render GTM when mid limit not present', async () => {
    mockGTMExpActive.value = mockActiveExp;
    server.use(apiHandlers.odsConfigNoBreachHandler);
    renderApp();
    await waitForOdsModal();
    expect(screen.queryByText('Reliable')).not.toBeInTheDocument();
  });
  describe('IS Restricted merchants', () => {
    test('should render IS restricted banner', async () => {
      const user = userEvent.setup();
      renderApp({ user: { isOndemandSettlementsRestricted: true } });
      await waitForOdsModal();
      expect(screen.getByText('Current balance')).toBeInTheDocument();
      expect(
        screen.getByText(/Withdraw upto 60% of your balance upto ₹5,000/i),
      ).toBeInTheDocument();
      expect(screen.queryByText(/Remaining daily limit/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/Maximum daily withdrawal limit/i)).not.toBeInTheDocument();
      /** Open Modal */
      user.click(screen.getByText('Learn More'));
      await waitFor(() => {
        expect(
          screen.getByText(
            'You are enjoying early access to Instant Settlements and can settle a part of your balance.',
          ),
        ).toBeInTheDocument();
      });
      /** Close Modal */
      user.click(screen.getByRole('button', { name: 'Understood' }));
      await waitFor(() => {
        expect(
          screen.queryByText(
            'You are enjoying early access to Instant Settlements and can settle a part of your balance.',
          ),
        ).not.toBeInTheDocument();
      });
    });
  });
});
