import React from 'react';
import { render } from 'test-utils';
import { CustomPaymentBlocksList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlocksList';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index');

describe('CustomPaymentBlocksList', () => {
  const mockUseCheckoutEditor = useCheckoutEditor;

  beforeEach(() => {
    mockUseCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
          checkout_config: {
            display: {
              sequence: ['block-1', 'block-2'],
            },
          },
        },
      },
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render SortableCustomPaymentBlocksList with correct props', () => {
    const list = [
      { slug: 'block-1', name: 'Block 1' },
      { slug: 'block-2', name: 'Block 2' },
    ];
    const newBlockKey = 'new-block';

    const { getByText } = render(<CustomPaymentBlocksList list={list} newBlockKey={newBlockKey} />);

    expect(getByText('Block 1')).toBeInTheDocument();
    expect(getByText('Block 2')).toBeInTheDocument();
  });
});
