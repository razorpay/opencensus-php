import React from 'react';
import { render, fireEvent } from 'test-utils';
import WalletConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/WalletConfiguration/WalletConfigurationModal';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));

describe('WalletConfigurationModal', () => {
  const onClose = jest.fn();
  const isOpen = true;
  const blockName = 'testBlock';
  const wallets = [
    { code: 'amazonpay', name: 'Amazon Pay' },
    { code: 'freecharge', name: 'Freecharge' },
    { code: 'phonepe', name: 'PhonePe' },
    { code: 'paytm', name: 'PayTM' },
  ];

  const mockUseCheckoutEditor = {
    values: {
      [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: {
        wallets,
      },
      [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
        checkout_config: {
          display: {
            blocks: {
              [blockName]: {
                instruments: [{ method: 'wallet' }],
              },
            },
          },
        },
      },
    },
    handleSelectedConfigChange: jest.fn(),
  };

  beforeEach(() => {
    useCheckoutEditor.mockReturnValue(mockUseCheckoutEditor);
  });

  it('should render modal with title and subtitle', () => {
    const { getByText } = render(
      <WalletConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    expect(getByText('Wallet')).toBeInTheDocument();
    expect(getByText('Amazon Pay, Freecharge, PhonePe, PayTM,')).toBeInTheDocument();
  });

  it('should render list of wallet items', () => {
    const { getByText } = render(
      <WalletConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    wallets.forEach((wallet) => {
      expect(getByText((content) => content.includes(wallet.name))).toBeInTheDocument();
    });
  });

  it('should render footer with cancel and save buttons', () => {
    const { getByText } = render(
      <WalletConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    expect(getByText('Cancel')).toBeInTheDocument();
    expect(getByText('Save')).toBeInTheDocument();
  });

  it('should call onClose when cancel button is clicked', () => {
    const { getByText } = render(
      <WalletConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    const cancelButton = getByText('Cancel');
    fireEvent.click(cancelButton);
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('should enable the save button when there are changes', () => {
    const { getByText } = render(
      <WalletConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    const walletItem = getByText((content) => content.includes(wallets[0].name));
    fireEvent.click(walletItem);
    const saveButton = getByText('Save');
    expect(saveButton).not.toBeDisabled();
  });
});
