import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettlementsBannerV2 from 'merchant/views/Settlements/components/SettlementsBannerV2';
import { render, screen, waitFor } from 'test-utils';
import userEvent from '@testing-library/user-event';
import { createMemoryHistory } from 'history';
import * as modals from 'merchant_common/reducers/modals';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import moment from 'moment/moment';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

const variantOn = { variables: { result: 'on' } };

const defaultAbExperiments = {};

let mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

jest.mock(
  'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage',
  () => () => <div>SettlementMessage</div>,
);

const state = {
  session: {
    user: {
      isActivated: true,
      isSubmitted: true,
    },
    org: {},
    mode: 'live',
  },
  home: {
    current_balance: { data: {} },
    settlement_amount: { data: {} },
  },
  settlement: {
    holidayList: { data: {} },
    config: { data: {} },
  },
  config: {
    config: {},
  },
  payments: {
    items: [],
  },
  profile: {
    bankAccountChangeStatus: false,
  },
  settlements: {
    items: [],
  },
};

describe('SettlementsBannerV2', () => {
  const modalsSpy = jest.spyOn(modals, 'closeModal');

  let history = createMemoryHistory();
  history.push = jest.fn();
  window.open = jest.fn();

  const renderApp = ({ initialState = state, ...props } = {}) =>
    render(<SettlementsBannerV2 {...props} />, {
      initialState,
      history,
    });

  beforeEach(() => {
    modalsSpy.mockClear();
    history = createMemoryHistory();
    history.push = jest.fn();
  });

  describe('under experiment', () => {
    beforeEach(() => {
      mockAbExperiments = {
        settlements_soh_block: variantOn,
      };
    });
    afterEach(() => {
      mockAbExperiments = defaultAbExperiments;
    });
    test('should render banner when in live mode and config.hold.status is true', () => {
      const initialState = {
        ...state,
        session: {
          ...state.session,
          user: {
            ...state.session.user,
            isOrgRZP: true,
          },
        },
        settlement: {
          config: {
            data: {
              config: {
                features: {
                  hold: {
                    status: true,
                    title: 'Testing title',
                  },
                },
              },
            },
          },
        },
      };
      renderApp({
        initialState,
      });

      expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
      expect(screen.getByText('Testing title')).toBeInTheDocument();
    });

    test('should render success banner when bankAccountChangeStatus is true', () => {
      const initialState = {
        ...state,
        profile: {
          bankAccountChangeStatus: true,
        },
        settlement: {
          config: {
            data: {
              config: {
                features: {
                  hold: {
                    status: true,
                    title: 'Your bank details have been successfully updated and are under review.',
                    sub_title:
                      'Settlements will be retried after your bank account has been verified.',
                  },
                },
              },
            },
          },
        },
      };

      renderApp({ initialState });

      expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
      expect(
        screen.getByText('Your bank details have been successfully updated and are under review'),
      ).toBeInTheDocument();
      expect(
        screen.getByText('Settlements will be retried after your bank account has been verified.'),
      ).toBeInTheDocument();
    });

    test('should render blocked settlements banner when isBlocked is true', () => {
      const initialState = {
        ...state,
        settlement: {
          config: {
            data: {
              config: {
                features: {
                  block: {
                    title: 'Contact support to resume settlements for your account',
                    status: true,
                  },
                },
              },
            },
          },
        },
      };

      renderApp({ initialState });
      expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();

      expect(
        screen.getByText('Contact support to resume settlements for your account'),
      ).toBeInTheDocument();
    });

    test('should render FOH banner when isFOH is true', () => {
      const initialState = {
        ...state,
        settlement: {
          config: {
            data: {
              config: {
                features: {
                  global_hold_config: {
                    title: 'Contact support to resume settlements for your account',
                    status: true,
                  },
                },
              },
            },
          },
        },
      };

      renderApp({ initialState });
      expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();

      expect(
        screen.getByText('Contact support to resume settlements for your account'),
      ).toBeInTheDocument();
    });
  });

  test('should render banner when in live mode and user KYC is under review', () => {
    const initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          activation_status: 'under_review',
          isActivated: true,
          isSubmitted: false,
        },
      },
    };
    renderApp({ initialState });
    expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
    expect(screen.getByText('Your KYC details are currently under review')).toBeInTheDocument();
    expect(
      screen.getByText(
        'We’ll verify your given KYC details in 3-4 working days, and reach out to you for any questions. Please note, you’ll be able to receive collected payments in your bank account after the KYC verification is complete',
      ),
    ).toBeInTheDocument();
  });

  test('should render banner when in live mode and user KYC needs clarification', async () => {
    const initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          activation_status: 'needs_clarification',
          isActivated: true,
          isSubmitted: false,
        },
      },
    };
    renderApp({ initialState });
    expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
    expect(
      screen.getByText('We need a few more details to complete KYC verification'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Follow the link to update the required details soon. We’ll verify your details in 3-4 working days, and reach out to you for any questions',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Submit details now/i)).toBeInTheDocument();
    userEvent.click(screen.getByText(/Submit details now/i));
    await waitFor(() => {
      expect(history.push).toHaveBeenCalledTimes(1);
      expect(history.push).toHaveBeenCalledWith(
        { hash: '', pathname: '/activation', search: '' },
        undefined,
        {},
      );
    });
  });

  test('should render banner when in live mode and user KYC needs clarification and user signupcampaign is easy', async () => {
    const initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          activation_status: 'needs_clarification',
          isActivated: true,
          isSubmitted: false,
          user: {
            ...state.session.user.user,
            signup_campaign: EASY_ONBOARDING,
          },
        },
      },
    };
    renderApp({ initialState });
    expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
    expect(
      screen.getByText('We need a few more details to complete KYC verification'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Follow the link to update the required details soon. We’ll verify your details in 3-4 working days, and reach out to you for any questions',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Submit details now/i)).toBeInTheDocument();
    userEvent.click(screen.getByText(/Submit details now/i));
    await new Promise((r) => setTimeout(r, 1000));
    await waitFor(() => {
      expect(window.open).toHaveBeenCalledWith(window.EASY_ONBOARDING_URL, '_self', 'noopener');
    });
  });

  test('should render banner when in live mode and user KYC has not been submitted', async () => {
    const initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          isActivated: true,
          isSubmitted: false,
        },
      },
    };
    renderApp({ initialState });
    expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
    expect(
      screen.getByText('Complete your KYC to receive collected payments in your bank account'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'To remove the ₹15,000 payment limit and receive collected payments in your bank account, complete your KYC. We’ll verify your given KYC details in 3-4 working days after you submit.',
      ),
    ).toBeInTheDocument();
    userEvent.click(screen.getByText(/Complete KYC/i));
    await waitFor(() => {
      expect(history.push).toHaveBeenCalledTimes(1);
      expect(history.push).toHaveBeenCalledWith(
        { hash: '', pathname: '/activation', search: '' },
        undefined,
        {},
      );
    });
  });

  test('should render nothing when in test mode and user KYC has not been submitted or is under review', () => {
    const initialState = {
      ...state,
      session: {
        mode: 'test',
        session: {
          ...state.session,
          user: {
            isSubmitted: false,
            isActivated: false,
          },
        },
      },
    };
    renderApp({ initialState });
    expect(screen.queryByLabelText('settlement-banner')).not.toBeInTheDocument();
  });

  test('should render banner when user is put on funds on hold', () => {
    window.rzpTicketSystem = true;
    const initialState = {
      ...state,
      settlement: {
        config: {
          data: {
            config: {
              features: {
                global_hold_config: {
                  status: true,
                },
              },
            },
          },
        },
      },
    };

    renderApp({ initialState });
    expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
    expect(
      screen.getByText('Contact support to resume settlements for your account'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your settlements are on-hold as we’ve noticed unusual activity in your account',
      ),
    ).toBeInTheDocument();
  });

  test("should call close modal, rzpAnalytics and createTicketEmitter when user's funds on hold", async () => {
    window.rzpTicketSystem = true;
    const initialState = {
      ...state,
      settlement: {
        config: {
          data: {
            config: {
              features: {
                global_hold_config: {
                  status: true,
                },
              },
            },
          },
        },
      },
    };
    renderApp({ initialState });
    userEvent.click(screen.getByText('Contact Support'));
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventCategory: 'Settlement Revamp',
        eventAction: 'Contact Support',
        eventLabel: `Settlements`,
      });
      expect(CreateTicketEmitter.emit).toHaveBeenCalledTimes(1);
      expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
    });
  });

  test("should not call createTicketEmitter when user's funds on hold and rzpTicketSystem is false", async () => {
    window.rzpTicketSystem = false;
    const initialState = {
      ...state,
      settlement: {
        config: {
          data: {
            config: {
              features: {
                global_hold_config: {
                  status: true,
                },
              },
            },
          },
        },
      },
    };
    renderApp({ initialState });
    userEvent.click(screen.getByText('Contact Support'));
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalledTimes(1);
      expect(CreateTicketEmitter.emit).toHaveBeenCalledTimes(0);
    });
  });

  test('should render banner when user is put on NSS hold', () => {
    const initialState = {
      ...state,
      settlement: {
        config: {
          data: {
            config: {
              features: {
                hold: {
                  status: true,
                },
              },
            },
          },
        },
      },
    };
    renderApp({ initialState });
    expect(
      screen.getByText('Update your bank account details to resume settlements'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your settlements are on-hold as we’ve encountered a few issues with your given bank account',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Update bank details')).toBeInTheDocument();

    initialState.profile.bankAccountChangeStatus = true;
    renderApp({ initialState });
    expect(screen.getAllByLabelText('settlement-banner')[1]).toBeInTheDocument();
    expect(
      screen.getByText('Your bank details have been successfully updated and are under review'),
    ).toBeInTheDocument();
    expect(
      screen.getByText('Settlements will be retried after your bank account has been verified.'),
    ).toBeInTheDocument();
  });

  test('should redirect to bank_account settings when update bank account is clicked', async () => {
    const initialState = {
      ...state,
      settlement: {
        config: {
          data: {
            config: {
              features: {
                hold: {
                  status: true,
                },
              },
            },
          },
        },
      },
      profile: {
        bankAccountChangeStatus: false,
      },
    };
    renderApp({ initialState });
    userEvent.click(screen.getByText(/Update bank details/));
    await waitFor(() => {
      expect(history.push).toHaveBeenCalledTimes(1);
      expect(history.push).toHaveBeenCalledWith(
        { hash: '', pathname: '/profile/update_bank_account', search: '' },
        undefined,
        {},
      );
    });

    initialState.session.user.isAccountAndSettingsRevampEnabled = true;
    renderApp({ initialState });
    userEvent.click(screen.getAllByText(/Update bank details/)[1]);
    await waitFor(() => {
      expect(history.push).toHaveBeenCalledTimes(2);
      expect(history.push).toHaveBeenCalledWith(
        { hash: '', pathname: '/bank-accounts-settlements/bank-account-details', search: '' },
        undefined,
        {},
      );
    });
  });

  test('should render banner when user is put on NSS block', async () => {
    window.rzpTicketSystem = true;
    const initialState = {
      ...state,
      settlement: {
        config: {
          data: {
            config: {
              features: {
                block: {
                  status: true,
                },
              },
            },
          },
        },
      },
    };
    renderApp({ initialState });
    expect(
      screen.getByText('Contact support to resume settlements for your account'),
    ).toBeInTheDocument();
    expect(
      screen.getByText('Your settlements are on-hold as per your request'),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Contact Support/i })).toBeInTheDocument();
    expect(screen.getByText('Contact Support')).toBeInTheDocument();
    userEvent.click(screen.getByText('Contact Support'));
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventCategory: 'Settlement Revamp',
        eventAction: 'Contact Support',
        eventLabel: `Settlements`,
      });
      expect(CreateTicketEmitter.emit).toHaveBeenCalledTimes(1);
      expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
    });
  });

  test('should render banner when previous settement is failed', async () => {
    window.rzpTicketSystem = true;
    const initialState = {
      ...state,
      settlements: {
        items: [
          {
            amount: 9999,
            status: 'failed',
            created_at: moment().format('X'),
          },
        ],
      },
    };
    renderApp({ initialState });
    expect(screen.getByText('Your failed settlement is being retried')).toBeInTheDocument();
    expect(
      screen.getByText(
        'We’re retrying your failed settlement as we’ve encountered a few issues. We’ll share an update with you in some time',
      ),
    ).toBeInTheDocument();

    initialState.settlements.items = [
      {
        amount: 9999,
        status: 'failed',
        created_at: moment().subtract(9, 'hours').format('X'),
      },
    ];

    renderApp({ initialState });
    expect(
      screen.getByText('Contact support to receive failed settlement of ₹99.99'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your previous settlement of ₹99.99 could not be processed as we’ve encountered a few issues',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Contact support')).toBeInTheDocument();
    userEvent.click(screen.getByText('Contact support'));
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventCategory: 'Settlement Revamp',
        eventAction: 'Contact Support',
        eventLabel: `Settlements`,
      });
      expect(CreateTicketEmitter.emit).toHaveBeenCalledTimes(1);
      expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
    });
  });

  test('should render banner when user has balance but no executions', () => {
    window.rzpTicketSystem = true;
    const initialState = {
      ...state,
      home: {
        current_balance: {
          data: {
            balance: 10099,
          },
        },
      },
    };
    renderApp({ initialState });
    expect(
      screen.getByText('Collect more payments to continue receiving settlements'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your settlements are not being processed as we’ve noticed lack of transactional activity',
      ),
    ).toBeInTheDocument();
  });

  test('should render banner when user balance < nextSettlement', () => {
    window.rzpTicketSystem = true;
    const initialState = {
      ...state,
      home: {
        current_balance: {
          data: {
            balance: 10099,
          },
        },
        settlement_amount: {
          data: {
            settlement_amount: 11999,
            next_settlement_time: moment().add(3, 'hours').format('X'),
          },
        },
      },
    };
    renderApp({ initialState });
    expect(screen.getByText('Upcoming settlement might get skipped')).toBeInTheDocument();
    expect(
      screen.getByText(
        'It might get skipped as your current balance going negative. Collect more payments to continue receiving settlements for your account',
      ),
    ).toBeInTheDocument();
  });

  test('should render settlement message when ondemand settlement enabled', () => {
    const initialState = {
      ...state,
      session: {
        user: {
          isOndemandSettlementEnabled: true,
        },
      },
    };
    renderApp({ initialState });
    expect(screen.getByLabelText('settlement-banner')).toBeInTheDocument();
    expect(screen.getByText('SettlementMessage')).toBeInTheDocument();
  });

  test('should not render banner when loading ', () => {
    const initialState = {
      ...state,
      session: {
        mode: 'live',
        session: {
          ...state.session,
          user: {
            isSubmitted: false,
            isActivated: false,
          },
        },
      },
      home: {
        current_balance: {
          data: {
            balance: 10099,
          },
        },
        settlement_amount: {
          loading: true,
          data: {
            settlement_amount: 11999,
            next_settlement_time: moment().add(3, 'hours').format('X'),
          },
        },
      },
    };
    renderApp({ initialState });
    expect(screen.queryByLabelText('settlement-banner')).not.toBeInTheDocument();
  });
});
