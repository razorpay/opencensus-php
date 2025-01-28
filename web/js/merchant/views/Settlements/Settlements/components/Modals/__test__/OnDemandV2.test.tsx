import React, { useEffect } from 'react';
import { render, screen, server, waitFor, userEvent } from 'test-utils';
import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { SpiltzContextState } from 'common/splitz/types';
import * as apiHandlers from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import * as modals from 'merchant_common/reducers/modals';
import { waitForSuccessScreen, waitForOdsModal } from './mocks/fixtures/OnDemandModalV2';

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

const waitForConfirmModal = async () => {
  await waitFor(() => {
    expect(screen.getAllByText('Confirm Settlement')).toHaveLength(2);
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

jest.setTimeout(20 * 1000);
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

  afterEach(() => {
    jest.restoreAllMocks();
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
    expect(screen.getByText('0.32% fees')).toBeInTheDocument();
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
  test('should show enable now cta for enabling same day settlement- merchant type: non managed merchant', async () => {
    const user = userEvent.setup();

    renderApp({
      user: {
        isAutomaticSettlementEnabled: false,
        isAutomaticSettlementRestricted: false,
        isOrgRZP: true,
      },
    });
    await waitForOdsModal();
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    const amountInput = screen.getByRole('textbox', {
      name: /how much do you want to settle now\?/i,
    });
    await user.clear(amountInput);
    await user.clear(amountInput);
    await user.type(amountInput, '999');
    expect(amountInput).toHaveValue('999');
    expect(screen.getByText('fees')).toBeInTheDocument();
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
    await waitForSuccessScreen();

    await waitFor(() => {
      expect(screen.getByText(/Enable Now/i)).toBeInTheDocument();
    });

    await user.click(screen.getByText(/Know More/i));
    await waitFor(() => {
      expect(screen.getByText(/Automate your settlements/i)).toBeInTheDocument();
    });
    expect(
      screen.getByRole('button', { name: /Enable Same-day Settlements/i }),
    ).toBeInTheDocument();

    expect(
      screen.queryByText(/Please contact your Account Manager to enable this feature./i),
    ).not.toBeInTheDocument();
  });

  test('should not show enable now cta for enabling same day settlement- merchant type:managed merchant', async () => {
    const user = userEvent.setup();
    mockSameDaySettlementDisabledExp.value = mockActiveExp;
    renderApp({
      user: {
        isAutomaticSettlementEnabled: false,
        isAutomaticSettlementRestricted: false,
        isOrgRZP: true,
      },
    });
    await waitForOdsModal();
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    const amountInput = screen.getByRole('textbox', {
      name: /how much do you want to settle now\?/i,
    });
    await user.clear(amountInput);
    await user.clear(amountInput);
    await user.type(amountInput, '999');
    expect(screen.getByText('fees')).toBeInTheDocument();
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
    await waitFor(() => {
      expect(
        screen.getByText(/Please contact your Account Manager to enable this feature./i),
      ).toBeInTheDocument();
    });

    await user.click(screen.getByText(/Know More/i));
    await waitFor(() => {
      expect(screen.getByText(/Automate your settlements/i)).toBeInTheDocument();
    });
    expect(
      screen.getByText(/Please contact your Account Manager to enable this feature./i),
    ).toBeInTheDocument();

    expect(
      screen.queryByRole('button', { name: /Enable Same-day Settlements/i }),
    ).not.toBeInTheDocument();
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
    ).toHaveValue('45,690.00');
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
});
