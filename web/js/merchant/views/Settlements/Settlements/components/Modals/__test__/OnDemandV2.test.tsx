import React, { useEffect } from 'react';
import { render, screen, server, waitFor, userEvent } from 'test-utils';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { SpiltzContextState } from 'common/splitz/types';
import * as apiHandlers from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import * as modals from 'merchant_common/reducers/modals';

jest.mock('merchant/views/Settlements/Settlements/components/Modals/OndemandModal', () => ({
  ...(jest.requireActual(
    'merchant/views/Settlements/Settlements/components/Modals/OndemandModal',
  ) as object),
  __esModule: true,
  default: () => <p>OndemandModal V1</p>,
}));

const mockActiveExp = { variables: { result: 'on' } };
const mockGTMExpActive: any = { value: undefined };

jest.mock('common/splitz', () => ({
  useSplitzService: () =>
    ({
      abExperiments: {
        capital_is_settle_now_v2: { variables: { result: 'on' } },
        capital_is_gtm: mockGTMExpActive.value,
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

const waitForOdsModal = async () => {
  await waitFor(() => {
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });
  await waitFor(() => {
    expect(screen.getByText('Instant Settlements')).toBeInTheDocument();
  });
};

const waitForConfirmModal = async () => {
  await waitFor(() => {
    expect(screen.getAllByText('Confirm Settlement')).toHaveLength(2); // CTA + Modal header
  });
};

const waitForSuccessScreen = async () => {
  await waitFor(() => {
    expect(screen.getByText(/Settlement Initiated/i)).toBeInTheDocument();
  });
};

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
/** Integration testing - PO flows */
describe('Capital/OnDemandV2', () => {
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

  test('should allow to complete instant settlement - merchant type: MID Limit Enabled', async () => {
    const user = userEvent.setup();
    renderApp();
    await waitForOdsModal();
    /** Withdraw screen */
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('Maximum daily withdrawal limit')).toBeInTheDocument();
    expect(screen.getByText('Remaining daily limit')).toBeInTheDocument();
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    expect(screen.getByText(/12,000/i)).toBeInTheDocument(); // pg balance
    expect(screen.getByText(/39,800/i)).toBeInTheDocument(); // max limit
    expect(screen.getAllByText(/2,990/i)).toHaveLength(3); // avail limit - 3 = input + text + confirm modal
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    /** Amount Validation */
    const amountInput = screen.getByRole('textbox', {
      name: /how much do you want to settle now\?/i,
    });
    expect(amountInput).toHaveValue('2990');
    await user.type(amountInput, '99');
    expect(amountInput).toHaveValue('299099');
    expect(screen.getByText('Maximum settlement amount is ₹12,000')).toBeInTheDocument();
    await user.clear(amountInput);
    await user.type(amountInput, '99');
    expect(screen.getByText('Minimum settlement amount should be ₹100')).toBeInTheDocument();
    /** Pricing Breakup */
    expect(screen.getByText('fees')).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: /show breakup/i,
      }),
    ).toBeDisabled();
    await user.type(amountInput, '9');
    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: /show breakup/i,
        }),
      ).not.toBeDisabled();
    });
    expect(screen.getByText('0.1% fees')).toBeInTheDocument();
    await user.click(
      screen.getByRole('button', {
        name: /show breakup/i,
      }),
    );
    // Confirm modal
    await user.click(
      screen.getByRole('button', {
        name: /Confirm Settlement/i,
      }),
    );
    await waitForConfirmModal();
    expect(
      screen.getByText(/Are you sure you want to proceed with the settlement of/i),
    ).toBeInTheDocument();
    await user.click(
      screen.getByRole('button', {
        name: /Yes, Settle/i,
      }),
    );
    // Success screen
    await waitForSuccessScreen();
    expect(screen.getByText(/999/i)).toBeInTheDocument();
    expect(screen.getByText(/999/i)).toBeInTheDocument();
    // close modal
    await user.click(
      screen.getAllByRole('button', {
        name: /Close/i,
      })[1],
    );
    await waitFor(() => {
      expect(
        screen.queryByRole('button', {
          name: /Close/i,
        }),
      ).not.toBeInTheDocument();
    });
  });

  test('should show cancel reason screen', async () => {
    const user = userEvent.setup();
    renderApp();
    await waitForOdsModal();
    await user.click(
      screen.getByRole('button', {
        name: /Close/i,
      }),
    );
    // Reason screen
    await waitFor(() => {
      expect(screen.getByText('Reason')).toBeInTheDocument();
    });
    await user.click(
      screen.getByRole('button', {
        name: /Go Back/i,
      }),
    );
    // Withdraw screen
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    // Reason screen
    await user.click(
      screen.getByRole('button', {
        name: /Close/i,
      }),
    );
    await user.click(
      screen.getByRole('button', {
        name: /Confirm & Close/i,
      }),
    );
    // Validations
    await waitFor(() => {
      expect(
        screen.getByText('Your feedback helps us improve. Please provide your comments.'),
      ).toBeInTheDocument();
    });
    await user.click(screen.getByText('Need more guidance with feature'));
    expect(
      screen.queryByText('Your feedback helps us improve. Please provide your comments.'),
    ).not.toBeInTheDocument();
    await user.click(screen.getByText('Other reasons'));
    expect(
      screen.getByText('Your feedback helps us improve. Please provide your comments.'),
    ).toBeInTheDocument();
    const input = screen.getByRole('textbox', { name: /write a brief description/i });
    await user.type(input, 'Test');
    expect(input).toHaveValue('Test');
    await user.click(
      screen.getByRole('button', {
        name: /Confirm & Close/i,
      }),
    );
    await waitFor(() => {
      expect(
        screen.queryByRole('button', {
          name: /Confirm & Close/i,
        }),
      ).not.toBeInTheDocument();
    });
  });

  test('should allow to complete route settlement', async () => {
    server.use(apiHandlers.routeBalanceHandler, apiHandlers.postRouteODSHandler);
    const user = userEvent.setup();
    renderApp({ user: { isOndemandRouteSettlementsEnabled: true } });
    await waitForOdsModal();
    // Withdraw screen - tabs
    expect(screen.getByRole('tab', { name: /settle to your account/i })).toBeInTheDocument();
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    await user.click(screen.getByRole('tab', { name: /Settle to linked account/i }));
    // Route tab
    expect(screen.getByText('Amount pending to be settled')).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', {
        name: /amount pending to be settled\?/i,
      }),
    ).toBeDisabled();
    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: /confirm settlement/i,
        }),
      ).not.toBeDisabled();
    });
    expect(
      screen.getByRole('textbox', {
        name: /amount pending to be settled\?/i,
      }),
    ).toHaveValue('45,690');
    await user.click(
      screen.getByRole('button', {
        name: /confirm settlement/i,
      }),
    );
    await waitForConfirmModal();
    await user.click(
      screen.getByRole('button', {
        name: /Yes, Settle/i,
      }),
    );
    await waitForSuccessScreen();
    // close modal
    await user.click(
      screen.getAllByRole('button', {
        name: /Close/i,
      })[1],
    );
    await waitFor(() => {
      expect(
        screen.queryByRole('button', {
          name: /Close/i,
        }),
      ).not.toBeInTheDocument();
    });
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

  describe('IS Merchant level limit GTM', () => {
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
  });
});
