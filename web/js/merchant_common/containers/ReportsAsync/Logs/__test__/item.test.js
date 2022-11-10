import { render, screen, userEvent } from 'common/services/test/test-utils';
import {
  defaultProps,
  configData,
} from 'merchant_common/containers/ReportsAsync/Logs/__test__/mocks/fixtures/item';
import * as analytics from 'common/utils/analytics';
import { getActualLogStatus } from 'merchant_common/containers/ReportsAsync/utils';
import LogItem, {
  logItemInfoMessages,
  DEFAULT_FILE_FORMAT,
} from 'merchant_common/containers/ReportsAsync/Logs/Item';

jest.mock('merchant_common/containers/ReportsAsync/utils', () => ({
  ...jest.requireActual('merchant_common/containers/ReportsAsync/utils'),
  getFormattedDate: (date) => date,
}));

jest.mock('merchant_common/containers/ReportsAsync/Logs/components/LogStatus', () => ({
  __esModule: true,
  default: ({ actualStatus, onDownloadClick }) => {
    return (
      <>
        {actualStatus === 'ready-for-download' && (
          <button onClick={onDownloadClick} type="button">
            Download
          </button>
        )}
        <span data-testid="actual-status">{actualStatus}</span>
      </>
    );
  },
}));

export const App = (props) => <LogItem {...defaultProps} {...props} />;

describe('Log Item', () => {
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  const checkActualStatus = (status) => {
    expect(screen.getByTestId('actual-status')).toHaveTextContent(status);
  };

  const checkLogItemInfo = (status) => {
    expect(screen.getByTestId('log-item-info-message')).toHaveTextContent(
      logItemInfoMessages[status],
    );
  };

  const checkFileFormat = (fileFormat) => {
    expect(screen.getByTestId('file-format')).toHaveTextContent(new RegExp(fileFormat, 'i'));
  };

  test('should not render download button when status is processed and no file to download', () => {
    render(<App />);
    expect(screen.getByText(configData.name)).toBeInTheDocument();
    const reportDuration = screen.getByTestId('report-duration');
    expect(reportDuration).toHaveTextContent(
      `${defaultProps.start_time} - ${defaultProps.end_time}`,
    );
    const actualStatus = getActualLogStatus({
      status: defaultProps.status,
      fileId: defaultProps.file_id,
    });
    checkActualStatus(actualStatus);
    expect(screen.queryByRole('button', { name: 'Download' })).not.toBeInTheDocument();
    checkLogItemInfo(actualStatus);
    checkFileFormat(DEFAULT_FILE_FORMAT);
  });

  test('should poll log when status is created/processing', () => {
    const abortPollingMock = jest.fn();
    const pollLogMock = jest.fn((...args) => {
      const cb = args[2];
      cb({ abort: abortPollingMock });
      return null;
    });
    const consumer = 'test-consumer';
    const status = 'created';
    const { unmount } = render(<App pollLog={pollLogMock} status={status} consumer={consumer} />);
    const actualStatus = getActualLogStatus({
      status,
      fileId: defaultProps.file_id,
    });
    checkActualStatus(actualStatus);
    expect(pollLogMock).toHaveBeenCalledWith(defaultProps.id, consumer, expect.any(Function));
    unmount();
    expect(abortPollingMock).toHaveBeenCalled();
  });

  test('should show download button when fileId exists', async () => {
    const fileId = 'test-file-id';
    const onDownloadClick = jest.fn();
    render(<App file_id={fileId} onDownloadClick={onDownloadClick} />);

    const actualStatus = getActualLogStatus({
      status: defaultProps.status,
      fileId,
    });
    checkActualStatus(actualStatus);
    const downloadButton = screen.getByRole('button', { name: 'Download' });
    expect(downloadButton).toBeInTheDocument();
    await userEvent.click(downloadButton);
    expect(analyticsTrackMock).toHaveBeenCalledWith({
      objectName: 'download report',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        reportType: defaultProps.config.name,
        format: DEFAULT_FILE_FORMAT,
        reportStartTime: defaultProps.start_time,
        reportEndTime: defaultProps.end_time,
      },
    });
    expect(onDownloadClick).toHaveBeenCalledWith(
      expect.any(Object),
      defaultProps.config.name,
      DEFAULT_FILE_FORMAT,
      defaultProps.start_time,
      defaultProps.end_time,
    );
  });

  test("should show -- when config doesn't have a name", () => {
    render(<App config={{ ...defaultProps.config, name: '' }} isNew />);
    expect(screen.getByText('--')).toBeInTheDocument();
  });

  test('should show only start time when start time and end time are equal', () => {
    render(<App end_time={defaultProps.start_time} />);
    const reportDuration = screen.getByTestId('report-duration');
    expect(reportDuration).not.toHaveTextContent(
      `${defaultProps.start_time} - ${defaultProps.start_time}`,
    );
    expect(reportDuration).toHaveTextContent(defaultProps.start_time);
  });
});
