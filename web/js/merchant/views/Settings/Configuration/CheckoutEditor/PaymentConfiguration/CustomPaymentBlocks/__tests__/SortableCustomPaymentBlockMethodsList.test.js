import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { SortableCustomPaymentBlockMethodsList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/SortableCustomPaymentBlockMethodsList';
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

describe('SortableCustomPaymentBlockMethodsList', () => {
  const mockProps = {
    blockKey: 'testBlockKey',
    list: [
      {
        id: '1',
        item: {
          slug: 'upi',
          name: 'UPI',
          description: 'UPI Payment',
          isVisible: true,
          isSingleInstrument: false,
        },
      },
      {
        id: '2',
        item: {
          slug: 'netbanking',
          name: 'Net Banking',
          description: 'Net Banking Payment',
          isVisible: false,
          isSingleInstrument: false,
        },
      },
    ],
    onUpdateSortList: jest.fn(),
  };

  const mockContextValues = {
    values: {
      [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
        checkout_config: {
          display: {
            blocks: {
              testBlockKey: {
                name: 'Test Block',
                instruments: [],
              },
            },
          },
        },
      },
    },
    handleSelectedConfigChange: jest.fn(),
    handleSelectedPaymentOptionChange: jest.fn(),
    handlePreviewScreenChange: jest.fn(),
  };

  beforeEach(() => {
    mockUseCheckoutEditor.mockReturnValue(mockContextValues);
  });

  test('should render the component', () => {
    render(<SortableCustomPaymentBlockMethodsList {...mockProps} />);
    expect(screen.getByText('UPI')).toBeInTheDocument();
    expect(screen.getByText('Net Banking')).toBeInTheDocument();
  });

  test('should handle visibility change', () => {
    render(<SortableCustomPaymentBlockMethodsList {...mockProps} />);
    const switchElement = screen.getByRole('switch', { name: 'toggle-netbanking' });
    fireEvent.click(switchElement);
    expect(mockContextValues.handleSelectedConfigChange).toHaveBeenCalled();
  });

  test('should not call onUpdateSortList when dragging starts but does not end', () => {
    render(<SortableCustomPaymentBlockMethodsList {...mockProps} />);
    const listItem = screen.getByText('UPI');
    fireEvent.dragStart(listItem);
    expect(mockProps.onUpdateSortList).not.toHaveBeenCalled();
  });

  test('should call handleSelectedConfigChange with correct arguments when visibility is toggled', () => {
    render(<SortableCustomPaymentBlockMethodsList {...mockProps} />);
    const switchElement = screen.getByRole('switch', { name: 'toggle-netbanking' });
    fireEvent.click(switchElement);
    expect(mockContextValues.handleSelectedConfigChange).toHaveBeenCalledWith({
      checkout_config: {
        display: {
          blocks: { testBlockKey: { instruments: [{ method: 'netbanking' }], name: 'Test Block' } },
        },
      },
    });
  });

  test('should not call handleSelectedPaymentOptionChange when a non-existent method is clicked', () => {
    render(<SortableCustomPaymentBlockMethodsList {...mockProps} />);
    const listItem = screen.queryByText('NonExistentMethod');
    if (listItem) {
      fireEvent.click(listItem);
    }
    expect(mockContextValues.handleSelectedPaymentOptionChange).not.toHaveBeenCalled();
  });

  test('should not call handlePreviewScreenChange when a non-existent method is clicked', () => {
    render(<SortableCustomPaymentBlockMethodsList {...mockProps} />);
    const listItem = screen.queryByText('NonExistentMethod');
    if (listItem) {
      fireEvent.click(listItem);
    }
    expect(mockContextValues.handlePreviewScreenChange).not.toHaveBeenCalled();
  });
});
