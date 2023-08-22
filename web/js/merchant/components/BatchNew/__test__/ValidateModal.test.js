import ValidateModal from 'merchant/components/BatchNew/ValidateModal';
import { render, screen } from 'test-utils';

describe('ValidateModal', () => {
  test('should render download sample file as link when sample file url is present', () => {
    render(<ValidateModal batchType="payment_link_v2" sampleUrl="/files/sample_file.xlsx" />);

    expect(
      screen.getByRole('link', { name: 'batch-upload-download-sample-file' }),
    ).toBeInTheDocument();
  });

  test('should render download sample file as button when sample file url is not present', () => {
    render(<ValidateModal batchType="payment_link_v2" shouldShowSampleDownloadBtn />);

    expect(
      screen.getByRole('button', { name: 'batch-upload-download-sample-file' }),
    ).toBeInTheDocument();
  });
});
