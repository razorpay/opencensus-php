import React from 'react';

import { exportFileAsExcel } from 'common/utils/rzp-utils';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/BatchList';
import {
  fetchDynamicFieldsSuccess,
  fetchDynamicFieldsError,
} from 'merchant/views/PaymentLinks/__test__/mocks/handlers';
import { showDynamicFields, convertExcelToObj } from 'merchant/views/PaymentLinks/utils';
import { render, screen, userEvent, server, waitFor } from 'test-utils';

jest.mock('merchant/views/PaymentLinks/utils', () => ({
  ...jest.requireActual('merchant/views/PaymentLinks/utils'),
  showDynamicFields: jest.fn(),
  convertExcelToObj: jest.fn(),
}));

jest.mock('common/utils/rzp-utils', () => ({
  ...jest.requireActual('common/utils/rzp-utils'),
  exportFileAsExcel: jest.fn(),
}));

const DEFAULT_INITIAL_STATE = {
  session: {
    user: {
      isPaymentlinksV2Enabled: true,
      isAllowedView: () => true,
      isOrgAllowedFunctionality: () => true,
      merchant: {
        country_code: 'IN',
      },
    },
  },
};

describe('Batch List', () => {
  beforeAll(() => {
    window.hj = jest.fn();
    window.rzp_user = {};
    window.open = jest.fn();

    window.rzpQ = {
      component: jest.fn(),
      onbr: () => ({
        success: jest.fn(),
      }),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  const renderApp = (props = {}, initialState = DEFAULT_INITIAL_STATE) => {
    return render(<App {...props} />, { showModal: true, initialState });
  };

  test('Batch Upload List component to be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render "Batch Upload Id" as label', () => {
    renderApp();
    expect(screen.getByText('Batch Upload Id')).toBeInTheDocument();
  });

  test('should render all batch list column', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Batch Id')).toBeInTheDocument();
      expect(screen.getByText('Batch Name')).toBeInTheDocument();
      expect(screen.getAllByText('Count')[0]).toBeInTheDocument();
      expect(screen.getByText('Status')).toBeInTheDocument();
      expect(screen.getByText('Actions')).toBeInTheDocument();
    });
  });

  test('should render "Upload Batch File" Modal', async () => {
    renderApp();
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

  test('should be able to download sample file', async () => {
    showDynamicFields.mockReturnValue(false);

    const session = {
      ...DEFAULT_INITIAL_STATE.session,
      user: { ...DEFAULT_INITIAL_STATE.session.user, isPaymentlinksV2Enabled: false },
    };

    renderApp({}, { ...DEFAULT_INITIAL_STATE, session });

    await userEvent.click(screen.getByLabelText('download-sample-file'));

    expect(window.open).toHaveBeenCalledTimes(1);
  });

  test('should be able to download sample file and the downloaded file should contain the dynamic field created', async () => {
    server.use(fetchDynamicFieldsSuccess());

    showDynamicFields.mockReturnValue(true);
    convertExcelToObj.mockReturnValue([
      {
        'Reference Id': 'Ref12345',
        'Customer Name': 'XYZ',
        'Customer Email': 'abcd@gmail.com',
        'Customer Contact': 9999999999,
        'Upi Link': 1,
        Currency: 'INR',
        Amount: 100,
        Description: "Test payment link's description",
        'Partial Payment': 0,
        'notes[charge]': 'Demonstration',
      },
    ]);

    renderApp();

    await userEvent.click(screen.getByLabelText('download-sample-file'));

    await waitFor(() => expect(exportFileAsExcel).toHaveBeenCalledTimes(1));
  });

  test('should throw error if dynamic fields are not fetched properly', async () => {
    server.use(fetchDynamicFieldsError());

    showDynamicFields.mockReturnValue(true);
    convertExcelToObj.mockReturnValue([]);

    renderApp();

    await userEvent.click(screen.getByLabelText('download-sample-file'));

    await waitFor(() => {
      expect(screen.getByTestId('Notification--error')).toHaveTextContent(
        'Something went wrong Status Code: 404',
      );
    });
  });

  test('should be able to download sample file from batch upload modal', async () => {
    server.use(fetchDynamicFieldsSuccess(null));

    showDynamicFields.mockReturnValue(true);
    convertExcelToObj.mockReturnValue([]);

    renderApp();

    // Open the batch upload modal.
    await userEvent.click(screen.getByRole('button', { name: /Click here to upload/ }));
    // Download sample file.
    await userEvent.click(screen.getByLabelText('batch-upload-download-sample-file'));

    await waitFor(() => expect(exportFileAsExcel).toHaveBeenCalledTimes(1));
  });
});
