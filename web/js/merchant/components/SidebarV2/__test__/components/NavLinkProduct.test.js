import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent } from 'test-utils';
import NavLinkProduct from 'merchant/components/SidebarV2/components/NavLinkProduct';
import { FALLBACK_PRODUCTS } from 'merchant/components/SidebarV2/utils/Fallback';
import * as showUtils from 'merchant/components/ShowWhen';
import * as analytics from 'common/utils/analytics';
import { titleCase } from 'common/utils/rzp-utils';

const defaultProps = {
  activeTab: 'main',
  routes: {},
  user: {
    tags: [],
  },
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

const App = (props) => <NavLinkProduct {...defaultProps} {...props} />;

describe('NavLinkProduct', () => {
  const renderApp = ({ props } = {}) =>
    render(<App {...props} />, {
      initialEntries: ['/profile'],
      path: '/profile',
    });
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  beforeEach(() => {
    jest.spyOn(showUtils, 'showWhenUtil').mockImplementation(() => true);
  });

  test('should render product sections heading', async () => {
    renderApp({
      props: {
        loading: false,
        heading: FALLBACK_PRODUCTS[0].section_name,
        products: FALLBACK_PRODUCTS[0].product_options,
        section_id: FALLBACK_PRODUCTS[0].section_id,
      },
    });
    await waitFor(() => {
      expect(screen.getByText('PAYMENT PRODUCTS')).toBeInTheDocument();
    });
    FALLBACK_PRODUCTS[0].product_options.forEach((each) => {
      expect(screen.getByText(each.title)).toBeInTheDocument();
    });
  });

  test('should render toggle button for collapsible and call analytics on click', async () => {
    renderApp({
      props: {
        loading: false,
        heading: FALLBACK_PRODUCTS[0].section_name,
        products: FALLBACK_PRODUCTS[0].product_options,
      },
    });
    const productsLength = FALLBACK_PRODUCTS[0].product_options.length;

    const showAllToggleButton = await screen.findByText(`Show all (${productsLength})`);
    await userEvent.click(showAllToggleButton);
    expect(analyticsTrackMock).toHaveBeenCalledWith({
      objectName: 'sidebar',
      actionName: 'clicked',
      screen: 'My Account',
      toCleverTap: true,
      properties: {
        clickedElement: 'show all',
        section: titleCase(FALLBACK_PRODUCTS[0].section_name),
        location: 'sidebar',
        sidebar: 'v2',
      },
    });

    const showLessToggleButton = await screen.findByText('Show less');
    expect(showLessToggleButton).toBeInTheDocument();
    await userEvent.click(showLessToggleButton);
    expect(analyticsTrackMock).toHaveBeenLastCalledWith({
      objectName: 'sidebar',
      actionName: 'clicked',
      screen: 'My Account',
      toCleverTap: true,
      properties: {
        clickedElement: 'show less',
        section: titleCase(FALLBACK_PRODUCTS[0].section_name),
        location: 'sidebar',
        sidebar: 'v2',
      },
    });
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

  test("should update products list when user's tags are updated", () => {
    jest.spyOn(showUtils, 'showWhenUtil').mockImplementation(() => true);
    const props = {
      loading: false,
      heading: FALLBACK_PRODUCTS[0].section_name,
      products: FALLBACK_PRODUCTS[0].product_options,
    };
    const { rerender } = renderApp({
      props,
    });

    expect(screen.getAllByText('Navigation Link Item').length).toBe(
      FALLBACK_PRODUCTS[0].product_options.length,
    );
    // showWhenUtil is set to false for all products so that in the next rerender
    // we can check if the products list is updated
    jest.spyOn(showUtils, 'showWhenUtil').mockImplementation(() => false);

    rerender(<App {...props} user={{ tags: ['test-tag'] }} />);

    expect(screen.queryAllByText('Navigation Link Item').length).toBe(0);
  });
});
