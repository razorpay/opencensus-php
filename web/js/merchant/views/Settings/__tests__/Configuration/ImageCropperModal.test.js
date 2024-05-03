import {
  UPLOADING_IMAGE,
  FILE_UPLOADED_SUCCESSFULLY,
} from 'merchant/views/Settings/Configuration/constants';
import {
  renderApp,
  defaultProps,
} from 'merchant/views/Settings/__tests__/mocks/fixtures/Configuration/ImageCropperModal';
import { updateConfig } from 'merchant/views/Settings/__tests__/mocks/handlers';
import { screen, server, userEvent, waitFor } from 'test-utils';

describe('ImageCropperModal', () => {
  test('should render modal & able to upload image', async () => {
    const initialState = {};
    const props = {
      ...defaultProps,
      showBranding: true,
    };
    server.use(updateConfig());
    renderApp(initialState, props, true);
    expect(screen.getByText('Adjust Image')).toBeInTheDocument();
    expect(
      screen.getByText('Crop and resize image for clear logo visibility with minimal white space.'),
    ).toBeInTheDocument();
    const saveButton = screen.getByRole('button', { name: 'Save' });
    expect(saveButton).toBeInTheDocument();
    await userEvent.click(saveButton);
    expect(screen.getByText(UPLOADING_IMAGE)).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText(FILE_UPLOADED_SUCCESSFULLY)).toBeInTheDocument();
    });
  });
});
