import React from 'react';

import { renderWithSuspense, screen, userEvent, waitFor } from 'test-utils';
import { PRODUCT_RECOMMENDER_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { CarouselWidget } from 'merchant/widgets/Carousel';

const mockRetry = jest.fn();
const error = "Products for you couldn't be loaded";

jest.mock('merchant/widgets/hooks', () => ({
  useRetryWidget: () => [false, mockRetry],
}));

describe('Widgets->Carousel', () => {
  test('should display loader', async () => {
    renderWithSuspense(
      <CarouselWidget {...PRODUCT_RECOMMENDER_MOCK_RESPONSE} isLoading={true} queryKey={[]} />,
    );
    await waitFor(() => {
      expect(screen.getAllByTestId('product-card-loader')).toHaveLength(
        PRODUCT_RECOMMENDER_MOCK_RESPONSE.components.length,
      );
    });
  });

  test('should display fallback widget loader if empty components', async () => {
    renderWithSuspense(
      <CarouselWidget
        {...PRODUCT_RECOMMENDER_MOCK_RESPONSE}
        components={[]}
        background_img=""
        isLoading={true}
        queryKey={[]}
      />,
    );
    await waitFor(() => {
      expect(screen.getAllByTestId('product-card-loader')).toHaveLength(4);
    });
  });

  test('should handle if no background image url is given', async () => {
    renderWithSuspense(
      <CarouselWidget
        {...PRODUCT_RECOMMENDER_MOCK_RESPONSE}
        components={[]}
        background_img=""
        isLoading={true}
        queryKey={[]}
      />,
    );
    await waitFor(() => {
      expect(screen.getByTestId('carousel-widget-wrapper')).toHaveStyle(
        'background-color:hsla(0,0%,100%,1)',
      );
    });
  });

  test('should display error state', async () => {
    renderWithSuspense(
      <CarouselWidget
        {...PRODUCT_RECOMMENDER_MOCK_RESPONSE}
        isLoading={false}
        queryKey={[]}
        error={{ message: error }}
      />,
    );
    await waitFor(() => {
      expect(screen.getByText(error)).toBeVisible();
    });
  });

  test('should not display anything if no carousel items present', async () => {
    renderWithSuspense(
      <CarouselWidget
        {...PRODUCT_RECOMMENDER_MOCK_RESPONSE}
        isLoading={false}
        queryKey={[]}
        components={[]}
      />,
    );
    await waitFor(() => {
      expect(screen.queryByText(PRODUCT_RECOMMENDER_MOCK_RESPONSE.title)).not.toBeInTheDocument();
    });
  });

  test('should render component with valid props', async () => {
    renderWithSuspense(
      <CarouselWidget {...PRODUCT_RECOMMENDER_MOCK_RESPONSE} isLoading={false} queryKey={[]} />,
    );
    await waitFor(() =>
      expect(screen.getByText(PRODUCT_RECOMMENDER_MOCK_RESPONSE.title)).toBeVisible(),
    );
  });

  test('should render component with background image', async () => {
    renderWithSuspense(
      <CarouselWidget {...PRODUCT_RECOMMENDER_MOCK_RESPONSE} isLoading={false} queryKey={[]} />,
    );
    await waitFor(() => {
      expect(screen.getByTestId('carousel-widget-wrapper')).toHaveStyle(
        `background-image:url(${PRODUCT_RECOMMENDER_MOCK_RESPONSE.background_img})`,
      );
    });
  });

  test('should retry error state', async () => {
    renderWithSuspense(
      <CarouselWidget
        {...PRODUCT_RECOMMENDER_MOCK_RESPONSE}
        isLoading={false}
        queryKey={[]}
        error={{ message: error }}
      />,
    );
    const retryButton = await screen.findByRole('button', { name: /try again/i });
    await userEvent.click(retryButton);
    await waitFor(() => {
      expect(mockRetry).toHaveBeenCalled();
    });
  });
});
