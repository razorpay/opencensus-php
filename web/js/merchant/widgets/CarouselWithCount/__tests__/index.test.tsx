import React from 'react';

import { KEY_UPDATES_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { CarouselWithCountWidget } from 'merchant/widgets/CarouselWithCount';
import { screen, waitFor, renderWithSuspense, userEvent } from 'test-utils';

const mockRetry = jest.fn();
const error = "Key updates couldn't be loaded";

jest.mock('merchant/widgets/hooks', () => ({
  useRetryWidget: () => [false, mockRetry],
}));

describe('Widgets->CarouselWithCount', () => {
  test('should display loader', async () => {
    renderWithSuspense(
      <CarouselWithCountWidget {...KEY_UPDATES_MOCK_RESPONSE} isLoading={true} queryKey={[]} />,
    );
    await waitFor(() => {
      expect(screen.getAllByTestId('data-widget-loader')).toHaveLength(
        KEY_UPDATES_MOCK_RESPONSE.components.length,
      );
    });
  });

  test('should display fallback widget loader if empty components', async () => {
    renderWithSuspense(
      <CarouselWithCountWidget
        {...KEY_UPDATES_MOCK_RESPONSE}
        components={[]}
        background_img=""
        isLoading={true}
        queryKey={[]}
      />,
    );
    await waitFor(() => {
      expect(screen.getAllByTestId('data-widget-loader')).toHaveLength(4);
    });
  });

  test('should display error state', async () => {
    renderWithSuspense(
      <CarouselWithCountWidget
        {...KEY_UPDATES_MOCK_RESPONSE}
        isLoading={false}
        queryKey={[]}
        error={{ message: error }}
      />,
    );
    await waitFor(() => {
      expect(screen.getByText(new RegExp(error, 'i'))).toBeVisible();
    });
  });

  test('should handle if no background image url is given', async () => {
    renderWithSuspense(
      <CarouselWithCountWidget
        {...KEY_UPDATES_MOCK_RESPONSE}
        components={[]}
        background_img=""
        isLoading={true}
        queryKey={[]}
      />,
    );
    await waitFor(() => {
      expect(screen.getByTestId('carousel-with-count-wrapper')).toHaveStyle(
        'background-color:hsla(0,0%,100%,1)',
      );
    });
  });

  test('should indicate if no carousel items present', async () => {
    renderWithSuspense(
      <CarouselWithCountWidget
        {...KEY_UPDATES_MOCK_RESPONSE}
        isLoading={false}
        queryKey={[]}
        components={[]}
      />,
    );
    await waitFor(() => {
      expect(
        screen.getByText(`${KEY_UPDATES_MOCK_RESPONSE.title} - You're all caught up!`),
      ).toBeInTheDocument();
    });
  });

  test('should render component with valid props', async () => {
    renderWithSuspense(
      <CarouselWithCountWidget
        {...KEY_UPDATES_MOCK_RESPONSE}
        isLoading={false}
        queryKey={[]}
        background_img=""
      />,
    );
    await waitFor(() => expect(screen.getByText(KEY_UPDATES_MOCK_RESPONSE.title)).toBeVisible());
  });

  test('should retry error state', async () => {
    renderWithSuspense(
      <CarouselWithCountWidget
        {...KEY_UPDATES_MOCK_RESPONSE}
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
