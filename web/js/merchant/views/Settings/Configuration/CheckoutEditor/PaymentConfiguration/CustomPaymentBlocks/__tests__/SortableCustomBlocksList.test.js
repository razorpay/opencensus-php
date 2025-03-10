import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { SortableCustomPaymentBlocksList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/SortableCustomBlocksList';
import { useCheckoutEditor, CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { method } from '@dashboards/payments/views/Transactions/v2/UploadInvoices/components/PaymentsTable/columns';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));
const mockUseCheckoutEditor = useCheckoutEditor;
const PREVIEW_SCREEN = {
  METHODS: 'methods',
};
const mockBlocks = [
  {
    id: '1',
    item: {
      slug: 'block1',
      name: 'Block 1',
      description: 'Description 1',
    },
  },
  {
    id: '2',
    item: {
      slug: 'block2',
      name: 'Block 2',
      description: 'Description 2',
    },
  },
];

describe('SortableCustomPaymentBlocksList', () => {
  beforeEach(() => {
    const updateSortableListMock = jest.fn();
    mockUseCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
          checkout_config: {
            display: {
              sequence: [],
              blocks: {
                block1: { name: 'Block 1' },
                block2: { name: 'Block 2' },
              },
            },
          },
          [CHECKOUT_EDITOR_FIELDS.CURRENT_EXPANDED_CUSTOM_BLOCK]: '',
        },
        handleSelectedConfigChange: jest.fn(),
        handleSelectedPaymentOptionChange: jest.fn().mockReturnValue({
          name: 'Block 1',
          isCustomBlock: true,
        }),
        handlePreviewScreenChange: jest.fn().mockReturnValue({ method: 'methods' }),
        handleCurrentExpandedCustomBlockChange: jest.fn(),
        handleCustomBlockClick: jest.fn(),
        updateSortableList: updateSortableListMock,
      }
    });
  });


  test('should render the list of custom payment blocks', () => {
    render(<SortableCustomPaymentBlocksList list={mockBlocks} newBlockKey="newBlock" />);
    expect(screen.getByText('Block 1')).toBeInTheDocument();
    expect(screen.getByText('Block 2')).toBeInTheDocument();
  });

  test('should call updateSortableList when the list is updated', () => {
    const { getByText } = render(<SortableCustomPaymentBlocksList list={mockBlocks} newBlockKey="newBlock" />);
    fireEvent.click(getByText('Block 1'));
    expect(mockUseCheckoutEditor().handleSelectedPaymentOptionChange);
  });

  test('should call handleCustomBlockClick when a block is clicked', () => {
    const { getByText } = render(<SortableCustomPaymentBlocksList list={mockBlocks} newBlockKey="newBlock" />);
    fireEvent.click(getByText('Block 1'));
    expect(mockUseCheckoutEditor().handleSelectedPaymentOptionChange);
  });
});