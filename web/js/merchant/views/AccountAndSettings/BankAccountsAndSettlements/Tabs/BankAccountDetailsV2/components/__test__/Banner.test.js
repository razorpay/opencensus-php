import { renderApp } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/__test__/mocks/fixtures/Banner';
import { screen, userEvent, waitFor } from 'test-utils';
import { BannerType } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner/config';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };

const defaultAbExperiments = {};

let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('Banner', () => {
  const maskedAccountNumber = '***789';
  const workflowEta = 'Feb 09, 2023';

  describe('when banner is under Experiment', () => {
    beforeEach(() => {
      mockAbExperiments = {
        settlements_soh_block: variantOn,
      };
    });

    afterEach(() => {
      mockAbExperiments = defaultAbExperiments;
    });

    test('should render risk_foh banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.RISK_FOH,
          settlementConfig: {
            sub_title: 'Testing Text',
          },
        },
        user: {
          isOrgRZP: true,
        },
      });

      expect(screen.getByText('Testing Text')).toBeInTheDocument();
      expect(
        screen.getByText(
          'Your settlements are on-hold as we’ve noticed unusual activity in your account',
        ),
      ).toBeInTheDocument();
    });
    test('should render soh banner with title and description - under Exp', () => {
      renderApp({
        props: {
          type: BannerType.SOH,
          settlementConfig: {
            sub_title: 'Testing Text2',
          },
        },
        user: {
          isOrgRZP: true,
        },
      });

      expect(screen.getByText('Testing Text2')).toBeInTheDocument();
      expect(
        screen.getByText(
          'Your settlements are on-hold as we’ve encountered a few issues with your given bank account',
        ),
      ).toBeInTheDocument();
    });
    test('should render Block banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.BLOCK,
          settlementConfig: {
            sub_title: 'Testing Text3',
          },
        },
        user: {
          isOrgRZP: true,
        },
      });

      expect(screen.getByText('Testing Text3')).toBeInTheDocument();
      expect(
        screen.getByText('Your settlements are on-hold as per your request'),
      ).toBeInTheDocument();
    });
  });
  describe('When banner type is unknown', () => {
    test('should render default banner with title and description', () => {
      renderApp();
      expect(screen.getByText('Banner type is not supported')).toBeInTheDocument();
      expect(screen.getByText('Provide valid banner type')).toBeInTheDocument();
    });

    test('should not render default banner when bankAccount is not present', () => {
      renderApp({ profile: { bankAccount: null } });
      expect(screen.queryByText('Banner type is not supported')).not.toBeInTheDocument();
    });
  });

  describe('When banner type is success', () => {
    test('should render success banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.SUCCESS,
        },
      });
      expect(
        screen.getByText('Your bank account change request was successful'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `Settlements are now active on your account ending with ${maskedAccountNumber}`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is active_settlement_under_review', () => {
    test('should render active_settlement_under_review banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.ACTIVE_SETTLEMENT_UNDER_REVIEW,
          workflowEta,
        },
      });
      expect(
        screen.getByText('Your bank account change request is under review'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements are currently active on your existing account ending with ${maskedAccountNumber} until then.`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is active_settlement_under_review_time_breached', () => {
    test('should render active_settlement_under_review_time_breached banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
          workflowEta,
        },
      });
      expect(
        screen.getByText('Your bank account change request is under review (Delayed)'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `This is taking more time than usual. We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements are currently active on your existing account ending with ${maskedAccountNumber} until then.`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is active_settlement_under_review_time_breached_again', () => {
    test('should render active_settlement_under_review_time_breached_again banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
        },
      });
      expect(
        screen.getByText('Your bank account change request is under review (Delayed)'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `This is taking more time than usual. We'll verify your details in a few days and share an update soon. To know more, contact our support. Please note, settlements are currently active on your existing account ending with ${maskedAccountNumber} until then.`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is inactive_settlement_under_review', () => {
    test('should render inactive_settlement_under_review banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.INACTIVE_SETTLEMENT_UNDER_REVIEW,
          workflowEta,
        },
      });
      expect(
        screen.getByText('Your bank account change request is under review'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements to your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is inactive_settlement_under_review_time_breached', () => {
    test('should render inactive_settlement_under_review_time_breached banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
          workflowEta,
        },
      });
      expect(
        screen.getByText('Your bank account change request is under review (Delayed)'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `This is taking more time than usual. We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements to your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is inactive_settlement_under_review_time_breached_again', () => {
    test('should render inactive_settlement_under_review_time_breached_again banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
        },
      });
      expect(
        screen.getByText('Your bank account change request is under review (Delayed)'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `This is taking more time than usual. We'll verify your details in a few days and share an update soon. To know more, contact our support. Please note, settlements to your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is active_settlement_nc', () => {
    test('should render active_settlement_nc banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.ACTIVE_SETTLEMENT_NC,
        },
      });
      expect(
        screen.getByText('We need a few more details for your bank account verification'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `Please note, settlements are currently active on your bank account ending with ${maskedAccountNumber}`,
        ),
      ).toBeInTheDocument();
    });

    test('should render active_settlement_nc banner with Submit details now button', async () => {
      renderApp({
        props: {
          type: BannerType.ACTIVE_SETTLEMENT_NC,
        },
      });
      const submitDetailsNow = screen.getByRole('button', {
        name: 'Submit details now',
      });
      await userEvent.click(submitDetailsNow);
      await waitFor(() => expect(screen.getByText('BankAccountUpdateFlow')).toBeInTheDocument());
    });
  });

  describe('When banner type is inactive_settlement_nc', () => {
    test('should render inactive_settlement_nc banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.INACTIVE_SETTLEMENT_NC,
        },
      });
      expect(
        screen.getByText('We need a few more details for your bank account verification'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `Please note, settlements on your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is active_settlement_rejected', () => {
    test('should render active_settlement_rejected banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.ACTIVE_SETTLEMENT_REJECTED,
        },
      });
      expect(screen.getByText('Your bank account change request is rejected')).toBeInTheDocument();
      expect(
        screen.getByText(
          `The new bank account details you submitted couldn't be verified. Check your details and submit a new bank account change request to try again. Please note, settlements are currently active on your bank account ending with ${maskedAccountNumber}`,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is inactive_settlement_rejected', () => {
    test('should render inactive_settlement_rejected banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.INACTIVE_SETTLEMENT_REJECTED,
        },
      });
      expect(screen.getByText('Your bank account change request is rejected')).toBeInTheDocument();
      expect(
        screen.getByText(
          "The new bank account details you submitted couldn't be verified. Check your details and submit a new bank account change request to try again. Please note, settlements will be on hold until your new bank account details are updated",
        ),
      ).toBeInTheDocument();
    });
  });

  describe('When banner type is complete_kyc', () => {
    test('should render inactive_settlement_rejected banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.COMPLETE_KYC,
        },
      });
      expect(
        screen.getByText('Complete your KYC to receive payments in your bank account'),
      ).toBeInTheDocument();
      expect(
        screen.getByText('Your KYC details are required to start settlements'),
      ).toBeInTheDocument();
    });

    test('should render inactive_settlement_rejected banner with Complete KYC button', async () => {
      renderApp({
        props: {
          type: BannerType.COMPLETE_KYC,
        },
      });
      const completeKyc = screen.getByRole('button', {
        name: 'Complete KYC',
      });
      await userEvent.click(completeKyc);
      // TODO: add the assertion - @Rishav
    });
  });

  describe('When banner type is risk_foh', () => {
    test('should render inactive_settlement_rejected banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.RISK_FOH,
        },
      });
      expect(
        screen.getByText('Settlements for your Razorpay account are on hold'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          'We noticed unusual activity and have paused transfer of payments to your bank account. To resume settlements, contact our support for the next steps',
        ),
      ).toBeInTheDocument();
    });

    test('should render inactive_settlement_rejected banner with contact support button', async () => {
      renderApp({
        props: {
          type: BannerType.RISK_FOH,
        },
      });
      const contactSupport = screen.getByRole('button', {
        name: 'Contact support',
      });
      await userEvent.click(contactSupport);
      expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
    });
  });

  describe('When banner type is soh', () => {
    test('should render soh banner with title and description', () => {
      renderApp({
        props: {
          type: BannerType.SOH,
        },
      });

      expect(
        screen.getByText('Update your bank account details to resume settlements'),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          "Your settlements are on-hold as we've encountered a few issues with your given bank account. Please submit a request to change your bank account at the earliest",
        ),
      ).toBeInTheDocument();
    });
  });
});
