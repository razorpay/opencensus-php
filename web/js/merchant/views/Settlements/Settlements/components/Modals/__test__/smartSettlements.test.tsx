import React, { useEffect } from 'react';
import { render, screen, server, waitFor, userEvent } from 'test-utils';

import { SpiltzContextState } from 'common/splitz/types';
import * as apiHandlers from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import * as modals from 'merchant_common/reducers/modals';
import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { waitForSuccessScreen, waitForOdsModal } from './mocks/fixtures/OnDemandModalV2';

jest.mock('merchant/views/Settlements/Settlements/components/Modals/OndemandModal', () => ({
  ...(jest.requireActual(
    'merchant/views/Settlements/Settlements/components/Modals/OndemandModal',
  ) as object),
  __esModule: true,
  default: () => <p>OndemandModal V1</p>,
}));

const mockGTMExpActive: any = { value: undefined };
const mockSameDaySettlementDisabledExp: any = { value: undefined };
const mockIsSmartSettlementEnabledExp: any = { value: { variables: { result: 'on' } } };

jest.mock('common/splitz', () => ({
  useSplitzService: () =>
    ({
      abExperiments: {
        capital_is_settle_now_v2: { variables: { result: 'on' } },
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

const waitForSelectPaymentModeModal = async () => {
  await waitFor(() => {
    expect(screen.getByRole('button', { name: /Instant Settlement/i })).toBeInTheDocument();
  });
};

const waitForSelectPaymentModeWithSmartSettlementModal = async () => {
  await waitFor(() => {
    expect(screen.getByRole('button', { name: /Instant Settlement/i })).toBeInTheDocument();
    expect(screen.getByText(/Smart Settlement/i)).toBeInTheDocument();
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

describe('Smart Settlement Enabled Merchants', () => {
  beforeEach(() => {
    queryClient.clear();
    server.use(
      apiHandlers.pgBalanceHandlerSmartSettlement,
      apiHandlers.pricingBreakupHandler,
      apiHandlers.odsConfigNoBreachWithLimitHandlerForSmartSettlements,
      apiHandlers.odsRestrictedConfigHandlerSmartSettlement,
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
    expect(screen.getByText(/120,000,000/i)).toBeInTheDocument(); // pg balance 120,000,000
    expect(screen.getByText(/110,000,000/i)).toBeInTheDocument(); // max limit
    expect(screen.getAllByText(/100,000,000/i)).toHaveLength(3); // avail limit - 3 = input + text + confirm modal
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    /** Amount Validation */
    const amountInput = screen.getByRole('textbox', {
      name: /how much do you want to settle now\?/i,
    });
    expect(amountInput).toHaveValue('100000000');
    await user.clear(amountInput);
    await user.clear(amountInput);
    await user.type(amountInput, '10000');
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
    await waitForSelectPaymentModeModal();

    await waitFor(() => {
      expect(screen.queryByText(/Smart Settlement/i)).not.toBeInTheDocument(); // since amount is less than 5lakh
    });

    await user.click(
      screen.getByRole('button', {
        name: /instant settlement/i,
      }),
    );

    await user.click(
      screen.getByRole('button', {
        name: /settle now/i,
      }),
    );
    // Success screen
    await waitForSuccessScreen();
    expect(screen.getByText(/10,000/i)).toBeInTheDocument();
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
  test('should allow to complete smart settlement - merchant type: MID Limit Enabled', async () => {
    const user = userEvent.setup();
    renderApp();
    await waitForOdsModal();
    /** Withdraw screen */
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    /** Amount Validation */
    const amountInput = screen.getByRole('textbox', {
      name: /how much do you want to settle now\?/i,
    });
    user.clear(amountInput);
    user.clear(amountInput);
    await user.type(amountInput, '10000000');
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
    await user.click(
      screen.getByRole('button', {
        name: /Confirm Settlement/i,
      }),
    );
    await waitForSelectPaymentModeWithSmartSettlementModal();
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Instant Settlement/i })).toBeEnabled();
    });

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Smart Settlement/i })).toBeEnabled();
    });

    await user.click(
      screen.getByRole('button', {
        name: /smart settlement/i,
      }),
    );

    await user.click(
      screen.getByRole('button', {
        name: /settle now/i,
      }),
    );
    // Success screen
    await waitForSuccessScreen();
    expect(screen.getByText(/10,000,000/i)).toBeInTheDocument();
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
  test('smart settlement should be disabled when bank timings are off and merchant should be able to use instant settlement', async () => {
    const user = userEvent.setup();
    server.use(
      apiHandlers.pgBalanceHandlerSmartSettlement,
      apiHandlers.pricingBreakupHandler,
      apiHandlers.odsConfigNoBreachWithLimitHandlerAndBankTimingsOffForSmartSettlements,
      apiHandlers.odsRestrictedConfigHandlerSmartSettlement,
    );
    renderApp();
    await waitForOdsModal();
    /** Withdraw screen */
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    /** Amount Validation */
    const amountInput = screen.getByRole('textbox', {
      name: /how much do you want to settle now\?/i,
    });
    user.clear(amountInput);
    user.clear(amountInput);
    await user.type(amountInput, '10000000');
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
    await waitForSelectPaymentModeWithSmartSettlementModal();
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Instant Settlement/i })).toBeEnabled();
    });

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Smart Settlement/i })).not.toBeEnabled();
    });

    await user.click(
      screen.getByRole('button', {
        name: /instant settlement/i,
      }),
    );

    await user.click(
      screen.getByRole('button', {
        name: /settle now/i,
      }),
    );
    // Success screen
    await waitForSuccessScreen();
    expect(screen.getByText(/10,000,000/i)).toBeInTheDocument();
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
  test('instant settlements should be disabled when entered amount is greater that 5cr', async () => {
    const user = userEvent.setup();
    renderApp();
    await waitForOdsModal();
    /** Withdraw screen */
    expect(screen.getByText('How much do you want to settle now?')).toBeInTheDocument();
    /** Amount Validation */
    const amountInput = screen.getByRole('textbox', {
      name: /how much do you want to settle now\?/i,
    });
    user.clear(amountInput);
    user.clear(amountInput);
    await user.type(amountInput, '50000001');
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
    await waitForSelectPaymentModeWithSmartSettlementModal();
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Instant Settlement/i })).not.toBeEnabled();
    });
  });
});
