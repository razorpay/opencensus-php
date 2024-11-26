import { useMutation } from '@tanstack/react-query';

import { renderApp } from 'merchant/views/Transactions/v2/Disputes/__tests__/mocks/fixtures/DisputeListHeader';
import { track } from 'merchant/views/Transactions/v2/common/tracking';
import { screen, userEvent, waitFor } from 'test-utils';

jest.mock('common/utils/rzp-utils', () => ({
  ...jest.requireActual('common/utils/rzp-utils'),
  exportFileAsExcel: jest.fn(),
}));

jest.mock('merchant/views/Transactions/v2/common/tracking', () => ({
  ...jest.requireActual('merchant/views/Transactions/v2/common/tracking'),
  track: jest.fn(),
}));

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

  test('should disable download button when fetching applied status,filter and duration of disputes', async () => {
    useMutation.mockReturnValue({
      mutate: jest.fn(),
      isLoading: false,
    });
    renderApp({ isFetchingTableData: true });
    const downloadBtn = screen.getByTestId('download-testID');
    await userEvent.click(downloadBtn);

    await waitFor(() => {
      expect(downloadBtn).toBeDisabled();
    });
  });

  test('should send the anaytics event for download report', async () => {
    useMutation.mockReturnValue({
      mutate: jest.fn(),
      isLoading: false,
    });
    renderApp();
    const downloadBtn = screen.getByTestId('download-testID');
    await userEvent.click(downloadBtn);

    await waitFor(() => {
      expect(track).toHaveBeenCalled();
    });
  });
});
