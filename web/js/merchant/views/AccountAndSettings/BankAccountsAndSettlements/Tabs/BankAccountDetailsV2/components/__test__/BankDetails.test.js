import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';
import BankDetails from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BankDetails';
import { mockBankData } from './mocks/fixtures/BankDetails';

jest.mock('common/ui/Popover', () => ({
  __esModule: true,
  default: ({ children }) => <div>{children}</div>,
  PopoverBody: ({ children }) => <div>{children}</div>,
}));

const handleActionMock = jest.fn();

const defaultProps = {
  bankData: mockBankData,
  handleAction: handleActionMock,
};

const renderApp = ({ ...props } = {}) => {
  return render(<BankDetails {...defaultProps} {...props} />);
};

describe('BankAccountDetailsV2 - BankDetails', () => {
  beforeEach(() => {
    handleActionMock.mockReset();
  });

  test('should show bank details', () => {
    renderApp();
    defaultProps.bankData.forEach(({ name, value }) => {
      expect(screen.getByText(name)).toBeInTheDocument();
      expect(screen.getByText(value)).toBeInTheDocument();
    });
  });

  test('should invoke handleAction on switch toggle', async () => {
    const switchAction = {
      title: 'Switch CTA',
      isDisable: false,
    };
    renderApp({
      switchAction,
    });
    const switchCTA = screen.getByRole('button', {
      name: switchAction.title,
    });
    expect(switchCTA).toBeInTheDocument();
    await userEvent.click(switchCTA);
    await waitFor(() => {
      expect(handleActionMock).toHaveBeenCalled();
    });
  });

  describe('info popup content', () => {
    test('should show correct content when settlement is ACTIVE', () => {
      renderApp({
        bankData: [
          ...defaultProps.bankData,
          {
            id: 'settlements',
            name: 'Settlements',
            value: 'ACTIVE',
          },
        ],
      });
      expect(screen.getByText('Your payments are currently being deposited in this bank account'));
    });
    test('should show correct content when settlement is not ACTIVE', () => {
      renderApp({
        bankData: [
          ...defaultProps.bankData,
          {
            id: 'settlements',
            name: 'Settlements',
            value: 'NOT ACTIVE',
          },
        ],
      });
      expect(
        screen.getByText('Your payments are currently not being deposited in this bank account'),
      );
    });
  });
});
