import React from 'react';
import { render, fireEvent } from 'test-utils';
import PayLaterConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/PayLaterConfiguration/PayLaterConfigurationModal';
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

describe('PayLaterConfigurationModal', () => {
  const onClose = jest.fn();
  const isOpen = true;
  const blockName = 'testBlock';
  const providers = [
    { name: 'Provider 1', providers: ['Bank 1', 'Bank 2'] },
    { name: 'Provider 2', providers: ['Bank 3', 'Bank 4'] },
    { name: 'Provider 3', providers: ['Bank 5', 'Bank 6'] },
  ];

  const mockUseCheckoutEditor = {
    values: {
      [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: {
        providers,
      },
      [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
        checkout_config: {
          display: {
            blocks: {
              [blockName]: {
                instruments: [{ method: 'paylater' }],
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
      <PayLaterConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    expect(getByText('PayLater')).toBeInTheDocument();
    expect(getByText('Buy now, pay with e-pay later')).toBeInTheDocument();
  });

  it('should render footer with cancel and save buttons', () => {
    const { getByText } = render(
      <PayLaterConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    expect(getByText('Cancel')).toBeInTheDocument();
    expect(getByText('Save')).toBeInTheDocument();
  });

  it('should call onClose when cancel button is clicked', () => {
    const { getByText } = render(
      <PayLaterConfigurationModal isOpen={isOpen} onClose={onClose} blockName={blockName} />,
    );
    const cancelButton = getByText('Cancel');
    fireEvent.click(cancelButton);
    expect(onClose).toHaveBeenCalledTimes(1);
  });
});
