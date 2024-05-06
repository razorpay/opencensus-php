import React from 'react';

import { screen, waitFor, renderWithSuspense } from 'test-utils';
import { KEY_UPDATES_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { CarouselDataWidget } from 'merchant/widgets/CarouselWithCount/subWidget/CarouselData';

describe('Widgets->CarouselWithCount->CarouselData', () => {
  test('should render component with valid props', async () => {
    renderWithSuspense(
      <CarouselDataWidget
        {...KEY_UPDATES_MOCK_RESPONSE.components[0]}
        queryKey={[]}
        isLoading={false}
      />,
    );
    await waitFor(() =>
      expect(screen.getByText(KEY_UPDATES_MOCK_RESPONSE.components[0].title)).toBeInTheDocument(),
    );
    await waitFor(
      () => {
        expect(screen.findByRole('link'));
      },
      { timeout: 10000 },
    );
  });

  test('should not display link if action is missing', async () => {
    renderWithSuspense(
      <CarouselDataWidget
        {...KEY_UPDATES_MOCK_RESPONSE.components[2]}
        queryKey={[]}
        isLoading={false}
      />,
    );
    await waitFor(() =>
      expect(screen.getByText(KEY_UPDATES_MOCK_RESPONSE.components[2].title)).toBeInTheDocument(),
    );
    expect(
      screen.queryByRole('link', { name: KEY_UPDATES_MOCK_RESPONSE.components[0].action?.title }),
    ).not.toBeInTheDocument();
  });

  test('should not render icon if variant missing in mapping', async () => {
    renderWithSuspense(
      <CarouselDataWidget
        {...KEY_UPDATES_MOCK_RESPONSE.components[0]}
        variant="invalid"
        queryKey={[]}
        isLoading={false}
      />,
    );
    await waitFor(() => expect(screen.queryByTestId('carousel-data-icon')).not.toBeInTheDocument());
  });
});
