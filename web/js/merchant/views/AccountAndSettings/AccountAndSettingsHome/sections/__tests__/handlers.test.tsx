import '@testing-library/jest-dom/extend-expect';
import {
  updateContactMobileHandler,
  updateDisplayNameHandler,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/handlers';
import { waitFor } from 'test-utils';
import { getState } from './mocks/fixtures/Profile';

const context = {
  updateUser: jest.fn(),
  closeModal: jest.fn(),
  showNotification: jest.fn(),
  updateSession: jest.fn(),
};

describe('Update From Fields Handlers', () => {
  const {
    session: { user },
  } = getState();

  describe('Update Contact Mobile Handler ', () => {
    test('should call update user and close modal on call', () => {
      const callback = updateContactMobileHandler(context);
      callback(user);
      expect(context.updateUser).toHaveBeenCalledTimes(1);
      expect(context.updateUser).toHaveBeenCalledWith(user);
      expect(context.closeModal).toHaveBeenCalledTimes(1);
    });
  });

  describe('Update Display Name Handler ', () => {
    test('should call success notification and update session on call when update config promise resolves successful', async () => {
      const response = { display_name: 'updated value' };
      const callback = updateDisplayNameHandler({
        ...context,
        updateMerchantConfig: jest.fn(() => Promise.resolve({ success: true, data: response })),
        user: {},
      });
      callback(
        {
          attribute: 'display_name',
        },
        () => {},
      );
      await waitFor(() => {
        expect(context.showNotification).toHaveBeenCalledTimes(1);
      });
      expect(context.showNotification).toHaveBeenCalledWith({
        type: 'success',
        message: 'Display name changed successfully.',
      });
      expect(context.updateSession).toHaveBeenCalledTimes(1);
      expect(context.updateSession).toHaveBeenCalledWith({
        user: expect.objectContaining({
          display_name: response.display_name,
        }),
      });
    });
  });

  test('should call error notification on call when update config promise failed', async () => {
    const callback = updateDisplayNameHandler({
      ...context,
      updateMerchantConfig: jest.fn(() => Promise.reject({ success: false })),
      user: {},
    });
    callback({}, () => {});
    await waitFor(() => {
      expect(context.showNotification).toHaveBeenCalledTimes(1);
    });
    expect(context.showNotification).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'error',
      }),
    );
  });

  test('should return null incase promise resolves with status false', () => {
    const callback = updateDisplayNameHandler({
      ...context,
      updateMerchantConfig: jest.fn(() => Promise.resolve({ success: false })),
      user: {},
    });
    const response = callback({}, () => {});
    response.then((res) => {
      expect(res).toStrictEqual({
        success: false,
      });
    });
  });
});
