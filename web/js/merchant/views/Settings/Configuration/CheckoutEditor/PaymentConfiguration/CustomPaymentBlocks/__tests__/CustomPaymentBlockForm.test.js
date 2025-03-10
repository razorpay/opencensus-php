import React from 'react';
import { render, fireEvent, waitFor, screen } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { CustomPaymentBlockForm } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlockForm';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));

const mockUseCheckoutEditor = useCheckoutEditor;

describe('CustomPaymentBlockForm', () => {
  const defaultValues = {
    [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
      checkout_config: {
        display: {
          blocks: {
            blockKey: {
              name: 'Test Block',
              instruments: [],
            },
          },
        },
      },
    },
    [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: [],
  };

  beforeEach(() => {
    mockUseCheckoutEditor.mockReturnValue({
      values: defaultValues,
      handleSelectedConfigChange: jest.fn(),
      handleCurrentExpandedCustomBlockChange: jest.fn(),
      handleSelectedPaymentOptionChange: jest.fn(),
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render CustomPaymentBlockForm', () => {
    const { getByText } = render(<CustomPaymentBlockForm blockKey="blockKey" isNew={false} />);
    expect(getByText('Test Block')).toBeInTheDocument();
  });

  test('should handle block name update', () => {
    const handleSelectedConfigChange = jest.fn();
    mockUseCheckoutEditor.mockReturnValue({
      ...mockUseCheckoutEditor(),
      handleSelectedConfigChange,
    });

    const { getByLabelText } = render(<CustomPaymentBlockForm blockKey="blockKey" isNew={false} />);
    const input = getByLabelText('Payment block name');
    fireEvent.change(input, { target: { value: 'Updated Block' } });

    expect(handleSelectedConfigChange).toHaveBeenCalled();
  });

  test('should handle custom block form close', () => {
    const handleCurrentExpandedCustomBlockChange = jest.fn();
    const handleSelectedPaymentOptionChange = jest.fn();
    mockUseCheckoutEditor.mockReturnValue({
      ...mockUseCheckoutEditor(),
      handleCurrentExpandedCustomBlockChange,
      handleSelectedPaymentOptionChange,
    });

    const { getByLabelText } = render(<CustomPaymentBlockForm blockKey="blockKey" isNew={false} />);
    const closeButton = getByLabelText('close custom block');
    fireEvent.click(closeButton);

    waitFor(() => {
      expect(handleCurrentExpandedCustomBlockChange).toHaveBeenCalledWith('');
      expect(handleSelectedPaymentOptionChange).toHaveBeenCalledWith({
        name: 'home',
        isCustomBlock: false,
      });
    });
  });

  test('should handle delete custom block', () => {
    const handleSelectedConfigChange = jest.fn();
    const handleCurrentExpandedCustomBlockChange = jest.fn();
    const handleSelectedPaymentOptionChange = jest.fn();
    mockUseCheckoutEditor.mockReturnValue({
      ...mockUseCheckoutEditor(),
      handleSelectedConfigChange,
      handleCurrentExpandedCustomBlockChange,
      handleSelectedPaymentOptionChange,
    });

    const { getByText } = render(<CustomPaymentBlockForm blockKey="blockKey" isNew={false} />);
    const deleteButton = getByText('Delete block');
    fireEvent.click(deleteButton);

    waitFor(() => {
      expect(handleSelectedConfigChange).toHaveBeenCalled();
      expect(handleCurrentExpandedCustomBlockChange).toHaveBeenCalledWith('');
      expect(handleSelectedPaymentOptionChange).toHaveBeenCalledWith({
        name: 'home',
        isCustomBlock: false,
      });
    });
  });

  test('should handle add single instrument modal', () => {
    const { getByText } = render(<CustomPaymentBlockForm blockKey="blockKey" isNew={false} />);
    const addButton = getByText('Add a single payment instrument');
    fireEvent.click(addButton);

    waitFor(() => {
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });
});
