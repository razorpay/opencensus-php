import React from 'react';
import { getModularConfig, updateModularConfig } from './mocks/handlers';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import AgreementSigning from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/AgreementSigning';
import { PosAgreementMode } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/PosAgreementMode';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'agreementSigning',
    component: 'agreementMode',
  }),
}));

const renderApp = () => {
  render(<AgreementSigning />);
};

describe('<AgreementSigning/>', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
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
    expect(screen.getByText(/Mon, 29th Jul’24 \| 6:07pm/i)).toBeInTheDocument();
    expect(screen.getByText(/action pending/i)).toBeInTheDocument();
    expect(screen.getByText(/signing confirmation/i)).toBeInTheDocument();
    expect(screen.getByText(/copy link/i)).toBeInTheDocument();
    const resendLinkBtn = screen.getByRole('button', { name: /send-link-btn/i });
    expect(resendLinkBtn).toBeInTheDocument();
    await userEvent.click(resendLinkBtn);
    await waitFor(() => {
      expect(screen.getByText(/Agreement re-sent successfully/i)).toBeInTheDocument();
    });
  });

  test('should show error if agreement sending fails', async () => {
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
        type: 'failure',
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
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument(),
    );
  });
  test('should show error if failed to fetch agreement options', async () => {
    server.use(
      getModularConfig({
        type: 'failure',
        data: {
          agreementType: '',
          agreementStatusField: '',
          agreementComponentStatus: '',
          agreementSentAt: '1722276450',
        },
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
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

  test('should throw error for invalid file upload', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          agreementType: 'online',
          agreementComponentStatus: '',
          agreementStatusField: '',
        },
      }),
    );
    renderApp();

    await waitFor(async () => {
      const offlineRadio = screen.getAllByTestId('agreement-mode-radio')[1];
      await userEvent.click(offlineRadio);
      expect(screen.getByRole('radio', { name: /offline/i })).toBeChecked();
      expect(screen.getByText(/Upload TnC & Pricing Agreement/i)).toBeInTheDocument();
    });

    const file = new File(['content'], 'tnc.pdf', { type: 'text/plain' });
    const fileInput = screen.getByLabelText('file-upload-input');
    await userEvent.upload(fileInput, file);
    expect(screen.getByText('Some error occurred while uploading file!')).toBeInTheDocument();

    const submitBtn = screen.getByText('Submit Merchant Details');
    await userEvent.click(submitBtn);
    expect(screen.getByText('Please upload a file')).toBeInTheDocument();
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

describe('<PosAgreementMode>', () => {
  const props = {
    modularConfig: null as any,
    isUpdateModularLoading: false,
    updateModularConfig: jest.fn(),
    isModularLoading: true,
    merchantDetails: undefined,
  };

  test('should show loader if isModularLoading is true', async () => {
    render(<PosAgreementMode {...props} />);
    expect(screen.getByLabelText('additional-details-spinner')).toBeInTheDocument();
  });

  test('should return null when modular config is absent', async () => {
    render(<PosAgreementMode {...{ ...props, isModularLoading: false }} />);
    expect(screen.queryByRole('radio', { name: /online/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('radio', { name: /offline/i })).not.toBeInTheDocument();
  });
});
