import React from 'react';
import { render, fireEvent } from 'test-utils';
import EmiConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/EmiConfiguration/EmiConfigurationModal';
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

describe('EmiConfigurationModal', () => {
  const props = {
    isOpen: true,
    onClose: jest.fn(),
    blockName: 'testBlock',
  };

  const mockUseCheckoutEditor = {
    values: {
      [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: {},
      [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
        checkout_config: {
          display: {
            blocks: {
              testBlock: {
                instruments: [],
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

  it('renders the modal with the correct title and subtitle', () => {
    const { getByText } = render(<EmiConfigurationModal {...props} />);
    expect(getByText('EMI')).toBeInTheDocument();
    expect(getByText('Debit card, Credit card and Cashless EMI options')).toBeInTheDocument();
  });

  it('calls the onClose function when the cancel button is clicked', () => {
    const { getByText } = render(<EmiConfigurationModal {...props} />);
    const cancelButton = getByText('Cancel');
    fireEvent.click(cancelButton);
    expect(props.onClose).toHaveBeenCalledTimes(1);
  });

  it('calls the onClose function when the save button is clicked', () => {
    const { getByText, getByLabelText } = render(<EmiConfigurationModal {...props} />);
    const cardTypeCheckbox = getByLabelText(/debit/i);
    fireEvent.click(cardTypeCheckbox);
    const saveButton = getByText('Save');
    fireEvent.click(saveButton);
    expect(mockUseCheckoutEditor.handleSelectedConfigChange).toHaveBeenCalled();
    expect(props.onClose).toHaveBeenCalledTimes(1);
  });

  it('enables the save button when there are changes', () => {
    const { getByText, getByLabelText } = render(<EmiConfigurationModal {...props} />);
    const cardTypeCheckbox = getByLabelText(/debit/i);
    fireEvent.click(cardTypeCheckbox);
    const saveButton = getByText('Save');
    expect(saveButton).not.toBeDisabled();
  });

  it('updates the configuration when the save button is clicked', () => {
    const { getByText, getByLabelText } = render(<EmiConfigurationModal {...props} />);
    const cardTypeCheckbox = getByLabelText(/debit/i);
    fireEvent.click(cardTypeCheckbox);
    const saveButton = getByText('Save');
    fireEvent.click(saveButton);
    expect(mockUseCheckoutEditor.handleSelectedConfigChange).toHaveBeenCalled();
  });
});
