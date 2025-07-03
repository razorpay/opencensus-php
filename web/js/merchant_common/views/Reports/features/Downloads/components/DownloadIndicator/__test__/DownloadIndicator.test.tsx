import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import { DownloadIndicator } from 'merchant_common/views/Reports/features/Downloads/components/DownloadIndicator';
import * as notifications from 'merchant_common/reducers/notifications';
import * as downloadFile from 'merchant/utils/downloadFile';
import {
  GENERATED_REPORT_FILE_DOWNLOAD_FAILED,
  GENERATED_REPORT_FILE_DOWNLOAD_SUCCESS,
} from 'merchant_common/views/Reports/constants/notifications';
import { trackDownloadsSection } from 'merchant_common/views/Reports/configs/analytics.config';

const showNotificationSpy = jest.spyOn(notifications, 'showNotification');
const downloadFileSpy = jest.spyOn(downloadFile, 'downloadFromUFH');
downloadFileSpy.mockImplementation(() => {
  return Promise.resolve();
});

describe('DownloadIndicator', () => {
  const App = (props) => {
    return <DownloadIndicator {...props} trackDownloadFile={trackDownloadsSection} />;
  };

  test('should render card component without any error', () => {
    expect(() => render(<App />)).not.toThrow();
  });

  ['created', 'processing', 'retrying'].forEach((status) => {
    it(`should show loading spinner for ${status} status`, () => {
      render(<App status={status} />);
      expect(screen.getByLabelText('Loading please wait...')).toBeInTheDocument();
    });
  });

  it('should show download icon when status is processed and fileId exist', () => {
    render(<App status="processed" file_id="dummyId" />);
    expect(screen.getByLabelText('Download Report')).toBeInTheDocument();
  });

  it('should show success notification status on download start', async () => {
    render(<App status="processed" file_id="rId" />);
    const downloadReportButton = screen.getByLabelText('Download Report');
    await userEvent.click(downloadReportButton);
    await expect(downloadFileSpy).toHaveBeenCalledTimes(1);
    await expect(downloadFileSpy).toHaveReturned();
    await expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    const successMessage = await screen.findByText(GENERATED_REPORT_FILE_DOWNLOAD_SUCCESS);
    await expect(successMessage).toBeInTheDocument();
  });

  it('should show error notification status on download failed', async () => {
    render(<App status="processed" file_id="rId" consumer="mId" />, {
      initialState: {
        session: {
          user: {
            current: 'eId',
            international: 'inId',
          },
        },
      },
    });
    const downloadReportButton = screen.getByLabelText('Download Report');
    downloadFileSpy.mockClear().mockImplementation(() => {
      return Promise.reject();
    });
    await userEvent.click(downloadReportButton);
    await expect(downloadFileSpy).toHaveBeenCalledTimes(1);
    await expect(downloadFileSpy).toHaveReturned();
    await expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    const errMessage = await screen.findByText(GENERATED_REPORT_FILE_DOWNLOAD_FAILED);
    await expect(errMessage).toBeInTheDocument();
  });
});
