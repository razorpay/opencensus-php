import { render, screen, waitForLoadingToFinish, server } from 'test-utils';
import { App } from 'merchant/views/Transactions/__tests__/mocks/fixtures/BatchPayments/BatchUpload';
import { getMerchantTokenSuccess } from 'merchant/views/Transactions/__tests__/mocks/handlers';

describe('BatchUploadContainer', () => {
  test('should render iframe on batch upload modal', async () => {
    server.use(getMerchantTokenSuccess());
    render(<App />);
    await waitForLoadingToFinish();
    expect(screen.getByTestId('batch-upload-modal')).toBeInTheDocument();
  });
});
