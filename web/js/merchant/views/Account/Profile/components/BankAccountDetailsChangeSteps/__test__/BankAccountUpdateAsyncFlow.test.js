import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { fireEvent, render, screen, waitFor } from 'test-utils';

import BankAccountUpdateAsyncFlow from '../BankAccountUpdateAsyncFlow';
import {
  BANK_ACCOUNT_UPDATE_UNDER_REVIEW,
  BANK_VERIFICATION_LETTER_UPLOAD,
  CANCELLED_CHEQUE_VIDEO_UPLOAD,
} from '../constants';
import * as bankAccountUpdateUtils from '../utils';

jest.mock('merchant/components/File/Upload', () => ({
  ...jest.requireActual('merchant/components/File/Upload'),
  __esModule: true,
  default: ({ files, onCloseClick, onFileChange, onBiggerFileSize }) => {
    if (files.length) {
      return (
        <>
          {files.map((file) => (
            <div key={file}>
              {file}{' '}
              <button type="button" onClick={onCloseClick}>
                X
              </button>
            </div>
          ))}
        </>
      );
    }
    const handleFileChange = ({ target: { files } }) =>
      files[0] === 'BIGGER_FILE' ? onBiggerFileSize() : onFileChange(files[0]);

    return (
      <div>
        <label htmlFor="uploadFile">Upload file</label>
        <input
          type="file"
          data-testid="uploadFile"
          id="uploadFile"
          name="uploadFile"
          onChange={handleFileChange}
        />
      </div>
    );
  },
}));

describe('BankAccountUpdateAsyncFlow', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
  const trackBankAccountDetailsChangeSpy = jest.spyOn(
    bankAccountUpdateUtils,
    'trackBankAccountDetailsChange',
  );

  beforeEach(() => {
    showNotificationSpy.mockClear();
    trackBankAccountDetailsChangeSpy.mockClear();
  });

  const App = () => <BankAccountUpdateAsyncFlow />;

  test('should initially render title and subtitle on the screen to upload a bank proof', () => {
    render(<App />);
    expect(screen.getByText("Your given bank account couldn't be verified.")).toBeInTheDocument();
    expect(
      screen.getByText('Upload a bank proof (any one) for our team to review:'),
    ).toBeInTheDocument();
  });

  describe('Watch sample video', () => {
    test('should render watch sample video link', () => {
      render(<App />);
      expect(screen.getByText('Watch sample video')).toBeInTheDocument();
    });

    test('should fire tracking event when watch sample video link is clicked', () => {
      render(<App />);
      const watchSampleVideo = screen.getByText('Watch sample video');
      fireEvent.click(watchSampleVideo);
      expect(trackBankAccountDetailsChangeSpy).toHaveBeenCalledWith({
        objectName: 'Sample Video CTA',
        actionName: 'Clicked',
      });
    });
  });

  describe('Form submit', () => {
    test('should show bank account update under review screen when video of cancelled cheque is uploaded', async () => {
      render(<App />);
      const fileUpload = screen.getByTestId('uploadFile');
      fireEvent.change(fileUpload, { target: { files: ['VALID_FILE'] } });
      const submitButton = screen.getByText('Submit');
      await waitFor(() => {
        expect(screen.getByText('VALID_FILE')).toBeInTheDocument();
        expect(submitButton).toBeEnabled();
      });
      fireEvent.click(submitButton);
      await waitFor(() => {
        expect(screen.getByText(CANCELLED_CHEQUE_VIDEO_UPLOAD.title)).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(screen.getByText(BANK_ACCOUNT_UPDATE_UNDER_REVIEW.title)).toBeInTheDocument();
      });
    });

    test('should show bank account update under review screen when bank verification letter is uploaded', async () => {
      render(<App />);
      const bankVerificationLetterOption = screen.getByText('Bank Verification letter');
      fireEvent.click(bankVerificationLetterOption);
      const fileUpload = screen.getByTestId('uploadFile');
      fireEvent.change(fileUpload, { target: { files: ['VALID_FILE'] } });
      const submitButton = screen.getByText('Submit');
      await waitFor(() => {
        expect(screen.getByText('VALID_FILE')).toBeInTheDocument();
        expect(submitButton).toBeEnabled();
      });
      fireEvent.click(submitButton);
      await waitFor(() => {
        expect(screen.getByText(BANK_VERIFICATION_LETTER_UPLOAD.title)).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(screen.getByText(BANK_ACCOUNT_UPDATE_UNDER_REVIEW.title)).toBeInTheDocument();
      });
    });

    test('should show notification error message when a bank proof is failed to be uploaded', async () => {
      render(<App />);
      const fileUpload = screen.getByTestId('uploadFile');
      fireEvent.change(fileUpload, { target: { files: ['INVALID_FILE'] } });
      const submitButton = screen.getByText('Submit');
      await waitFor(() => {
        expect(screen.getByText('INVALID_FILE')).toBeInTheDocument();
        expect(submitButton).toBeEnabled();
      });
      fireEvent.click(submitButton);
      await waitFor(() => {
        expect(screen.getByText(CANCELLED_CHEQUE_VIDEO_UPLOAD.title)).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(NotificationsActions.showNotification).toHaveBeenCalledWith({
          type: 'error',
          message: ['Network Error'],
        });
      });
    });
  });

  describe('File upload', () => {
    test('should allow uploaded file to be removed', async () => {
      render(<App />);
      const fileUpload = screen.getByTestId('uploadFile');
      fireEvent.change(fileUpload, { target: { files: ['VALID_FILE'] } });
      await waitFor(() => {
        expect(screen.getByText('VALID_FILE')).toBeInTheDocument();
      });
      const removeButton = screen.getByText('X');
      fireEvent.click(removeButton);
      expect(screen.queryByText('VALID_FILE')).not.toBeInTheDocument();
    });

    test('should show notification error message when a bigger file is uploaded', async () => {
      render(<App />);
      const fileUpload = screen.getByTestId('uploadFile');
      fireEvent.change(fileUpload, { target: { files: ['BIGGER_FILE'] } });
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: 'File exceeds total upload limit of 50MB!',
        });
      });
    });
  });
});
