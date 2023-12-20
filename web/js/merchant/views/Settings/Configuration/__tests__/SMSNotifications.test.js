import React from 'react';
import { screen, waitFor, fireEvent, render } from 'test-utils';
import SmsNotification from 'merchant/views/Settings/Configuration/SmsNotification';
import { FakeMouseEvent } from 'merchant/views/Settings/Webhooks/__test__/mocks/fixtures/Entity';
import { QueryClient, QueryClientProvider, useQuery, useMutation } from '@tanstack/react-query';

jest.mock('@tanstack/react-query', () => {
  return {
    ...jest.requireActual('@tanstack/react-query'),
    useQuery: jest.fn(),
    useMutation: jest.fn(() => ({
      mutate: jest.fn(),
    })),
  };
});

const initialState = {
  session: {
    user: {
      contact_mobile: '1234567890',
    },
  },
};

const queryClient = new QueryClient();

describe('SmsNotification component', () => {
  it('renders SmsNotification component with loading state', () => {
    useQuery.mockReturnValue({
      data: undefined,
      isFetching: true,
      status: 'loading',
    });

    render(
      <QueryClientProvider client={queryClient}>
        <SmsNotification />
      </QueryClientProvider>,
    );
    const loaderDotsElement = screen.getByTestId('loader-dots');

    expect(loaderDotsElement).toBeInTheDocument();
    expect(loaderDotsElement).toHaveClass('LoaderDots');
  });

  it('renders SmsNotification component with enabled state', () => {
    useQuery.mockReturnValue({
      data: {
        smsNotificationStatus: {
          isEnabled: true,
        },
      },
      isFetching: false,
      status: 'success',
    });

    render(
      <QueryClientProvider client={queryClient}>
        <SmsNotification />
      </QueryClientProvider>,
    );

    expect(screen.getByText('Enabled')).toBeInTheDocument();
  });

  it('handles SMS toggle successfully', async () => {
    // Mocking useQuery to simulate success state with disabled SMS notifications
    useQuery.mockReturnValue({
      data: {
        smsNotificationStatus: {
          isEnabled: false,
        },
      },
      isFetching: false,
      status: 'success',
    });

    // Mocking useMutation to simulate a successful SMS toggle
    useMutation.mockReturnValue({
      mutate: jest.fn().mockImplementation((_, { onSuccess }) => {
        onSuccess();
      }),
      isLoading: false,
    });

    render(
      <QueryClientProvider client={queryClient}>
        <SmsNotification />
      </QueryClientProvider>,
      {
        initialState,
      },
    );

    const toggleButton = screen.getByRole('button');
    fireEvent(
      toggleButton,
      new FakeMouseEvent('click', {
        bubbles: true,
        pageX: 350,
        pageY: 125,
      }),
    );

    await waitFor(() => {
      expect(screen.getByText('Enabled')).toBeInTheDocument();
    });
  });
});
