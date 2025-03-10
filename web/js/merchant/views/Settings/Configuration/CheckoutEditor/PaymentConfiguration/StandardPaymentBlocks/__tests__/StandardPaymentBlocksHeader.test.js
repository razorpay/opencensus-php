import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import { StandardPaymentBlocksHeader } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/StandardPaymentBlocks/StandardPaymentBlocksHeader';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));

const mockUseCheckoutEditor = useCheckoutEditor;

describe('StandardPaymentBlocksHeader', () => {
  const handleSelectedConfigChange = jest.fn();
  const selectedConfig = {
    checkout_config: {
      display: {
        sequence: ['block.card', 'block.upi'],
        hide: [],
        preferences: {
          show_default_blocks: true,
        },
      },
    },
  };

  beforeEach(() => {
    mockUseCheckoutEditor.mockReturnValue({
      values: { [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: selectedConfig },
      handleSelectedConfigChange,
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should disable all blocks when isAllBlocksVisible is true', () => {
    render(<StandardPaymentBlocksHeader isAllBlocksVisible={true} />);

    fireEvent.click(screen.getByText('Disable all'));

    expect(handleSelectedConfigChange).toHaveBeenCalledWith({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig.checkout_config.display,
          hide: [
            { method: 'upi' },
            { method: 'card' },
            { method: 'emi' },
            { method: 'netbanking' },
            { method: 'wallet' },
            { method: 'paylater' },
            { method: 'cod' },
          ],
          sequence: ['block.card', 'block.upi'],
          preferences: {
            show_default_blocks: false,
          },
        },
      },
    });
  });

  test('should enable all blocks when isAllBlocksVisible is false', () => {
    selectedConfig.checkout_config.display.hide = [
      { method: 'block.card' },
      { method: 'block.upi' },
    ];
    selectedConfig.checkout_config.display.preferences.show_default_blocks = false;

    render(<StandardPaymentBlocksHeader isAllBlocksVisible={false} />);

    fireEvent.click(screen.getByText('Enable all'));

    expect(handleSelectedConfigChange).toHaveBeenCalledWith({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig.checkout_config.display,
          hide: [],
          preferences: {
            show_default_blocks: true,
          },
        },
      },
    });
  });
});
