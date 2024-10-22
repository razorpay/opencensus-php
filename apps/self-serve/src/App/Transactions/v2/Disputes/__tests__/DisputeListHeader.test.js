import { useMutation } from '@tanstack/react-query';

import { renderApp } from 'apps/self-serve/src/App/Transactions/v2/Disputes/__tests__/mocks/fixtures/DisputeListHeader';
import { screen, waitFor, userEvent } from 'apps/self-serve/src/services/test/test-utils';

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');

  return {
    ...original,
    useMutation: jest.fn().mockReturnValue({
      mutate: jest.fn(),
      isLoading: false,
    }),
  };
});

describe('DisputeListHeader', () => {
  test('renders without error', () => {
    renderApp();
    expect(screen.getByText('Disputes')).toBeVisible();
  });

  test('should call the download api on clicking download button', async () => {
    renderApp();

    const downloadBtn = screen.getByTestId('download-testID');
    await userEvent.click(downloadBtn);
    await waitFor(() => {
      expect(useMutation).toHaveBeenCalledWith(
        expect.objectContaining({
          mutationFn: expect.any(Function),
          onSuccess: expect.any(Function),
          onError: expect.any(Function),
        }),
      );

      expect(useMutation().mutate).toHaveBeenCalled();
    });
  });

  test('should disable download button when fetching download report data', async () => {
    useMutation.mockReturnValue({
      mutate: jest.fn(),
      isLoading: true,
    });
    renderApp();
    const downloadBtn = screen.getByTestId('download-testID');
    await userEvent.click(downloadBtn);

    await waitFor(() => {
      expect(downloadBtn).toBeDisabled();
    });
  });
});
