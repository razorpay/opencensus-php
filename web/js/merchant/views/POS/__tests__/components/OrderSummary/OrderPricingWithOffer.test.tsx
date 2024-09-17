import React from 'react';

import OrderPricing from 'merchant/views/POS/OrderSummary/OrderPricing';
import {
  MOCK_PRICING_WITH_PRICES_WITH_OFFER,
  MOCK_PRODUCT,
} from 'merchant/views/POS/__tests__/mocks/fixtures';
import { processPrecheckoutPricing } from 'merchant/views/POS/helpers';
import { OrderPricing as OrderPricingType, ProductPlans } from 'merchant/views/POS/types';
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
    productDescriptions: [
      {
        ...MOCK_PRODUCT,
        rentalDiscountPeriod: 3,
        pricing: MOCK_PRICING_WITH_PRICES_WITH_OFFER,
        offer: {
          offerText: 'Mock Offer',
          pdpOfferText: 'Mock PDP offer',
          partnerOfferText: 'mock partner offer',
          partnerPdpOfferText: 'mock partner pdp offer',
        },
      },
    ],
  }),
  isLoading: false,
};

const renderApp = ({ pricing, renderWithNull }: OrderPricingProps) => {
  render(<OrderPricing pricing={!renderWithNull ? pricing ?? initProps.pricing : null} />);
};

describe('<OrderPricing/> with offer', () => {
  test('should render order pricing component on screen', () => {
    renderApp({ pricing: null, isLoading: false });
    expect(screen.getByText('Payment Details')).toBeVisible();
    expect(screen.getByText('Device charges')).toBeVisible();
    expect(screen.getByText('Total Order Price')).toBeVisible();
    expect(screen.getByText('Rental charges')).toBeVisible();
    expect(screen.getAllByText('GST @18%').length).toBe(2);
    expect(screen.getByText('Shipping')).toBeVisible();

    expect(
      screen.getByText(
        /Once your device is delivered, monthly rental charges will automatically be debited from your account./,
      ),
    ).toBeVisible();
  });

  test('should render toggle detailed device charges on screen', async () => {
    renderApp({ pricing: null, isLoading: false });
    const deviceChargesContainer = screen.getByTestId('device-charges-container');
    expect(within(deviceChargesContainer).queryByText('Mock Product')).toBeNull();

    await userEvent.click(screen.getByText('Device charges'));

    expect(within(deviceChargesContainer).getByText('Offer Applied')).toBeVisible();
    expect(within(deviceChargesContainer).getAllByText('Mock Product').length).toBe(2);
    expect(within(deviceChargesContainer).getByText('Monthly Plan | (Qty: 3)')).toBeVisible();
    expect(within(deviceChargesContainer).getByText('3,600')).toBeVisible();
    expect(within(deviceChargesContainer).getByText('3,000')).toBeVisible();
    expect(within(deviceChargesContainer).getByText(/Lifetime Plan | (Qty: 2)/)).toBeVisible();
    expect(within(deviceChargesContainer).getByText('60,000')).toBeVisible();
  });

  test('should render toggle detailed rental charges on screen', async () => {
    renderApp({ pricing: null, isLoading: false });
    const deviceChargesContainer = screen.getByTestId('rental-charges-container');
    expect(within(deviceChargesContainer).queryByText('Mock Product')).toBeNull();

    await userEvent.click(screen.getByText('Rental charges'));

    expect(within(deviceChargesContainer).getByText('Offer Applied')).toBeVisible();
    expect(
      within(deviceChargesContainer).getAllByText('Monthly Plan - Mock Product X 3').length,
    ).toBe(2);
    expect(within(deviceChargesContainer).getByText(/post 3 months/)).toBeVisible();
    expect(within(deviceChargesContainer).getByText('first 3 months')).toBeVisible();
    expect(within(deviceChargesContainer).getByText('354')).toBeVisible();
    expect(within(deviceChargesContainer).getByText('MDR (%)')).toBeVisible();
  });

  test('should render toggle detailed device charges and partner offer tag on screen', async () => {
    const pricing = processPrecheckoutPricing({
      cartItems: MOCK_CART_ITEMS,
      productDescriptions: [
        {
          ...MOCK_PRODUCT,
          pricing: MOCK_PRICING_WITH_PRICES_WITH_OFFER,
          offer: {
            offerText: 'Mock Offer',
            pdpOfferText: 'Mock PDP offer',
            partnerOfferText: 'mock partner offer',
            partnerPdpOfferText: 'mock partner pdp offer',
          },
          isPartnerPricing: true,
        },
      ],
    });
    renderApp({ pricing, isLoading: false });
    const deviceChargesContainer = screen.getByTestId('device-charges-container');
    expect(within(deviceChargesContainer).queryByText('Mock Product')).toBeNull();

    await userEvent.click(screen.getByText('Device charges'));

    expect(within(deviceChargesContainer).getByText('Partner Offer Applied')).toBeVisible();
    expect(within(deviceChargesContainer).getAllByText('Mock Product').length).toBe(2);
    expect(within(deviceChargesContainer).getByText('Monthly Plan | (Qty: 3)')).toBeVisible();
  });

  test('should render toggle detailed rental charges and partner offer tag on screen', async () => {
    const pricing = processPrecheckoutPricing({
      cartItems: MOCK_CART_ITEMS,
      productDescriptions: [
        {
          ...MOCK_PRODUCT,
          pricing: MOCK_PRICING_WITH_PRICES_WITH_OFFER,
          rentalDiscountPeriod: 3,
          offer: {
            offerText: 'Mock Offer',
            pdpOfferText: 'Mock PDP offer',
            partnerOfferText: 'mock partner offer',
            partnerPdpOfferText: 'mock partner pdp offer',
          },
          isPartnerPricing: true,
        },
      ],
    });
    renderApp({ pricing, isLoading: false });
    const deviceChargesContainer = screen.getByTestId('rental-charges-container');
    expect(within(deviceChargesContainer).queryByText('Mock Product')).toBeNull();

    await userEvent.click(screen.getByText('Rental charges'));

    expect(within(deviceChargesContainer).getByText('Partner Offer Applied')).toBeVisible();
    expect(
      within(deviceChargesContainer).getAllByText('Monthly Plan - Mock Product X 3').length,
    ).toBe(2);
    expect(within(deviceChargesContainer).getByText(/post 3 months/)).toBeVisible();
    expect(within(deviceChargesContainer).getByText('first 3 months')).toBeVisible();
    expect(within(deviceChargesContainer).getByText('354')).toBeVisible();
    expect(within(deviceChargesContainer).getByText('MDR (%)')).toBeVisible();
  });
});
