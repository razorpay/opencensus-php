import { userEvent, waitFor, screen } from 'test-utils';
import {
  renderApp,
  onUpdateClick,
  onModalDismiss,
} from 'merchant/views/AccountAndSettings/common/components/ModalForm/__tests__/mocks/fixtures/ModalForm';
import { PersonalProfileFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

describe('ModalForm', () => {
  test('should render nothing when entity is undefined', () => {
    renderApp({ entity: null });
    expect(screen.queryByText('Update details')).not.toBeInTheDocument();
  });

  test('should render the modal form with default values', () => {
    renderApp();
    expect(screen.getByText('Update details')).toBeInTheDocument();
    expect(screen.getByText('Enter new details')).toBeInTheDocument();
  });

  test('should call onUpdateClick when the update button is clicked with valid input', async () => {
    renderApp();
    await userEvent.type(screen.getByText('Enter new details'), 'ValidInput');
    await userEvent.click(screen.getByRole('button', { name: 'Update' }));
    await waitFor(() => expect(onUpdateClick).toHaveBeenCalled());
  });

  test('should disable the update button when input is empty', async () => {
    renderApp();
    expect(screen.getByRole('button', { name: 'Update' })).toBeDisabled();
    await userEvent.type(screen.getByText('Enter new details'), 'ValidInput');
    expect(screen.getByRole('button', { name: 'Update' })).not.toBeDisabled();
  });

  test('should handle modal dismissal', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));
    expect(onModalDismiss).toHaveBeenCalled();
  });

  test('should display validation error when input is invalid', async () => {
    renderApp({
      entity: {
        id: PersonalProfileFields.CONTACT_MOBILE,
      },
    });
    await userEvent.type(screen.getByText('Enter new mobile number'), 's');
    await userEvent.click(screen.getByRole('button', { name: 'Continue' }));
    expect(screen.getByText('Invalid mobile number')).toBeInTheDocument();
  });

  test('should display checkbox when updating email', async () => {
    renderApp({
      entity: {
        id: PersonalProfileFields.EMAIL,
      },
      hasCheckbox: true,
    });
    expect(screen.getByRole('checkbox')).toBeChecked();
    await userEvent.click(screen.getByRole('checkbox'));
    expect(screen.getByRole('checkbox')).not.toBeChecked();
  });

  test('should clear text input when clear button is clicked', async () => {
    renderApp();
    await userEvent.type(screen.getByText('Enter new details'), 'ValidInput');
    await userEvent.click(screen.getByRole('button', { name: 'Clear Input Content' }));
    expect(screen.getByRole('textbox')).toHaveValue('');
  });

  test('should handle merchant country code correctly', () => {
    renderApp({
      entity: {
        id: PersonalProfileFields.CONTACT_MOBILE,
      },
      user: {
        merchant: {
          country_code: 'US',
        },
      },
    });
    const phoneInput = screen.getByRole('textbox');
    expect(phoneInput).toBeInTheDocument();
  });
});
