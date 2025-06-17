import React from 'react';

import ProductFeatureRows from 'apps/pos/src/app/views/SelfServe/Catalog/ProductFeatureTable/ProductFeatureRows';
import ProductFeatureTableItem from 'apps/pos/src/app/views/SelfServe/Catalog/ProductFeatureTable/ProductFeatureTableItem';
import {
  MOCK_FEATURE_SCHEMA,
  MOCK_PRODUCT_FEATURE,
  MOCK_USER,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { render, screen, waitForElementToBeRemoved, server } from 'test-utils';

jest.mock('@razorpay/blade/components', () => ({
  ...(jest.requireActual('@razorpay/blade/components') as Record<string, unknown>),
  CheckIcon: () => <span>Check Icon</span>,
  CloseIcon: () => <span>Close Icon</span>,
}));

jest.mock('apps/pos/src/app/views/SelfServe/constants', () => {
  const actual = jest.requireActual('apps/pos/src/app/views/SelfServe/constants') as Record<
    string,
    string
  >;
  const mocks = jest.requireActual('apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures');
  const mockProduct = mocks.MOCK_PRODUCT;
  return {
    ...actual,
    PRODUCT_DESCRIPTIONS: {
      'mock-product': mockProduct,
    },
    PRODUCT_TABLE_LIST: {
      features: mocks.MOCK_FEATURE_SCHEMA,
      products: [mocks.MOCK_PRODUCT_FEATURE],
    },
  };
});

const renderApp = (children) => {
  render(<PosDeviceStoreProvider user={MOCK_USER}>{children}</PosDeviceStoreProvider>);
};

describe('<ProductFeatureTableItem/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler());
  });
  const props = {
    schema: MOCK_FEATURE_SCHEMA,
    isColumn: true,
    isExpanded: false,
    collapsibleIndex: 2,
  };

  test('should render heading for columns', async () => {
    renderApp(<ProductFeatureTableItem {...props} />);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('Choose the best devices for your business')).toBeVisible();
  });

  test('should render Product name and image', async () => {
    const newProps = {
      ...props,
      isColumn: false,
      product: MOCK_PRODUCT_FEATURE,
    };
    renderApp(<ProductFeatureTableItem {...newProps} />);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('Mock Product')).toBeVisible();
    expect(screen.getByAltText('Product image')).toBeVisible();
  });

  test('should not render features if not expanded and > collapsible index', async () => {
    const newProps = {
      ...props,
      isColumn: false,
      product: MOCK_PRODUCT_FEATURE,
    };
    renderApp(<ProductFeatureTableItem {...newProps} />);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('Barcode Scanner')).not.toBeVisible();
    expect(screen.getAllByTestId('non-collapsible-item-divider').length).toBe(1);
  });

  test('should  render features if not expanded and > collapsible index', async () => {
    const newProps = {
      ...props,
      isColumn: false,
      isExpanded: true,
      product: MOCK_PRODUCT_FEATURE,
    };
    renderApp(<ProductFeatureTableItem {...newProps} />);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('Barcode Scanner')).toBeVisible();
    expect(screen.getByText('Paper Billing Available')).toBeVisible();
    expect(screen.getAllByTestId('non-collapsible-item-divider').length).toBe(2);
  });
});

describe('<ProductFeatureRow/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler());
  });
  test('should render column names if columns are provided', async () => {
    const props = {
      featureColumns: MOCK_FEATURE_SCHEMA[0],
      isColumn: true,
    };
    renderApp(<ProductFeatureRows {...props} />);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('Pricing Plan')).toBeVisible();
  });

  test('should render product feature props if product and props are provided', async () => {
    const props = {
      featureColumns: MOCK_FEATURE_SCHEMA[1],
      isColumn: false,
      product: MOCK_PRODUCT_FEATURE,
    };

    renderApp(<ProductFeatureRows {...props} />);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('UPI')).toBeVisible();
    expect(screen.getByText('Check Icon')).toBeVisible();

    expect(screen.getByText('Card Tap & Pay')).toBeVisible();
    expect(screen.getByText('Close Icon')).toBeVisible();
  });

  test('should render custom component if available', async () => {
    const props = {
      featureColumns: MOCK_FEATURE_SCHEMA[0],
      isColumn: false,
      product: MOCK_PRODUCT_FEATURE,
    };

    renderApp(<ProductFeatureRows {...props} />);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('Subscription Pricing:')).toBeVisible();
    expect(screen.getByText('Lifetime Pricing:')).toBeVisible();
  });
});
