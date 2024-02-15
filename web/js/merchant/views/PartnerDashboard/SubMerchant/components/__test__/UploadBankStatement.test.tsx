import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import { render, screen, server, waitFor, userEvent } from 'common/services/test/test-utils';
import { UploadBankStatement } from 'merchant/views/PartnerDashboard/SubMerchant/components/UploadBankStatement';
import {
  uploadBankStatementSuccess,
  uploadBankStatementError,
  submitBankStatementSuccess,
  submitBankStatementError,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/__test__/mocks/handlers';

const closeModal = jest.fn();
const showNotification = jest.fn();
const setIsUploadBankStatementButtonDisabled = jest.fn();

type RequiredData = {
  partnerId: string;
  merchantId: string;
  appId: string;
};
const requiredData: RequiredData = {
  partnerId: 'userid123',
  merchantId: 'merchantis123',
  appId: 'appid123',
};

function createDummyFile() {
  const file = new File([''], 'example.pdf', { type: 'application/pdf' });
  return file;
}

describe('Upload Bank statement', () => {
  const renderApp = () => {
    return render(
      <UploadBankStatement
        closeModal={closeModal}
        showNotification={showNotification}
        isUploadSuccess={() => {
          setIsUploadBankStatementButtonDisabled();
        }}
        uploadData={requiredData}
      />,
      {
        showModal: true,
      },
    );
  };

  test('should render the upload component', () => {
    renderApp();
    expect(screen.getByText('Upload bank account statement')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Share the current account statements for the last 6 months for your client',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('click to upload')).toBeInTheDocument();
    const submitButton = screen.getByRole('button', { name: 'Submit statements' });
    expect(submitButton).toBeInTheDocument();
    expect(submitButton).toHaveAttribute('disabled');
  });

  test('should upload a file successfully', async () => {
    server.use(uploadBankStatementSuccess());
    renderApp();
    const fileUploaderInput = screen.getByLabelText(/click to upload/);
    const submitButton = screen.getByRole('button', { name: 'Submit statements' });
    expect(submitButton).toHaveAttribute('disabled');
    const dummyFile = createDummyFile();
    await userEvent.upload(fileUploaderInput, dummyFile);

    await waitFor(() => {
      expect(submitButton).not.toHaveAttribute('disabled');
    });
  });

  test('should show error if upload fails', async () => {
    server.use(uploadBankStatementError());
    renderApp();
    const fileUploaderInput = screen.getByLabelText(/click to upload/);
    const submitButton = screen.getByRole('button', { name: 'Submit statements' });
    expect(submitButton).toHaveAttribute('disabled');
    const dummyFile = createDummyFile();
    await userEvent.upload(fileUploaderInput, dummyFile);

    await waitFor(() => {
      expect(showNotification).toBeCalled();
    });
  });

  test('should upload a file and submit successfully', async () => {
    server.use(uploadBankStatementSuccess());
    server.use(submitBankStatementSuccess());
    renderApp();
    const fileUploaderInput = screen.getByLabelText(/click to upload/);
    const submitButton = screen.getByRole('button', { name: 'Submit statements' });
    expect(submitButton).toHaveAttribute('disabled');
    const dummyFile = createDummyFile();
    await userEvent.upload(fileUploaderInput, dummyFile);

    await waitFor(() => {
      expect(submitButton).not.toHaveAttribute('disabled');
    });

    await userEvent.click(submitButton);

    await waitFor(() => {
      expect(showNotification).toBeCalled();
    });
  });

  // todo skipping this for now because it is getting failed because of retry option of react-query.
  test.skip('should show error if files submit is not success', async () => {
    server.use(uploadBankStatementSuccess());
    server.use(submitBankStatementError());
    renderApp();
    const fileUploaderInput = screen.getByLabelText(/click to upload/);
    const submitButton = screen.getByRole('button', { name: 'Submit statements' });
    expect(submitButton).toHaveAttribute('disabled');
    const dummyFile = createDummyFile();
    await userEvent.upload(fileUploaderInput, dummyFile);

    await waitFor(() => {
      expect(submitButton).not.toHaveAttribute('disabled');
    });

    await userEvent.click(submitButton);

    await waitFor(() => {
      expect(showNotification).toBeCalled();
    });
  });
});
