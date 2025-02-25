import {
  renderApp,
  trackBankAccountDetailsChangeSpy,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/__test__/fixtures/BankAccountDetails';
import { screen, waitFor, userEvent, server } from 'test-utils';
import {
  fetchBankAccountFailure,
  fetchBankAccountChangeStatusFailure,
  saveBankAccountChangesAutomateSuccessSync,
  saveBankAccountChangesAutomateSuccessAsync,
  saveBankAccountChangesAutomateSuccessAsyncTimeout,
  saveBankAccountChangesAutomateFailure,
  saveBankAccountChangesSuccess,
  saveBankAccountChangesFailure,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/__test__/fixtures/handlers';

describe('BankAccountDetails', () => {
  test('should render bank account details', async () => {
    renderApp();
    expect(screen.getByTestId('loader-dots')).toBeInTheDocument();
    await waitFor(() => expect(screen.getByText('BankAccountDetails')).toBeInTheDocument());
  });

  test('should not render bank account details when no bank account data', async () => {
    renderApp();
    server.use(fetchBankAccountFailure(), fetchBankAccountChangeStatusFailure());
    await waitFor(() =>
      expect(screen.getByText('No bank account details found!')).toBeInTheDocument(),
    );
  });

  test('should open bank account details change modal when change bank account button is clicked', async () => {
    renderApp();
    await waitFor(() => expect(screen.getByText('BankAccountDetails')).toBeInTheDocument());
    const changeBankAccountButton = screen.getByRole('button', {
      name: 'Change Bank Account',
    });
    await userEvent.click(changeBankAccountButton);
    expect(screen.getByText('BankAccountDetailsChange')).toBeInTheDocument();
  });

  test('should not render change bank account button when user is not admin or owner', async () => {
    renderApp({
      user: {
        isAdminOrOwner: false,
      },
    });
    await waitFor(() => expect(screen.getByText('BankAccountDetails')).toBeInTheDocument());
    const changeBankAccountButton = screen.queryByRole('button', {
      name: 'Change Bank Account',
    });
    expect(changeBankAccountButton).not.toBeInTheDocument();
  });

  describe('When user.bankAccountAutoUpdateOrWorkflow is true', () => {
    test('should save bank account changes when submit & verify button is clicked', async () => {
      server.use(saveBankAccountChangesAutomateSuccessSync());
      renderApp();
      await waitFor(() => expect(screen.getByText('BankAccountDetails')).toBeInTheDocument());
      const changeBankAccountButton = screen.getByRole('button', {
        name: 'Change Bank Account',
      });
      await userEvent.click(changeBankAccountButton);
      const submitAndVerifyButton = screen.getByRole('button', {
        name: 'Submit & Verify',
      });
      await userEvent.click(submitAndVerifyButton);
      await waitFor(() =>
        expect(trackBankAccountDetailsChangeSpy).toHaveBeenCalledWith({
          objectName: 'Bank Account Update Submit',
          actionName: 'Result',
          properties: {
            status: 'success',
            responseTime: expect.any(String),
            requestType: 'sync',
          },
        }),
      );
      server.use(saveBankAccountChangesAutomateSuccessAsync());
      await userEvent.click(submitAndVerifyButton);
      await waitFor(() =>
        expect(trackBankAccountDetailsChangeSpy).toHaveBeenCalledWith({
          objectName: 'Bank Account Update Submit',
          actionName: 'Result',
          properties: {
            status: 'success',
            responseTime: expect.any(String),
            requestType: 'async',
          },
        }),
      );
      server.use(saveBankAccountChangesAutomateSuccessAsyncTimeout());
      await userEvent.click(submitAndVerifyButton);
      await waitFor(() =>
        expect(trackBankAccountDetailsChangeSpy).toHaveBeenCalledWith({
          objectName: 'Bank Account Request',
          actionName: 'Timeout',
        }),
      );
    });

    test('should catch save bank account changes failure when submit & verify button is clicked', async () => {
      server.use(saveBankAccountChangesAutomateFailure());
      renderApp();
      await waitFor(() => expect(screen.getByText('BankAccountDetails')).toBeInTheDocument());
      const changeBankAccountButton = screen.getByRole('button', {
        name: 'Change Bank Account',
      });
      await userEvent.click(changeBankAccountButton);
      const submitAndVerifyButton = screen.getByRole('button', {
        name: 'Submit & Verify',
      });
      await userEvent.click(submitAndVerifyButton);
      await waitFor(() =>
        expect(trackBankAccountDetailsChangeSpy).toHaveBeenCalledWith({
          objectName: 'Bank Account Update Submit',
          actionName: 'Result',
          properties: {
            status: 'failure',
            responseTime: expect.any(String),
            errorMessage: 'Network Error',
          },
        }),
      );
    });
  });

  describe('When user.bankAccountAutoUpdateOrWorkflow is false', () => {
    test('should save bank account changes when submit & verify button is clicked', async () => {
      server.use(saveBankAccountChangesSuccess());
      renderApp({
        user: {
          bankAccountAutoUpdateOrWorkflow: () => false,
        },
      });
      await waitFor(() => expect(screen.getByText('BankAccountDetails')).toBeInTheDocument());
      const changeBankAccountButton = screen.getByRole('button', {
        name: 'Change Bank Account',
      });
      await userEvent.click(changeBankAccountButton);
      const submitAndVerifyButton = screen.getByRole('button', {
        name: 'Submit & Verify',
      });
      await userEvent.click(submitAndVerifyButton);
      await waitFor(() =>
        expect(
          screen.getByText('Bank Account change request updated succesfully.'),
        ).toBeInTheDocument(),
      );
    });

    test('should catch save bank account changes failure when submit & verify button is clicked', async () => {
      server.use(saveBankAccountChangesFailure());
      renderApp({
        user: {
          bankAccountAutoUpdateOrWorkflow: () => false,
        },
      });
      await waitFor(() => expect(screen.getByText('BankAccountDetails')).toBeInTheDocument());
      const changeBankAccountButton = screen.getByRole('button', {
        name: 'Change Bank Account',
      });
      await userEvent.click(changeBankAccountButton);
      const submitAndVerifyButton = screen.getByRole('button', {
        name: 'Submit & Verify',
      });
      await userEvent.click(submitAndVerifyButton);
      await waitFor(() => expect(screen.getByTestId('Notification--error')).toBeInTheDocument());
    });
  });
});
