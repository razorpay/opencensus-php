import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import { renderApp } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/__test__/fixtures/BankAccountsAndSettlements';
import { screen } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  isBankAccountDetailsAllowed: jest.fn().mockReturnValue(false),
  isSettlementsAllowed: jest.fn().mockReturnValue(false),
}));

describe('BankAccountsAndSettlements', () => {
  test('should render banner on bank accounts and settlements', () => {
    renderApp();
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
  });

  testBreadCrumb(renderApp, 'Bank account details', ROUTES_INFO.BANK_ACCOUNT_DETAILS);

  testConditionalLinks(renderApp, [
    ['Bank account details', 'isBankAccountDetailsAllowed', ROUTES_INFO.BANK_ACCOUNT_DETAILS],
    ['Settlement details', 'isSettlementsAllowed', ROUTES_INFO.SETTLEMENT_DETAILS],
  ]);

  test('should render BankAccountDetails component when isBankAccountDetailsAllowed', () => {
    conditionalUtils.isBankAccountDetailsAllowed.mockReturnValue(true);
    renderApp();
    expect(screen.getByText('Bank account details')).toBeInTheDocument();
  });

  test('should render BankAccountDetails component when isSettlementsAllowed', () => {
    conditionalUtils.isSettlementsAllowed.mockReturnValue(true);
    renderApp();
    expect(screen.getByText('Settlement details')).toBeInTheDocument();
  });

  describe('When account and settings revamp is not enabled', () => {
    testRedirectionWhenAccountAndSettingsIsNotEnabled(renderApp, [
      ['/profile', ROUTES_INFO.BANK_ACCOUNT_DETAILS],
      ['/profile', ROUTES_INFO.SETTLEMENT_DETAILS],
      ['/dashboard', '/bank-accounts-settlements/some-route'],
    ]);
  });
});
