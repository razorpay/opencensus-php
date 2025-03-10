import React from 'react';
import { render, fireEvent } from 'test-utils';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { SortableStandardBlocksList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/StandardPaymentBlocks/SortableStandardBlocksList';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));

const mockUseCheckoutEditor = useCheckoutEditor;

describe('SortableStandardBlocksList', () => {
  const mockHandleSelectedConfigChange = jest.fn();
  const mockHandleSelectedPaymentMethodChange = jest.fn();
  const mockHandlePreviewScreenChange = jest.fn();

  const mockValues = {
    [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
      config_id: 'test-config-id',
      checkout_config: {
        display: {
          sequence: ['block1', 'block2'],
          hide: [],
        },
      },
    },
  };

  const mockList = [
    {
      id: '1',
      item: {
        slug: 'block1',
        name: 'Block 1',
        description: 'Description 1',
        isVisible: true,
      },
    },
    {
      id: '2',
      item: {
        slug: 'block2',
        name: 'Block 2',
        description: 'Description 2',
        isVisible: false,
      },
    },
  ];

  const mockAllBlocks = [
    {
      slug: 'block1',
      name: 'Block 1',
      description: 'Description 1',
      isVisible: true,
    },
    {
      slug: 'block2',
      name: 'Block 2',
      description: 'Description 2',
      isVisible: false,
    },
  ];

  beforeEach(() => {
    mockUseCheckoutEditor.mockReturnValue({
      values: mockValues,
      handleSelectedConfigChange: mockHandleSelectedConfigChange,
      handleSelectedPaymentMethodChange: mockHandleSelectedPaymentMethodChange,
      handlePreviewScreenChange: mockHandlePreviewScreenChange,
      handleSelectedPaymentOptionChange: jest.fn(), // Add this line
    });
  });

  test('should render SortableStandardBlocksList correctly', () => {
    const { getByText } = render(
      <SortableStandardBlocksList list={mockList} allBlocks={mockAllBlocks} />,
    );
    expect(getByText('Block 1')).toBeInTheDocument();
    expect(getByText('Block 2')).toBeInTheDocument();
  });

  test('should toggle block visibility', () => {
    const { getByLabelText } = render(
      <SortableStandardBlocksList list={mockList} allBlocks={mockAllBlocks} />,
    );
    const toggle = getByLabelText('toggle-block2');
    fireEvent.click(toggle);
    expect(mockHandleSelectedConfigChange).toHaveBeenCalled();
  });
});
