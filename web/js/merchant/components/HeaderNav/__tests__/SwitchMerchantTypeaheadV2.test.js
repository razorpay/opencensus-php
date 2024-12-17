import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import SwitchMerchantTypeaheadV2 from 'merchant/components/HeaderNav/SwitchMerchantTypeaheadV2';

const initProps = {
  onSwitchMerchant: jest.fn(),
  isOpen: true,
  onDismiss: jest.fn(),
};

const INIT_STATE = {
  session: {
    user: {
      current: {},
      merchants: {
        mid1: { id: 1, display_name: 'Razorpay', name: 'Razorpay' },
        mid2: { id: 2, display_name: 'SSAB-FE', name: 'Razorpay SSAB-fe' },
        mid3: { id: 3, display_name: '', name: '' },
        mid4: { id: 3, display_name: '', name: '' },
        mid5: { id: 3, display_name: '', name: '' },
      },
    },
  },
};

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <SwitchMerchantTypeaheadV2 {...initProps} {...props} />
    </Provider>,
  );
};
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      create_merchant_cta: {
        variables: {
          result: 'on',
        },
      },
    },
  }),
  withSplitzService: jest.fn(),
}));

Object.defineProperty(window, 'RAZORPAY_ACCOUNTS_URL', {
  value: 'https://accounts.np.razorpay.in',
  writable: false,
});

describe('testing switch merchant revamp', () => {
  test('component should render properly', () => {
    renderApp();
    expect(screen.getByText('Switch Merchant')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Search Merchant')).toBeInTheDocument();
    expect(screen.getByText('Razorpay')).toBeInTheDocument();
    expect(screen.getByText('SSAB-FE')).toBeInTheDocument();
    expect(screen.getByText('Existing Account 1')).toBeInTheDocument();
    expect(screen.getByText('Existing Account 3')).toBeInTheDocument();
  });

  test('should be able to write in search input', async () => {
    renderApp();
    const searchInput = screen.getByPlaceholderText('Search Merchant');
    await userEvent.type(searchInput, 'SSAB-FE');
    expect(screen.queryByText('Razorpay')).not.toBeInTheDocument();
  });

  test('should show all merchants if the search input is empty', async () => {
    renderApp();
    const searchInput = screen.getByPlaceholderText('Search Merchant');
    await userEvent.type(searchInput, 'SSAB-FE');
    expect(screen.queryByText('Razorpay')).not.toBeInTheDocument();
    await userEvent.clear(searchInput);
    expect(screen.queryByText('Razorpay')).toBeInTheDocument();
    expect(screen.queryByText('SSAB-FE')).toBeInTheDocument();
  });

  test('should redirect to usl when create new account button is clicked', async () => {
    renderApp();
    const createNewAccountButton = screen.getByTestId('create-new-account-cta');
    expect(createNewAccountButton).toBeInTheDocument();
    expect(createNewAccountButton).toHaveAttribute(
      'href',
      'https://accounts.np.razorpay.in/merchants/new',
    );
  });
});
