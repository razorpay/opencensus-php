import { render, screen, userEvent } from 'common/services/test/test-utils';
import LogStatus from 'merchant_common/containers/ReportsAsync/Logs/components/LogStatus';

describe('Log Status', () => {
  const defaultProps = {
    actualStatus: '',
    onDownloadClick: jest.fn(),
    fileId: 'fileId',
    consumerId: 'consumerId',
  };

  const App = (props) => <LogStatus {...defaultProps} {...props} />;

  const validateLogStatusText = (text) => {
    expect(screen.getByTestId('log-status-text')).toHaveTextContent(text);
  };

  test('should show no text when status is empty', () => {
    render(<App />);
    validateLogStatusText('');
  });

  test('should show generating text when status is in-process', () => {
    render(<App actualStatus="in-process" />);
    validateLogStatusText(/Generating/i);
  });

  test('should show no data text when status is no-data', () => {
    render(<App actualStatus="no-data" />);
    validateLogStatusText('No data available');
  });

  test('should show download button when status is ready-for-download', async () => {
    render(<App actualStatus="ready-for-download" />);
    const downloadButton = screen.getByRole('button', { name: 'Download' });
    expect(downloadButton).toBeInTheDocument();
    expect(downloadButton).toHaveAttribute('data-file-id', defaultProps.fileId);
    expect(downloadButton).toHaveAttribute('data-consumer-id', defaultProps.consumerId);
    await userEvent.click(downloadButton);
    expect(defaultProps.onDownloadClick).toHaveBeenCalled();
  });

  test('should show failed text when status is error', () => {
    render(<App actualStatus="error" />);
    validateLogStatusText('Failed');
  });
});
