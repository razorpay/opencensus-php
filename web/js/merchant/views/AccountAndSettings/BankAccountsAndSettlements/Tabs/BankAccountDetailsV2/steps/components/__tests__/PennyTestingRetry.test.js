import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { render, screen, userEvent } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form',
  () => ({
    __esModule: true,
    default: () => <div>Bank Account Form</div>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/UploadProofs',
  () => ({
    __esModule: true,
    default: () => <div>Upload Documents Flow</div>,
  }),
);

describe('PennytestingRetry', () => {
  const renderApp = ({ props } = {}) => {
    return render(
      <BankAccountUpdateFlow
        defaultView={BANK_ACCOUNT_UPDATE_STEPS.PENNY_TESTING_RETRY}
        {...props}
      />,
    );
  };

  test('should render penny testing heading and description', () => {
    renderApp();
    expect(screen.getByText('Couldn’t verify bank account')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Something went wrong while automatically verifying your details. Check details and try again',
      ),
    ).toBeInTheDocument();
  });

  test('should render penny testing timeline notice', () => {
    renderApp();
    expect(screen.getByText('We’ll manually verify your details in 2-3 days')).toBeInTheDocument();
  });

  describe('PennytestingRetryActions', () => {
    test('should render bank account update form on clicking retry action', async () => {
      renderApp();
      const retryAction = screen.getByRole('button', {
        name: 'Try again',
      });
      await userEvent.click(retryAction);
      expect(screen.getByText('Bank Account Form')).toBeInTheDocument();
    });

    test('should render bank account upload proofs flow on clicking upload action', async () => {
      renderApp();
      expect(screen.getByText('Upload bank account proof')).toBeInTheDocument();
      const uploadAction = screen.getByRole('button', {
        name: 'Upload bank account proof',
      });
      await userEvent.click(uploadAction);
      expect(screen.getByText('Upload Documents Flow')).toBeInTheDocument();
    });
  });
});
