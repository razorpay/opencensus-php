import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import AccountSection from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/AccountSection/AccountSection';
import {
  ActiveAccountDataOHS,
  NoBankAccountData,
  PreviousAccountData,
} from './mocks/fixtures/AccountSection';
import { action } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/constants/data';
import { getDefaultUserObj } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/User';

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BankDetails/BankDetails.tsx',
  () => () => <div>BankDetails Component</div>,
);

const handleActionMock = jest.fn();

const defaultProps = {
  isCtaAction: true,
  isCollapsible: true,
  accountData: ActiveAccountDataOHS,
};

const defaultInitialState = {
  app: {
    isMobileResolution: true,
  },
};

const renderApp = ({ initialState = defaultInitialState, ...props } = {}) => {
  return render(<AccountSection handleAction={handleActionMock} {...defaultProps} {...props} />, {
    initialState,
  });
};

describe('BankAccountDetailsV2 - AccountSection', () => {
  beforeEach(() => {
    handleActionMock.mockReset();
  });

  test('should render Account Section component', () => {
    renderApp();
    expect(screen.getByText(defaultProps.accountData.title)).toBeInTheDocument();
    expect(screen.getByText(defaultProps.accountData.description)).toBeInTheDocument();
  });

  test('should invoke handleAction on Change Bank Account CTA click', async () => {
    renderApp();
    const ChangeBankAccountCTA = screen.getByRole('button', {
      name: 'Change bank account',
    });
    expect(ChangeBankAccountCTA).toBeInTheDocument();
    await userEvent.click(ChangeBankAccountCTA);
    expect(handleActionMock).toHaveBeenCalledWith({ flowType: 'update' });
  });

  test('should show collapse button when it is isCollapsible and on click it should show bank details', async () => {
    renderApp({
      isCollapsible: true,
      switchAction: action,
    });
    const collapseCta = screen.getByTestId('collapse-btn');
    await userEvent.click(collapseCta);
    expect(screen.getByText('BankDetails Component')).toBeInTheDocument();
  });

  test('should show Change Account CTA on dweb when is not collapsed', () => {
    renderApp({
      isCtaAction: true,
      isCollapsible: false,
      initialState: {
        app: {
          isMobileResolution: false,
        },
      },
    });
    const ChangeBankAccountCTA = screen.getByRole('button', {
      name: 'Change bank account',
    });
    expect(ChangeBankAccountCTA).toBeInTheDocument();
  });

  describe('BankDetails', () => {
    test('should show bankdetails when bank data is available', () => {
      renderApp({
        accountData: ActiveAccountDataOHS,
      });
      expect(screen.getByText('BankDetails Component')).toBeInTheDocument();
    });

    test('should show multiple bank details when there are more than one', () => {
      renderApp({ isCtaAction: false, isCollapsible: false, accountData: PreviousAccountData });
      expect(screen.getAllByText('BankDetails Component')).toHaveLength(3);
    });

    test('should show no bank account available when there is no bank data', () => {
      renderApp({
        accountData: NoBankAccountData,
      });
      expect(screen.getByText('NO BANK ACCOUNT AVAILABLE')).toBeInTheDocument();
    });
  });
});

describe('Update Bank Account CTA', () => {
  test('should not show update bank account button for jpmc import merchants', () => {
    const tags = ['enable_jpmc_import_flow'];
    renderApp({
      accountData: ActiveAccountDataOHS,
      initialState: {
        session: {
          user: getDefaultUserObj({ tags }),
        },
      },
    });
    expect(screen.queryByText('Change bank account')).not.toBeInTheDocument();
  });

  test('should not show update bank account button for opgsp import merchants', () => {
    const tags = ['opgsp_import_flow'];
    renderApp({
      accountData: ActiveAccountDataOHS,
      initialState: {
        session: {
          user: getDefaultUserObj({ tags }),
        },
      },
    });
    expect(screen.queryByText('Change bank account')).not.toBeInTheDocument();
  });

  test('should show update bank account button for other merchants', () => {
    renderApp({
      accountData: ActiveAccountDataOHS,
    });
    expect(screen.getByText('Change bank account')).toBeInTheDocument();
  });
});
