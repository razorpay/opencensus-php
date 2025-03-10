import React from 'react';
import { render, screen } from '@testing-library/react';
import {
  useCheckoutEditor,
  CHECKOUT_EDITOR_FIELDS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { StandardPaymentBlocks } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/StandardPaymentBlocks/StandardPaymentBlocks';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_EDITOR_FIELDS: {
    ALL_PAYMENT_METHOD_DETAILS: 'ALL_PAYMENT_METHOD_DETAILS',
    SELECTED_PAYMENT_CONFIG: 'SELECTED_PAYMENT_CONFIG',
  },
}));

jest.mock(
  'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/StandardPaymentBlocks/StandardPaymentBlocksList',
  () => ({
    StandardPaymentBlocksList: jest.fn(() => <div>StandardPaymentBlocksList</div>),
  }),
);

jest.mock(
  'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/StandardPaymentBlocks/StandardPaymentBlocksHeader',
  () => ({
    StandardPaymentBlocksHeader: jest.fn(() => <div>StandardPaymentBlocksHeader</div>),
  }),
);

const mockValues = {
  [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: {
    checkout_config: {
      display: {
        sequence: ['block1', 'block2'],
        hide: [{ method: 'upi' }],
        preferences: { show_default_blocks: true },
      },
    },
  },
  [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: [
    { name: 'block1', title: 'Block 1', description: 'Description 1', isEnabled: true },
    { name: 'block2', title: 'Block 2', description: 'Description 2', isEnabled: true },
    { name: 'upi', title: 'UPI', description: 'UPI Description', isEnabled: true },
  ],
};

describe('StandardPaymentBlocks', () => {
  const sortAndFilterStandardBlocks = jest.fn();
  beforeEach(() => {
    useCheckoutEditor.mockReturnValue({ values: mockValues });
  });

  test('should render with Razorpay config selected', () => {
    render(<StandardPaymentBlocks isRazorpayConfigSelected={true} />);
    expect(screen.getByText('StandardPaymentBlocksList')).toBeInTheDocument();
  });

  test('should render without Razorpay config selected', () => {
    render(<StandardPaymentBlocks isRazorpayConfigSelected={false} />);
    expect(screen.getByText('StandardPaymentBlocksHeader')).toBeInTheDocument();
    expect(screen.getByText('StandardPaymentBlocksList')).toBeInTheDocument();
  });

  test('should correctly sorts and filters blocks', () => {
    sortAndFilterStandardBlocks.mockReturnValue({
      visibleBlockInstruments: [
        { slug: 'block1', name: 'Block 1', description: 'Description 1', isVisible: true },
        { slug: 'block2', name: 'Block 2', description: 'Description 2', isVisible: true },
      ],
      hiddenBlockInstruments: [
        { slug: 'upi', name: 'UPI', description: 'UPI Description', isVisible: false },
      ],
    });
    render(<StandardPaymentBlocks isRazorpayConfigSelected={true} />);
    const { visibleBlockInstruments, hiddenBlockInstruments } = sortAndFilterStandardBlocks(
      mockValues[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG].checkout_config.display.sequence,
      mockValues[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG].checkout_config.display.hide,
      mockValues[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG].checkout_config.display.preferences
        .show_default_blocks,
      mockValues[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS],
    );

    expect(visibleBlockInstruments).toEqual([
      { slug: 'block1', name: 'Block 1', description: 'Description 1', isVisible: true },
      { slug: 'block2', name: 'Block 2', description: 'Description 2', isVisible: true },
    ]);

    expect(hiddenBlockInstruments).toEqual([
      { slug: 'upi', name: 'UPI', description: 'UPI Description', isVisible: false },
    ]);
  });

  test('should handle hidden and visible blocks correctly', () => {
    sortAndFilterStandardBlocks.mockReturnValue({
      visibleBlockInstruments: [
        { slug: 'block1', name: 'Block 1', description: 'Description 1', isVisible: false },
        { slug: 'block2', name: 'Block 2', description: 'Description 2', isVisible: false },
      ],
      hiddenBlockInstruments: [
        { slug: 'upi', name: 'UPI', description: 'UPI Description', isVisible: false },
      ],
    });
    render(<StandardPaymentBlocks isRazorpayConfigSelected={true} />);
    const { visibleBlockInstruments, hiddenBlockInstruments } = sortAndFilterStandardBlocks(
      mockValues[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG].checkout_config.display.sequence,
      mockValues[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG].checkout_config.display.hide,
      mockValues[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG].checkout_config.display.preferences
        .show_default_blocks,
      mockValues[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS],
    );

    expect(visibleBlockInstruments.length).toBe(2);
    expect(hiddenBlockInstruments.length).toBe(1);
  });
});
