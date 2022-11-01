import { render, screen } from 'test-utils';
import BatchUploadContainer from 'merchant/views/Transactions/BatchRefunds/BatchUpload';

describe('BatchRefunds - BatchUpload.js', () => {
  test('should render batch upload component', () => {
    render(
      <BatchUploadContainer
        location={{
          search: '',
        }}
      />,
      {
        initialState: {
          app: {
            isMobileResolution: true,
          },
        },
      },
    );
    expect(screen.getByTestId('batchrefunds-batchupload')).toBeInTheDocument();
  });
});
