import { render, screen, userEvent } from 'test-utils';
import {
  getInstrumentData,
  getAccounts,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/__tests__/mocks/fixtures';

import { GREYED, ACTIVATED } from 'merchant/views/Settings/PaymentMethods/constants';
import InstrumentRow from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/InstrumentRow';

const DETAIL_FIELDS = ['Routing Code', 'Routing Type', 'Account Number', 'Beneficiary Name'];

const renderComponent = (props = {}, initialState = {}) => {
  return render(<InstrumentRow {...props} />, { initialState });
};

describe('When account is not activated', () => {
  const data = getInstrumentData(GREYED);

  test('account details should be hidden', () => {
    renderComponent({ accounts: [], data });

    //toggle shouldn't be visible
    expect(screen.queryByTestId('toggle')).not.toBeInTheDocument();
    //account details should be hidden
    expect(screen.queryByTestId('detail-list')).not.toBeInTheDocument();
  });
});

describe('When account is activated', () => {
  const data = getInstrumentData(GREYED);
  const accounts = getAccounts();

  test('account details should be hidden initially', () => {
    renderComponent({ accounts, data, showInstrumentAction: true });

    //toggle should be visible
    expect(screen.getByTestId('toggle')).toBeInTheDocument();
    expect(screen.getByText('Account details')).toBeInTheDocument();

    //account detail container should be hidden
    expect(screen.queryByTestId('detail-list')).not.toBeInTheDocument();
  });

  test('setIsOpen should be called when toggle is clicked', async () => {
    const setIsOpen = jest.fn();

    renderComponent({ accounts, data, setIsOpen, showInstrumentAction: true });

    //toggle should be visible
    expect(screen.getByTestId('toggle')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Account details'));

    expect(setIsOpen).toHaveBeenCalled();
    expect(setIsOpen).toHaveBeenCalledWith(data.vaCurrency);
  });

  test('account details should be show when isOpen = USD', () => {
    renderComponent({ accounts, data, showInstrumentAction: true, isOpen: 'USD' });

    //toggle should be visible
    expect(screen.getByTestId('toggle')).toBeInTheDocument();
    expect(screen.getByText('Hide details')).toBeInTheDocument();

    //account detail container should be visible
    expect(screen.getByTestId('detail-list')).toBeInTheDocument();

    //all passed account details should be visible
    DETAIL_FIELDS.forEach((field) => {
      expect(screen.getByText(field)).toBeInTheDocument();
      expect(
        screen.getByText(accounts[0][field.replace(' ', '_').toLowerCase()]),
      ).toBeInTheDocument();
    });
  });

  test('account details should be hidden when isOpen = false', () => {
    renderComponent({ accounts, data, showInstrumentAction: true, isOpen: false });

    //toggle should be visible
    expect(screen.getByTestId('toggle')).toBeInTheDocument();

    //account details should be visible instead of hide details
    expect(screen.getByText('Account details')).toBeInTheDocument();
    expect(screen.queryByText('Hide details')).not.toBeInTheDocument();

    //account detail container should be hidden
    expect(screen.queryByTestId('detail-list')).not.toBeInTheDocument();

    //all passed account details should be hidden
    DETAIL_FIELDS.forEach((field) => {
      expect(screen.queryByText(field)).not.toBeInTheDocument();
      expect(
        screen.queryByText(accounts[0][field.replace(' ', '_').toLowerCase()]),
      ).not.toBeInTheDocument();
    });
  });

  test('Copy details should be visible and working when isOpen = USD', async () => {
    document.execCommand = jest.fn();
    renderComponent({ accounts, data, showInstrumentAction: true, isOpen: 'USD' });

    expect(screen.getByText('Copy Details')).toBeInTheDocument();

    //fire onclick event on copy details button
    await userEvent.click(screen.getByText('Copy Details'));
    expect(document.execCommand).toHaveBeenCalledTimes(1);
  });
});

describe('Tests for account balance component', () => {
  const data = getInstrumentData(ACTIVATED);
  const accounts = getAccounts();

  test('Account balance should be visible when enable_global_account is present and va_Currency is USD', async () => {
    renderComponent(
      { accounts, data, showInstrumentAction: true, isOpen: 'USD' },
      {
        session: {
          user: {
            tags: ['enable_global_account'],
            isAuthenticated: true,
          },
        },
      },
    );

    // This part lazy loaded, test has to wait for the component to load, hence using findByTestId.
    expect(await screen.findByTestId('account-balance-container')).toBeInTheDocument();
  });

  test('Account balance should be hidden when enable_global_account is present and va_Currency is not USD', () => {
    renderComponent({ accounts, data, showInstrumentAction: true, isOpen: 'SWIFT' });

    expect(screen.queryByTestId('account-balance-container')).not.toBeInTheDocument();
  });
});
