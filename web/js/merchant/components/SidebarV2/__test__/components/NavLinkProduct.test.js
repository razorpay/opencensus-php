import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent } from 'test-utils';
import NavLinkProduct from 'merchant/components/SidebarV2/components/NavLinkProduct';
import { FALLBACK_PRODUCTS } from 'merchant/components/SidebarV2/utils/Fallback';
import * as showUtils from 'merchant/components/ShowWhen';

const defaultProps = {
  activeTab: 'main',
  routes: {},
};

jest.mock('merchant/components/SidebarV2/components/NavLinkItem', () => ({ title }) => (
  <>
    <div>Navigation Link Item</div>
    <span>{title}</span>
  </>
));
jest.mock('merchant/components/SidebarV2/components/Shimmer/shimmer', () => () => (
  <div>Shimmer Loading</div>
));
describe('NavLinkProduct', () => {
  const renderApp = ({ props } = {}) => render(<NavLinkProduct {...defaultProps} {...props} />, {});

  beforeEach(() => {
    jest.spyOn(showUtils, 'showWhenUtil').mockImplementation(() => true);
  });

  test('should render product sections heading', async () => {
    renderApp({
      props: {
        loading: false,
        heading: FALLBACK_PRODUCTS[0].section_name,
        products: FALLBACK_PRODUCTS[0].product_options,
      },
    });
    await waitFor(() => {
      expect(screen.getByText('PAYMENT PRODUCTS')).toBeInTheDocument();
    });
    FALLBACK_PRODUCTS[0].product_options.forEach((each) => {
      expect(screen.getByText(each.title)).toBeInTheDocument();
    });
  });

  test('should render toggle button for collapsible', async () => {
    renderApp({
      props: {
        loading: false,
        heading: FALLBACK_PRODUCTS[0].section_name,
        products: FALLBACK_PRODUCTS[0].product_options,
      },
    });
    await waitFor(() => {
      expect(
        screen.getByText(`Show all (${FALLBACK_PRODUCTS[0].product_options.length})`),
      ).toBeInTheDocument();
    });
    const toggleButton = screen.getByText(
      `Show all (${FALLBACK_PRODUCTS[0].product_options.length})`,
    );
    await userEvent.click(toggleButton);
    expect(screen.getByText(`Show less`)).toBeInTheDocument();
  });

  test('should render shimmer when in loading state', async () => {
    renderApp({
      props: {
        loading: true,
        heading: FALLBACK_PRODUCTS[0].section_name,
        products: FALLBACK_PRODUCTS[0].product_options,
      },
    });
    await waitFor(() => {
      expect(screen.getByText('Shimmer Loading')).toBeInTheDocument();
    });
  });
});
