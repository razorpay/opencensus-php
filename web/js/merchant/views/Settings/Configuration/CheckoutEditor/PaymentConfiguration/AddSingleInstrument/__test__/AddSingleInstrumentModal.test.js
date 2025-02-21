import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { AddSingleInstrumentModal } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/AddSingleInstrument/AddSingleInstrumentModal';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));

const mockOnClose = jest.fn();
const mockHandleSelectedConfigChange = jest.fn();

const mockValues = {
  [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: {},
  [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
    checkout_config: {
      display: {
        blocks: {},
      },
    },
  },
};

describe('AddSingleInstrumentModal', () => {
  beforeEach(() => {
    useCheckoutEditor.mockReturnValue({
      values: mockValues,
      handleSelectedConfigChange: mockHandleSelectedConfigChange,
    });
  });

  test('should render modal when isOpen is true', () => {
    render(<AddSingleInstrumentModal isOpen={true} onClose={mockOnClose} blockName="testBlock" />);
    expect(screen.getByText('Add a single payment instrument')).toBeInTheDocument();
  });

  test('should call close function and reset state', () => {
    render(<AddSingleInstrumentModal isOpen={true} onClose={mockOnClose} blockName="testBlock" />);
    fireEvent.click(screen.getByText('Cancel'));

    expect(mockOnClose).toHaveBeenCalled();
  });
});
