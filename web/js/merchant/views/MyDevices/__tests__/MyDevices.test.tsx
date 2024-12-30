import React from 'react';

import { screen, render, waitFor, act, fireEvent } from 'common/services/test/test-utils';
import MyDevices from 'merchant/views/MyDevices';
import { useFetchDevices } from '../hooks';
import { MOCK_DEVICE } from '../mocks/fixtures';

jest.mock('../hooks', () => {
  const actualImport = jest.requireActual('../hooks');
  return {
    ...actualImport,
    useFetchDevices: jest.fn(),
  };
});

const App = (props) => <MyDevices {...props} />;

describe('My Devicesw', () => {
  test('should render my devices page correctly', () => {
    (useFetchDevices as jest.Mock).mockReturnValue({
      data: {
        device_data: [],
      },
      isLoading: true,
    });
    render(<App />, {});
    expect(screen.getByText(/My Devices/i)).toBeInTheDocument();
  });

  test('Should render DeviceCardSkeleton when devices are loading', () => {
    (useFetchDevices as jest.Mock).mockReturnValue({
      data: {
        device_data: [],
      },
      isLoading: true,
    });
    render(<App />);

    expect(screen.getByTestId('my-devices-skeleton')).toBeInTheDocument();
  });

  test('Should render empty message if no devices present', async () => {
    const refetchMock = jest.fn();
    (useFetchDevices as jest.Mock).mockReturnValue({
      data: {
        device_data: [],
      },
      isLoading: false,
      refetch: refetchMock,
    });
    render(<App />);
    await waitFor(() => {
      expect(screen.getByText(/Error Loading the page!/i)).toBeInTheDocument();
      const retryBtn = screen.getByRole('button', {
        name: /Retry/i,
      });
      expect(retryBtn).toBeInTheDocument();
    });

    await act(async () => {
      await fireEvent.click(screen.getByText(/Retry/i));
    });

    await waitFor(() => {
      expect(refetchMock).toHaveBeenCalled();
    });
  });

  test('Should render device details if devices present', async () => {
    (useFetchDevices as jest.Mock).mockReturnValue({
      data: {
        data: {
          items: MOCK_DEVICE,
        },
      },
      isLoading: false,
    });
    render(<App />, {});

    await waitFor(() => {
      expect(screen.getAllByText(/J&K Soundbox/i)).toHaveLength(1);
    });
  });
});
