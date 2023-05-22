import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/BatchList';

describe('Batch List', () => {
  beforeAll(() => {
    window.hj = jest.fn();
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      onbr: () => ({
        success: jest.fn(),
      }),
    };
  });

  const renderApp = (props = {}) => {
    return render(<App {...props} />, {
      showModal: true,
      initialState: {
        session: {
          user: {
            isPaymentlinksV2Enabled: true,
            isAllowedView: () => true,
            merchant: {
              country_code: 'IN',
            },
          },
        },
      },
    });
  };

  test('Batch Upload List component to be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render "Batch Upload Id" as label', () => {
    renderApp();
    expect(screen.getByText('Batch Upload Id')).toBeInTheDocument();
  });

  test('should render all batch list column', () => {
    renderApp();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
    expect(screen.getByText('Batch Name')).toBeInTheDocument();
    expect(screen.getAllByText('Count')[0]).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Actions')).toBeInTheDocument();
  });

  test('should have "Download Sample File" in document', () => {
    renderApp();
    const downloadSampleFile = screen.getAllByText('Download Sample File')[0];
    expect(downloadSampleFile).toBeInTheDocument();
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
});
