import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import * as analytics from 'common/utils/analytics';
import {
  defaultProps,
  allConfigs,
  logItems,
} from 'merchant_common/containers/ReportsAsync/Logs/__test__/mocks/fixtures/List';
import LogList from 'merchant_common/containers/ReportsAsync/Logs/List';
import { DEFAULT_FILE_FORMAT } from 'merchant_common/containers/ReportsAsync/Logs/Item';

jest.mock('merchant_common/containers/ReportsAsync/Logs/Item', () => ({
  __esModule: true,
  ...jest.requireActual('merchant_common/containers/ReportsAsync/Logs/Item'),
  default: ({ config, onDownloadClick, pollLog, status, file_id, ...log }) => {
    if (status === 'processing') {
      pollLog();
    }

    return (
      <div data-testid="log-item">
        {status === 'processed' && !!file_id && (
          <button
            onClick={() =>
              onDownloadClick(
                { target: { dataset: { fileId: file_id, consumerId: log.generated_by } } },
                config.name,
                'csv',
                log.start_time,
                log.end_time,
              )
            }
            type="button"
          >
            Download
          </button>
        )}
        <span data-testid="status">{status}</span>
        <span data-testid="config-name">{config.name}</span>
      </div>
    );
  },
}));

const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

const App = (props) => <LogList {...defaultProps} {...props} />;

describe('Log list', () => {
  test('should show log items', () => {
    render(<App />);
    expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
    expect(screen.getByText('Recent Reports')).toBeInTheDocument();
    expect(screen.queryAllByTestId('log-item')).toHaveLength(defaultProps.items.length);
    expect(defaultProps.pollLog).not.toHaveBeenCalled();
    expect(screen.getByText('Nothing more to load')).toBeInTheDocument();

    screen.getAllByTestId('config-name').forEach((element, idx) => {
      expect(element).toHaveTextContent(allConfigs[idx].name);
    });
  });

  test('should show spinner when pending is true and items are less than 1', () => {
    render(<App pending items={[]} />);
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test("should show load more when there're more items to fetch", async () => {
    const { rerender } = render(<App allFetched={false} />);
    const loadMoreButton = screen.getByRole('button', { name: 'Load More' });
    expect(loadMoreButton).toBeInTheDocument();
    await userEvent.click(loadMoreButton);
    expect(defaultProps.onLoadMoreClick).toHaveBeenCalled();
    rerender(<App allFetched={false} pending />);
    expect(screen.getByText(/Loading more requests/i)).toBeInTheDocument();
  });

  test('should call pollLog when status is processing', () => {
    render(<App items={[{ ...logItems[0], status: 'processing' }]} />);
    expect(defaultProps.pollLog).toHaveBeenCalled();
  });

  describe('Download button', () => {
    beforeEach(() => {
      window.rzpQ = {
        component: jest.fn(),
        reporting: () => ({
          success: jest.fn(),
        }),
      };
    });

    test('should show up when status is processed and file id exists', async () => {
      render(<App items={[logItems[1]]} />);
      const downloadButton = screen.getByRole('button', { name: 'Download' });
      expect(downloadButton).toBeInTheDocument();
      await userEvent.click(downloadButton);
    });

    const downloadFile = async (logItem, props = {}) => {
      render(<App items={[logItem]} {...props} />);
      const downloadButton = screen.getByRole('button', { name: 'Download' });
      expect(downloadButton).toBeInTheDocument();
      await userEvent.click(downloadButton);
    };

    test('should download file and call analytics on click', async () => {
      const logItemToBeDownloaded = logItems[1];
      await downloadFile(logItemToBeDownloaded);
      await waitFor(() => {
        expect(window.rzpQ.component).toHaveBeenCalledWith('LogList');

        expect(analyticsTrackMock).toHaveBeenCalledWith({
          objectName: 'download report',
          actionName: 'result',
          screen: 'reports',
          properties: {
            location: 'generate reports',
            status: 'Success',
            reportType: allConfigs[1].name,
            format: DEFAULT_FILE_FORMAT,
            reportStartTime: logItemToBeDownloaded.start_time,
            reportEndTime: logItemToBeDownloaded.end_time,
          },
        });
      });
    });

    test('should call analytics and show notification on download file error', async () => {
      const logItemToBeDownloaded = logItems[1];
      logItemToBeDownloaded.file_id = 'error-id';
      await downloadFile(logItemToBeDownloaded);
      await waitFor(() => {
        expect(analyticsTrackMock).toHaveBeenCalledWith({
          objectName: 'download report',
          actionName: 'result',
          screen: 'reports',
          properties: {
            location: 'generate reports',
            status: 'Failure',
            failureReason: 'file cannot be downloaded',
          },
        });
        expect(screen.getByText('file cannot be downloaded')).toBeInTheDocument();
      });
    });

    test("should include consumerId in the download file payload and show no error on download file when there's no error in response", async () => {
      const logItemToBeDownloaded = logItems[1];
      logItemToBeDownloaded.file_id = 'no-error-response';
      await downloadFile(logItemToBeDownloaded, { allConfigs: [], currentMerchantId: 'test' });

      await waitFor(() => {
        expect(analyticsTrackMock).toHaveBeenCalledWith({
          objectName: 'download report',
          actionName: 'result',
          screen: 'reports',
          properties: {
            location: 'generate reports',
            status: 'Failure',
            failureReason: undefined,
          },
        });
      });
    });
  });
});
