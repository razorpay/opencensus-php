import React from 'react';
import { render, screen } from 'test-utils';
import { v4 as uuid } from 'uuid';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { CustomPaymentBlocks } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlocks';

jest.mock('uuid');
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));

describe('CustomPaymentBlocks', () => {
  const mockHandleCurrentExpandedCustomBlockChange = jest.fn();
  const mockHandleSelectedConfigChange = jest.fn();

  const mockValues = {
    [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
      checkout_config: {
        display: {
          sequence: [],
          blocks: {},
        },
      },
    },
  };

  beforeEach(() => {
    useCheckoutEditor.mockReturnValue({
      values: mockValues,
      handleCurrentExpandedCustomBlockChange: mockHandleCurrentExpandedCustomBlockChange,
      handleSelectedConfigChange: mockHandleSelectedConfigChange,
    });
    uuid.mockReturnValue('mock-uuid');
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should not create a new custom block if the button is not clicked', () => {
    render(<CustomPaymentBlocks />);

    expect(mockHandleSelectedConfigChange).not.toHaveBeenCalled();
    expect(mockHandleCurrentExpandedCustomBlockChange).not.toHaveBeenCalled();
  });

  test('should handle empty custom blocks list correctly', () => {
    render(<CustomPaymentBlocks />);

    expect(screen.queryByText('Block 1')).not.toBeInTheDocument();
  });

  test('should render the custom blocks list when there are custom blocks', () => {
    const customBlocks = {
      'block-1': {
        name: 'Block 1',
        instruments: [],
      },
    };

    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
          checkout_config: {
            display: {
              sequence: ['block.block-1'],
              blocks: customBlocks,
            },
          },
        },
      },
      handleCurrentExpandedCustomBlockChange: mockHandleCurrentExpandedCustomBlockChange,
      handleSelectedConfigChange: mockHandleSelectedConfigChange,
    });

    render(<CustomPaymentBlocks />);

    expect(screen.getByText('Block 1')).toBeInTheDocument();
  });
});
