import React from 'react';

import OrderPricing from 'apps/pos/src/app/views/SelfServe/OrderSummary/OrderPricing';
import {
  MOCK_PRICING_WITH_PRICES,
  MOCK_PRODUCT,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { processPrecheckoutPricing } from 'apps/pos/src/app/views/SelfServe/helpers';
import {
  OrderPricing as OrderPricingType,
  ProcessedRefundObj,
  ProductPlans,
} from 'apps/pos/src/app/views/SelfServe/types';
import { render, screen, userEvent, within } from 'test-utils';

type OrderPricingProps = {
  pricing: OrderPricingType | null;
  isLoading: boolean;
  renderWithNull?: boolean;
};

const MOCK_CART_ITEMS = [
  {
    code: 'mock-product',
    quantity: 3,
    plan: 'monthly' as ProductPlans,
  },
  {
    code: 'mock-product',
    quantity: 2,
    plan: 'lifetime' as ProductPlans,
  },
];
const initProps: OrderPricingProps = {
  pricing: processPrecheckoutPricing({
    cartItems: MOCK_CART_ITEMS,
    productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
  }),
  isLoading: false,
};

const renderApp = ({ pricing, renderWithNull }: OrderPricingProps) => {
  render(<OrderPricing pricing={!renderWithNull ? pricing ?? initProps.pricing : null} />);
};

describe('<OrderPricing/>', () => {
  test('should render order pricing component on screen', () => {
    renderApp({ pricing: null, isLoading: false });
    expect(screen.getByText('Payment Details')).toBeVisible();
    expect(screen.getByText('Device charges')).toBeVisible();
    expect(screen.getByText('Total Order Price')).toBeVisible();
    expect(screen.getByText('Rental charges')).toBeVisible();
    expect(screen.getAllByText('GST @18%').length).toBe(2);
    expect(screen.getByText('Shipping')).toBeVisible();
    expect(screen.getByText('27,600')).toBeVisible();

    expect(
      screen.getByText(
        /Once your device is delivered, monthly rental charges will automatically be debited from your account./,
      ),
    ).toBeVisible();
  });

  test('should render toggle detailed device charges on screen', async () => {
    renderApp({ pricing: null, isLoading: false });
    const deviceChargesContainer = screen.getByTestId('device-charges-container');
    expect(
      within(deviceChargesContainer).queryByText('Mock Product | Monthly Plan (Qty: 3)'),
    ).toBeNull();

    await userEvent.click(screen.getByText('Device charges'));

    expect(
      within(deviceChargesContainer).getByText('Mock Product | Monthly Plan (Qty: 3)'),
    ).toBeVisible();

    expect(
      within(deviceChargesContainer).getByText('Mock Product | Lifetime Plan (Qty: 2)'),
    ).toBeVisible();

    expect(within(deviceChargesContainer).getByText('27,600')).toBeVisible();
    expect(within(deviceChargesContainer).getByText('3,600')).toBeVisible();

    await userEvent.click(screen.getByText('Device charges'));
    expect(
      within(deviceChargesContainer).queryByText('Mock Product | Monthly Plan (Qty: 3)'),
    ).toBeNull();
  });

  test('should render show rental device charges on screen', async () => {
    renderApp({ pricing: null, isLoading: false });
    const deviceChargesContainer = screen.getByTestId('rental-charges-container');
    expect(
      within(deviceChargesContainer).queryByText('Mock Product | Monthly Plan (Qty: 3)'),
    ).toBeNull();

    await userEvent.click(screen.getByText('Rental charges'));

    expect(
      within(deviceChargesContainer).getByText('Mock Product | Monthly Plan (Qty: 3)'),
    ).toBeVisible();
  });

  test('should not show rental if only lifetime product exists', () => {
    const newCartItems = [
      {
        code: 'mock-product',
        quantity: 2,
        plan: 'lifetime' as ProductPlans,
      },
    ];
    const pricing = processPrecheckoutPricing({
      cartItems: newCartItems,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });
    renderApp({ pricing, isLoading: false });
    expect(screen.queryByText('Rental charges')).toBeNull();
  });

  test('should show refund details and not show rental amount if refund obj exists', () => {
    const pricing = processPrecheckoutPricing({
      cartItems: MOCK_CART_ITEMS,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });

    const MOCK_REFUND_OBJ: ProcessedRefundObj = {
      amount: 3248,
      id: 'mock-refund-id',
      refId: 'mock-reference-id',
      status: 'processed',
    };

    const newPricing = {
      ...pricing,
      refund: MOCK_REFUND_OBJ,
    };
    renderApp({ pricing: newPricing, isLoading: false });
    expect(screen.getByText('Total Refund')).toBeVisible();
    expect(screen.queryByText('Rental charges')).toBeNull();
  });

  test('should render pricing component with prices as 0 if not pricing avaible ', () => {
    renderApp({ pricing: null, isLoading: false, renderWithNull: true });

    expect(screen.getByText('Payment Details')).toBeVisible();
    expect(screen.getByText('Device charges')).toBeVisible();
    expect(screen.getByText('Total Order Price')).toBeVisible();
  });
});
