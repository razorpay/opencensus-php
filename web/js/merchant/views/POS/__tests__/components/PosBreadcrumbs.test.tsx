import React from 'react';
import * as reactRouter from 'react-router-dom';

import PosBreadcrumbs from 'merchant/views/POS/PosBreadcrumbs';
import { render, screen } from 'test-utils';

const MOCK_LOCATION = {
  key: '',
  pathname: '',
  hash: '',
  search: '',
  state: {},
};

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useLocation: jest.fn(),
}));

jest.mock('merchant/views/POS/constants', () => {
  const actual = jest.requireActual('merchant/views/POS/constants') as Record<string, string>;
  const mockProduct = jest.requireActual(
    'merchant/views/POS/__tests__/mocks/fixtures',
  ).MOCK_PRODUCT;

  return {
    ...actual,
    PRODUCT_DESCRIPTIONS: {
      'mock-product': mockProduct,
    },
    ROUTE_PATTERNS: [
      {
        path: '/pos/catalog/mock-product',
        steps: [
          {
            link: '/pos/catalog',
            label: 'Catalog',
          },
          {
            link: () => `/pos/catalog/mock-product`,
            label: () => 'Mock Product',
          },
        ],
      },
    ],
  };
});

describe('<PosBreadcrumbs/>', () => {
  test('should render breadcrumbs component on screen', () => {
    const locationSpy = jest.spyOn(reactRouter, 'useLocation');
    locationSpy.mockReturnValue({
      ...MOCK_LOCATION,
      pathname: '/pos/catalog/mock-product',
    });
    render(<PosBreadcrumbs />);
    expect(screen.getByText('Catalog')).toBeVisible();
    expect(screen.getByText('Mock Product')).toBeVisible();
  });

  test('should render breadcrumbs component and take path from location state on screen', () => {
    const locationSpy = jest.spyOn(reactRouter, 'useLocation');
    locationSpy.mockReturnValue({
      ...MOCK_LOCATION,
      state: {
        breadcrumbsPath: '/pos/catalog/mock-product',
      },
    });
    render(<PosBreadcrumbs />);
    expect(screen.getByText('Catalog')).toBeVisible();
    expect(screen.getByText('Mock Product')).toBeVisible();
  });

  test('should not render breadcrumbs component if mapping not available ', () => {
    const locationSpy = jest.spyOn(reactRouter, 'useLocation');
    locationSpy.mockReturnValue({
      ...MOCK_LOCATION,
      state: {
        breadcrumbsPath: '/pos/some-random-route',
      },
    });
    render(<PosBreadcrumbs />);
    expect(screen.queryByText('Catalog')).toBeNull();
  });
});
