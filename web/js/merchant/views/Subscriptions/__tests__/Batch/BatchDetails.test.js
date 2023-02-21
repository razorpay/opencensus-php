import { screen, server, render, waitForLoadingToFinish } from 'test-utils';
import { fetchBatchDetails } from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/fixtures';
import App from 'merchant/views/Subscriptions/Batch/Details';

describe('Render Batch Details', () => {
  beforeEach(async () => {
    server.use(fetchBatchDetails());
    render(<App id={1} />);
    await waitForLoadingToFinish();
  });

  test('should render all the registration link fields', () => {
    [
      'batch type',
      'batch name',
      'status',
      '^created$',
      'download the report containing all registration links data.',
      'total rows',
      'payments created',
      'rows failed',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    expect(
      screen.getByRole('button', {
        name: /download report/i,
      }),
    ).toBeInTheDocument();
  });

  test('should render all the mocked values for registration link fields', () => {
    ['^registration link$', '^processed$', '04 jan 2023'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    expect(screen.getAllByText(/auth_link/i)[0]).toBeInTheDocument();
    expect(screen.getAllByText(/auth_link/i)[1]).toBeInTheDocument();
  });
});
