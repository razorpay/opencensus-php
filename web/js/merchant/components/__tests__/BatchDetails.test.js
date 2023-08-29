import {
  batchDetails,
  NO_RESULT_FOUND,
  REPORT_LABEL,
  BATCH_ID,
  BATCH_NAME,
  renderApp,
} from 'merchant/components/__tests__/fixtures/mocks/BatchDetails';
import { screen, userEvent } from 'test-utils';

describe('BatchDetails', () => {
  test('should show loader', () => {
    renderApp({
      props: {
        isLoading: true,
      },
    });
    const loader = screen.getByTestId('spinner');
    expect(loader).toBeInTheDocument();
  });

  test('should show message "No results found for given id"', () => {
    renderApp({
      props: {
        isLoading: false,
      },
    });
    const noResultsMessage = screen.getByText(NO_RESULT_FOUND);
    expect(noResultsMessage).toBeInTheDocument();
  });

  test('should show batch details & able to download report', async () => {
    renderApp({
      props: {
        isLoading: false,
        batch: batchDetails,
        onDownload: jest.fn(),
      },
    });

    const downloadReportEl = screen.getByTestId('download-report');
    const downloadReportLabel = screen.getByText('Download the report containing all data.');
    expect(downloadReportEl).toBeInTheDocument();
    // Click to download report
    await userEvent.click(downloadReportEl);
    expect(downloadReportLabel).toBeInTheDocument();
  });

  test('should show download report label as "Download Batch Payment Page Report"', () => {
    renderApp({
      props: {
        isLoading: false,
        batch: batchDetails,
        onDownload: jest.fn(),
        downloadReportText: () => {
          return REPORT_LABEL;
        },
        renderDetails: jest.fn(),
      },
    });

    const downloadReportEl = screen.getByTestId('download-report');
    const downloadReportLabel = screen.getByText(REPORT_LABEL);
    expect(downloadReportEl).toBeInTheDocument();
    expect(downloadReportLabel).toBeInTheDocument();
  });

  test('should show batch ID instead of batch name', () => {
    renderApp({
      props: {
        isLoading: false,
        batch: { name: null, id: BATCH_ID },
      },
    });

    const labelEl = screen.getByText(BATCH_ID);
    expect(labelEl).toBeInTheDocument();
  });

  test('should show batch name instead of batch ID', () => {
    renderApp({
      props: {
        isLoading: false,
        batch: { name: BATCH_NAME, id: BATCH_ID },
      },
    });
    const labelEl = screen.getByText('Test batch name with mor...');
    expect(labelEl).toBeInTheDocument();
  });
});
