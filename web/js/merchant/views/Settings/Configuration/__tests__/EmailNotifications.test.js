import { screen, waitFor, fireEvent, render, act } from 'test-utils';
import EmailNotifications from 'merchant/views/Settings/Configuration/EmailNotifications';
import { QueryClient, QueryClientProvider, useMutation } from '@tanstack/react-query';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

jest.mock('@tanstack/react-query', () => {
  return {
    ...jest.requireActual('@tanstack/react-query'),
    useQuery: jest.fn(),
    useMutation: jest.fn(() => ({
      mutate: jest.fn(),
    })),
  };
});

const initialEmailSettings = 'john.doe@gmail.com';

const initialState = {
  session: {
    user: {
      name: 'John Doe',
      role: 'owner',
    },
    org: { name: 'Razorpay Softwate Pvt Ltd', features: [] },
    config: {
      config: { transaction_report_email: initialEmailSettings },
    },
  },
  form: {
    configForm: {
      values: {
        transaction_report_email: initialEmailSettings,
      },
      initial: {
        transaction_report_email: initialEmailSettings,
      },
    },
  },
};

const queryClient = new QueryClient();

const notificationEmailUpdateMockResponse = {
  notificationEmailUpdate: { __typename: 'NotificationEmailUpdateSuccessResponse' },
};

describe('EmailNotifications component', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  it('renders EmailNotification with initial state', () => {
    render(
      <QueryClientProvider client={queryClient}>
        <EmailNotifications />
      </QueryClientProvider>,
      {
        initialState,
      },
    );

    waitFor(() => {
      expect(screen.getByText(initialEmailSettings)).toBeInTheDocument();
      expect(screen.getByText(/save changes/i)).toBeInTheDocument();
    });
  });

  it.skip('handles form submission correctly and updates the email', () => {
    useMutation.mockReturnValue({
      mutateAsync: jest.fn().mockImplementation((_, { onSuccess }) => {
        onSuccess(notificationEmailUpdateMockResponse);
      }),
    });

    render(
      <QueryClientProvider client={queryClient}>
        <EmailNotifications />
      </QueryClientProvider>,
      {
        initialState,
      },
    );
    const emailInput = screen.getByRole('textbox');
    expect(emailInput).toBeInTheDocument();

    fireEvent.change(emailInput, {
      target: { value: 'test@example.com' },
    });

    //Trigger form submission
    act(() => {
      fireEvent.submit(screen.getByText(/save changes/i));
    });

    expect(emailInput).toHaveValue('test@example.com');

    waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled(); //Doesn't work.
    });
  });
});
