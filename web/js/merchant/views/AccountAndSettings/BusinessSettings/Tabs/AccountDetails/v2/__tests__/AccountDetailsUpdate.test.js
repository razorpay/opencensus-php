import { screen, userEvent } from 'test-utils';
import {
  renderApp,
  mockEntity,
  onContactUpdateSubmit,
  onEnterEmailSubmit,
} from './mocks/fixtures/AccountDetailsUpdate';
import {
  PersonalProfileFields,
  HANDLERS,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

describe('AccountDetailsUpdate', () => {
  test('should render the AccountDetailsUpdate form with a Display Name field', () => {
    renderApp();
    expect(screen.getByText('display_name modal form')).toBeInTheDocument();
  });

  test('should call the appropriate update function when the "Update" button is clicked for Display Name', async () => {
    renderApp();
    const updateButton = screen.getByRole('button', { name: 'Update' });
    await userEvent.click(updateButton);
    expect(mockEntity.updateMerchantConfig).toHaveBeenCalledWith({
      display_name: 'userInput',
      setIsLoading: expect.any(Function),
    });
  });

  test('should call the appropriate update function when the "Update" button is clicked for Name', async () => {
    renderApp({ entity: { ...mockEntity, id: PersonalProfileFields.NAME } });
    const updateButton = screen.getByRole('button', { name: 'Update' });
    await userEvent.click(updateButton);
    expect(mockEntity.updateMerchantConfig).toHaveBeenCalledWith({
      name: 'userInput',
      setIsLoading: expect.any(Function),
    });
  });

  test('should call the appropriate update function when the "Update" button is clicked for Contact Mobile', async () => {
    renderApp({ entity: { ...mockEntity, id: PersonalProfileFields.CONTACT_MOBILE } });
    const updateButton = screen.getByRole('button', { name: 'Update' });
    await userEvent.click(updateButton);
    expect(onContactUpdateSubmit).toHaveBeenCalledWith({
      contactMobile: 'userInput',
      setIsLoading: expect.any(Function),
    });
  });

  test('should call the appropriate update function when the "Update" button is clicked for Email with HandlerType UPDATE', async () => {
    renderApp({ entity: { ...mockEntity, id: PersonalProfileFields.EMAIL } });
    const updateButton = screen.getByRole('button', { name: 'Update' });
    await userEvent.click(updateButton);
    expect(mockEntity.onEmailUpdate).toHaveBeenCalledWith({
      email: 'userInput',
      setContactEmail: true,
      setIsLoading: expect.any(Function),
    });
  });

  test('should call the appropriate update function when the "Update" button is clicked for Email with HandlerType ADD', async () => {
    renderApp({
      entity: { ...mockEntity, id: PersonalProfileFields.EMAIL, handlerType: HANDLERS.ADD },
    });
    const updateButton = screen.getByRole('button', { name: 'Update' });
    await userEvent.click(updateButton);
    expect(onEnterEmailSubmit).toHaveBeenCalledWith({
      userInput: 'userInput',
      setIsLoading: expect.any(Function),
    });
  });

  test('should not call any update function when the "Update" button is clicked for invalid entity', async () => {
    renderApp({
      entity: { ...mockEntity, id: 'idk' },
    });
    const updateButton = screen.getByRole('button', { name: 'Update' });
    await userEvent.click(updateButton);
    expect(onEnterEmailSubmit).not.toHaveBeenCalledWith();
  });
});
