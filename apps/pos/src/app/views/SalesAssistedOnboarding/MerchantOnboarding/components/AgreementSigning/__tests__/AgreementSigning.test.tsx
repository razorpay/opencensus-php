import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { getModularConfig, updateModularConfig } from './mocks/handlers';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import AgreementSigning from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/AgreementSigning';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'agreementSigning',
    component: 'agreementMode',
  }),
}));

const queryClient = new QueryClient();
const renderApp = () => {
  render(
    <QueryClientProvider client={queryClient}>
      <AgreementSigning />
    </QueryClientProvider>,
  );
};

describe('<AgreementSigning/>', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  test('should render agent agreement screen', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /online/i })).toBeInTheDocument();
      expect(screen.getByRole('radio', { name: /offline/i })).toBeInTheDocument();
    });
  });

  test('should select online option if agreement type is online', async () => {
    server.use(getModularConfig({ type: 'success', data: { agreementType: 'online' } }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /online/i })).toBeChecked();
      expect(screen.getByRole('radio', { name: /offline/i })).toBeInTheDocument();
      screen.logTestingPlaygroundURL();
      expect(screen.getByRole('button', { name: /send-link-btn/i })).toBeInTheDocument();
    });
  });

  test('should select offline option if agreement type is offline', async () => {
    server.use(getModularConfig({ type: 'success', data: { agreementType: 'offline' } }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /online/i })).toBeInTheDocument();
      expect(screen.getByRole('radio', { name: /offline/i })).toBeChecked();
    });
  });

  test('should show agreement sent if agreement is generated', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: '',
          agreementStatusField: '',
          agreementSentAt: '',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementStatusField: 'in_progress',
          agreementComponentStatus: '',
          agreementSentAt: '1722276450',
        },
      }),
    );
    renderApp();

    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /online/i })).toBeChecked();
      expect(screen.getByRole('radio', { name: /offline/i })).toBeInTheDocument();
    });
    await userEvent.click(screen.getByRole('button', { name: /send-link-btn/i }));
    await waitFor(() => {
      expect(screen.getByText(/agreement sent/i)).toBeInTheDocument();
    });
    screen.debug(undefined, 100000000);
    expect(screen.getByText(/Mon, 29th Jul’24 | 6:07pm/i)).toBeInTheDocument();
    expect(screen.getByText(/action pending/i)).toBeInTheDocument();
    expect(screen.getByText(/signing confirmation/i)).toBeInTheDocument();
    expect(screen.getByText(/copy link/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /send-link-btn/i })).toBeInTheDocument();
  });

  test('should show agreement consented if agreement is consented', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementStatusField: 'completed',
          agreementComponentStatus: 'executed',
          agreementSentAt: '1722276450',
          agreementConsentedAt: '1722277779',
        },
      }),
    );
    renderApp();

    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /online/i })).toBeChecked();
      expect(screen.getByRole('radio', { name: /offline/i })).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(screen.getByText(/agreement sent/i)).toBeInTheDocument();
    });
    screen.debug(undefined, 10000000);
    expect(screen.getByText(/Mon, 29th Jul’24 \| 6:07pm/i)).toBeInTheDocument();
    expect(screen.getByText(/signing confirmation/i)).toBeInTheDocument();
    expect(screen.getByText(/^successful$/i)).toBeInTheDocument();
    expect(screen.getByText(/Mon, 29th Jul’24 \| 6:29pm/i)).toBeInTheDocument();
    expect(screen.queryByText(/copy link/i)).not.toBeInTheDocument();
  });

  test('should show agreement re-sent toast if agreement is re-sent', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementStatusField: 'in_progress',
          agreementComponentStatus: '',
          agreementSentAt: '1722276450',
          agreementConsentedAt: '1722277779',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          retrySentAt: '1722578653',
        },
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /online/i })).toBeChecked();
      expect(screen.getByRole('radio', { name: /offline/i })).toBeInTheDocument();
    });
    expect(screen.getByText(/agreement sent/i)).toBeInTheDocument();
    screen.logTestingPlaygroundURL();
    expect(screen.getByText(/Mon, 29th Jul’24 \| 6:07pm/i)).toBeInTheDocument();
    expect(screen.getByText(/action pending/i)).toBeInTheDocument();
    expect(screen.getByText(/signing confirmation/i)).toBeInTheDocument();
    expect(screen.getByText(/copy link/i)).toBeInTheDocument();
    const resendLinkBtn = screen.getByRole('button', { name: /send-link-btn/i });
    expect(resendLinkBtn).toBeInTheDocument();
    await userEvent.click(resendLinkBtn);
    screen.debug(undefined, 10000000);
    await waitFor(() => {
      expect(screen.getByText(/Agreement re-sent successfully/i)).toBeInTheDocument();
    });
  });

  test('should show error if agreement is failed to generate', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: '',
          agreementStatusField: '',
          agreementComponentStatus: '',
          agreementSentAt: '1722276450',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementStatusField: '',
          agreementComponentStatus: '',
          agreementSentAt: '1722276450',
        },
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /online/i })).toBeChecked();
      expect(screen.getByRole('radio', { name: /offline/i })).toBeInTheDocument();
    });
    await userEvent.click(screen.getByRole('button', { name: 'send-link-btn' }));
    await waitFor(() =>
      expect(screen.getByText(/Unable to generate link. Please try again/i)).toBeInTheDocument(),
    );
  });

  test('should show bottom sheet if agreement component is executed', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementComponentStatus: 'executed',
          agreementStatusField: 'completed',
        },
      }),
    );
    renderApp();

    await waitFor(() => {
      expect(screen.getByText(/KYC details submitted successfully!/i)).toBeInTheDocument();
    });
  });

  test('should show file upload field when agreement mode is offline', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'offline',
          agreementComponentStatus: '',
          agreementStatusField: '',
        },
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /offline/i })).toBeChecked();
      expect(screen.getByText(/Upload TnC & Pricing Agreement/i)).toBeInTheDocument();
    });
  });

  test('should show submitted bottom sheet if agreement is already signed and online mode', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementComponentStatus: 'executed',
          agreementStatusField: 'completed',
        },
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/KYC details submitted successfully!/i)).toBeInTheDocument();
    });
  });

  test('should show submitted bottom sheet if agreement is already signed and offline mode', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'offline',
          agreementComponentStatus: 'executed',
          agreementStatusField: 'completed',
        },
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/KYC details submitted successfully!/i)).toBeInTheDocument();
    });
  });

  test('should show link copied toast when copying link', async () => {
    const originalExec = document.execCommand;
    document.execCommand = jest.fn();
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementComponentStatus: '',
          agreementStatusField: 'in_progress',
          agreementSentAt: '1722276450',
        },
      }),
    );
    renderApp();

    await waitFor(() => {
      const copyBtn = screen.getByRole('button', { name: /copy link/i });
      userEvent.click(copyBtn);
    });
    await waitFor(() => {
      expect(screen.getByText(/Link copied successfully/i)).toBeInTheDocument();
    });
    document.execCommand = originalExec;
  });
});
