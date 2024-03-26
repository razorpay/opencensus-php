import React from 'react';

import { screen, waitFor, renderWithSuspense, userEvent } from 'test-utils';
import { PRODUCT_RECOMMENDER_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';

import { useMobile } from 'common/hooks/useMobile';
import { ProductCardWidget } from 'merchant/widgets/Carousel/subWidget/ProductCard';
import { makeLink } from 'merchant/widgets/common/utils';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

describe('Widgets->Carousel->Product Card', () => {
  describe('dWeb', () => {
    test('should display loader', async () => {
      renderWithSuspense(
        <ProductCardWidget {...PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0]} isLoading={true} />,
      );
      await waitFor(() => {
        expect(screen.getByTestId('product-card-loader')).toBeVisible();
      });
    });

    test('should render component with valid props', async () => {
      renderWithSuspense(
        <ProductCardWidget
          {...PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0]}
          isLoading={false}
        />,
      );
      await waitFor(() => {
        expect(
          screen.getByText(PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0].title),
        ).toBeVisible();
        expect(
          screen.getByText(PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0].description),
        ).toBeVisible();
      });
    });

    test('should show/hide link when hovered/un-hovered for desktop version', async () => {
      renderWithSuspense(
        <ProductCardWidget
          {...PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0]}
          isLoading={false}
        />,
      );
      const productCardWidget = screen.getByTestId('product-card-widget');
      await userEvent.hover(productCardWidget);
      await waitFor(() => {
        expect(
          screen.getByRole('link', {
            name: new RegExp(PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0].actions[0].title, 'i'),
          }),
        ).toBeVisible();
      });
      await userEvent.unhover(productCardWidget);
      await waitFor(() => {
        expect(
          screen.queryByRole('link', {
            name: new RegExp(PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0].actions[0].title, 'i'),
          }),
        ).not.toBeInTheDocument();
      });
    });

    test('should navigate to dashboard internal url', async () => {
      const { history } = renderWithSuspense(
        <ProductCardWidget
          {...PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[1]}
          isLoading={false}
        />,
      );
      const productCardWidget = screen.getByTestId('product-card-widget');
      await userEvent.hover(productCardWidget);
      const linkCTA = await screen.findByRole('link', {
        name: new RegExp(PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[1].actions[0].title, 'i'),
      });
      await userEvent.click(linkCTA);
      expect(history.location.pathname).toBe(
        makeLink(PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[1].actions[0].action),
      );
    });
  });

  describe('mWeb', () => {
    test('should navigate to link on click on mobile version', async () => {
      const spyWindowOpen = jest.spyOn(window, 'open');
      (useMobile as jest.Mock).mockImplementation(() => true);
      renderWithSuspense(
        <ProductCardWidget
          {...PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0]}
          isLoading={false}
        />,
      );
      const productCardWidget = screen.getByTestId('product-card-widget');
      await userEvent.click(productCardWidget);
      expect(spyWindowOpen).toHaveBeenCalledWith(
        PRODUCT_RECOMMENDER_MOCK_RESPONSE.components[0].actions[0].action,
        '_blank',
      );
    });
  });
});
