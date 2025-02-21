import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import UpiConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/UpiConfiguration/UpiConfigurationModal';
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

const mockUseCheckoutEditor = useCheckoutEditor;

describe('UpiConfigurationModal', () => {
  const mockOnClose = jest.fn();
  const mockHandleSelectedConfigChange = jest.fn();

  beforeEach(() => {
    mockUseCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
          checkout_config: {
            display: {
              blocks: {
                blockName: {
                  instruments: [],
                },
              },
            },
          },
        },
      },
      handleSelectedConfigChange: mockHandleSelectedConfigChange,
    });
  });

  it('should render UpiConfigurationModal component', () => {
    render(<UpiConfigurationModal isOpen={true} onClose={mockOnClose} blockName="blockName" />);
    expect(screen.getByText('UPI')).toBeInTheDocument();
    expect(screen.getByText('UPI QR Code')).toBeInTheDocument();
    expect(screen.getByText('UPI Apps')).toBeInTheDocument();
    expect(screen.getByText('UPI ID/Number')).toBeInTheDocument();
  });

  it('should call handleSave on save button click', () => {
    render(<UpiConfigurationModal isOpen={true} onClose={mockOnClose} blockName="blockName" />);
    const saveButton = screen.getByText('Save');

    const appsSwitch = screen.getByLabelText('UPI Apps switch');
    fireEvent.click(appsSwitch);

    fireEvent.click(saveButton);

    expect(mockHandleSelectedConfigChange).toHaveBeenCalled();
    expect(mockOnClose).toHaveBeenCalled();
  });
});
