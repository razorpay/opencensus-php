import { screen, waitForLoadingToFinish, userEvent, waitFor, server } from 'test-utils';
import {
  renderApp,
  defaultProps,
} from 'merchant/views/PaymentPages/__test__/mocks/fixtures/BatchUpload/List/index';
import FileSaver from 'file-saver';
import { paymentPagesErrorHandlers } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/handlers';
const saveAsSpy = jest.spyOn(FileSaver, 'saveAs');

describe('Batch Payment Page - Batch Details', () => {
  beforeAll(() => {
    window.rzpQ = {
      onbr: () => {
        return {
          success: jest.fn(),
        };
      },
    };
  });

  test('should render "Download Sample File" option', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_LpoFCooJAk0a2j',
      isBatchPaymentPages: true,
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(screen.getByText('Download Sample File')).toBeInTheDocument();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
  });

  test('should show error message while parsing udf_schema', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_parsingerrortest',
      isBatchPaymentPages: true,
    };
    server.use(paymentPagesErrorHandlers.paymentPagesDetailsParsingError());
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(
      screen.getByText(/Issue while parsing the udf_schema, please try again later/i),
    ).toBeInTheDocument();
  });

  test('should show error message while fetching payment page details', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_apierrortest',
      isBatchPaymentPages: true,
    };
    server.use(paymentPagesErrorHandlers.paymentPagesDetailsError());
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(screen.getByText(/The requested URL was not found on the server./i)).toBeInTheDocument();
  });

  test('should show error message while downloading sample file', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_apierrortest',
      isBatchPaymentPages: true,
    };
    server.use(paymentPagesErrorHandlers.paymentPagesDetailsError());
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    const downloadSampleFileBtn = screen.getByRole('button', { name: 'Download Sample File' });
    expect(downloadSampleFileBtn).toBeInTheDocument();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
    await userEvent.click(downloadSampleFileBtn);
    expect(screen.getByText(/Error while generating sample file./i)).toBeInTheDocument();
  });

  test('should able to downloading sample file', async () => {
    saveAsSpy.mockImplementation(() => jest.fn());
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_validid',
      isBatchPaymentPages: true,
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    const downloadSampleFileBtn = screen.getByRole('button', { name: 'Download Sample File' });
    expect(downloadSampleFileBtn).toBeInTheDocument();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
    await userEvent.click(downloadSampleFileBtn);
    expect(FileSaver.saveAs).toHaveBeenCalledWith(new Blob(), 'sample_pl_validid.xlsx');
  });

  test('should able to clear filter', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_LpoFCooJAk0a2j',
      isBatchPaymentPages: true,
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    const clearBtn = screen.getByRole('button', { name: 'Clear' });
    expect(clearBtn).toBeInTheDocument();
    await userEvent.click(clearBtn);
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
  });

  test('should render upload modal', async () => {
    const initialState = {
      session: {
        user: {
          isPaymentPageFileUploadEnabled: true,
          isAllowedView: jest.fn(() => false),
          merchant: { country_code: 'IN' },
        },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_LpoFCooJAk0a2j',
      isBatchPaymentPages: true,
    };
    server.use(paymentPagesErrorHandlers.batchPaymentPageGetBatchesError());
    jest.setTimeout(30000);
    renderApp(initialState, props, true);
    await waitForLoadingToFinish();
    const uploadBtn = screen.getByRole('button', { name: 'Click here to upload' });
    expect(uploadBtn).toBeInTheDocument();
    await userEvent.click(uploadBtn);
    const uploadCTA = screen.getAllByRole('button', {
      name: /Click here to upload/,
    });
    expect(uploadCTA[0]).toBeInTheDocument();
    await userEvent.click(uploadCTA[0]);
    const startUploadingCTA = screen.getByRole('button', {
      name: /Start Uploading/,
    });
    expect(startUploadingCTA).toBeInTheDocument();
    await userEvent.click(startUploadingCTA);
    expect(screen.getByText('UPLOAD FILE')).toBeInTheDocument();
    expect(screen.getByText('Getting Started with Batch Uploads?')).toBeInTheDocument();
  });

  test('should able to render list of batches & able to notify batch  when "file_upload_pp" flag is enabled', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_valid_id',
      isBatchPaymentPages: true,
    };
    renderApp(initialState, props, true);
    await waitForLoadingToFinish();
    const notifyBatch = screen.getAllByText('Send all links')[0];
    expect(notifyBatch).toBeInTheDocument();
    await userEvent.click(notifyBatch);
    expect(screen.getByText('Send Reminder?')).toBeInTheDocument();
    expect(
      screen.getByText('Are you sure that you want to send the unpaid links again?'),
    ).toBeInTheDocument();
    const sendSms = screen.getByText('Send SMS');
    const sendEmail = screen.getByText('Send Email');
    expect(sendSms).toBeInTheDocument();
    expect(sendEmail).toBeInTheDocument();
    await userEvent.click(sendSms);
    await userEvent.click(sendEmail);
    const sendAllButton = screen.getByRole('button', { name: 'Yes, Send All' });
    expect(sendAllButton).toBeInTheDocument();
    await userEvent.click(sendAllButton);
    await waitFor(() =>
      expect(
        screen.getByText(/All payment links of this batch will be sent shortly/i),
      ).toBeInTheDocument(),
    );
  });

  test('should able to render list of batches & able to notify batch  when batch type is other than "payment_page" & PL V2 is disabled', async () => {
    const initialState = {
      session: {
        user: {
          isPaymentlinksV2Enabled: false,
          isPaymentPageFileUploadEnabled: true,
          isAllowedView: jest.fn(() => false),
        },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_valid_id',
      isBatchPaymentPages: true,
    };
    renderApp(initialState, props, true);
    await waitForLoadingToFinish();
    const notifyBatch = screen.getAllByText('Send all links')[1];
    expect(notifyBatch).toBeInTheDocument();
    await userEvent.click(notifyBatch);
    expect(screen.getByText('Send Reminder?')).toBeInTheDocument();
    expect(
      screen.getByText('Are you sure that you want to send the unpaid links again?'),
    ).toBeInTheDocument();
    const sendSms = screen.getByText('Send SMS');
    const sendEmail = screen.getByText('Send Email');
    expect(sendSms).toBeInTheDocument();
    expect(sendEmail).toBeInTheDocument();
    await userEvent.click(sendSms);
    await userEvent.click(sendEmail);
    const sendAllButton = screen.getByRole('button', { name: 'Yes, Send All' });
    expect(sendAllButton).toBeInTheDocument();
    await userEvent.click(sendAllButton);
    await waitFor(() =>
      expect(
        screen.getByText(/All payment links of this batch will be sent shortly/i),
      ).toBeInTheDocument(),
    );
  });

  test('should able to render list of batches & able to notify batch  when batch type is other than "payment_page" & PL V2 is enabled', async () => {
    const initialState = {
      session: {
        user: {
          isPaymentlinksV2Enabled: true,
          isPaymentPageFileUploadEnabled: true,
          isAllowedView: jest.fn(() => false),
        },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_valid_id',
      isBatchPaymentPages: true,
    };
    renderApp(initialState, props, true);
    await waitForLoadingToFinish();
    const notifyBatch = screen.getAllByText('Send all links')[1];
    expect(notifyBatch).toBeInTheDocument();
    await userEvent.click(notifyBatch);
    expect(screen.getByText('Send Reminder?')).toBeInTheDocument();
    expect(
      screen.getByText('Are you sure that you want to send the unpaid links again?'),
    ).toBeInTheDocument();
    const sendSms = screen.getByText('Send SMS');
    expect(sendSms).toBeInTheDocument();
    expect(screen.getByText('Send Email')).toBeInTheDocument();
    await userEvent.click(sendSms);
    const sendAllButton = screen.getByRole('button', { name: 'Yes, Send All' });
    expect(sendAllButton).toBeInTheDocument();
    await userEvent.click(sendAllButton);
    await waitFor(() =>
      expect(
        screen.getByText(/All payment links of this batch will be sent shortly/i),
      ).toBeInTheDocument(),
    );
  });

  test('should able to render list of batches & show error message on notify batch', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_notify_error_test',
      isBatchPaymentPages: true,
    };
    server.use(paymentPagesErrorHandlers.batchPaymentPagesFetchNotifyDetails());
    renderApp(initialState, props, true);
    await waitForLoadingToFinish();
    const notifyBatch = screen.getAllByText('Send all links')[0];
    expect(notifyBatch).toBeInTheDocument();
    await userEvent.click(notifyBatch);
    expect(screen.getByText('Send Reminder?')).toBeInTheDocument();
    expect(
      screen.getByText('Are you sure that you want to send the unpaid links again?'),
    ).toBeInTheDocument();
    const sendSms = screen.getByText('Send SMS');
    expect(sendSms).toBeInTheDocument();
    expect(screen.getByText('Send Email')).toBeInTheDocument();
    await userEvent.click(sendSms);
    const sendAllButton = screen.getByRole('button', { name: 'Yes, Send All' });
    expect(sendAllButton).toBeInTheDocument();
    await userEvent.click(sendAllButton);
    await waitFor(() =>
      expect(
        screen.getByText(/The requested URL was not found on the server/i),
      ).toBeInTheDocument(),
    );
  });

  test('should able to show message "No Batch Files Found" when there is no any batch found', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
    };
    const props = {
      ...defaultProps,
      id: 'pl_invalid_id',
      isBatchPaymentPages: true,
    };
    server.use(paymentPagesErrorHandlers.batchPaymentPageGetBatchesError());
    renderApp(initialState, props, true);
    await waitForLoadingToFinish();
    expect(screen.getByText(/No Batch Files Found/i)).toBeInTheDocument();
  });
});
