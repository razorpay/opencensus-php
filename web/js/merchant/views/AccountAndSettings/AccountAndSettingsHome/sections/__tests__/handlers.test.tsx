import '@testing-library/jest-dom/extend-expect';
import {
  onEmailAdd,
  onEmailUpdate,
  updateContactMobileHandler,
  updateDisplayNameHandler,
  updateUserNameHandler,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/handlers';
import { waitFor } from 'test-utils';
import { getState } from './mocks/fixtures/Profile';
import * as analytics from 'common/utils/analytics';

const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');

const mockPromise = () => ({
  then: (cb) => {
    cb({
      success: true,
      data: {
        name: 'test',
      },
    });
    return {
      catch: (cb) => {
        cb({ errors: [] });
        return {
          finally: (cb) => {
            cb();
          },
        };
      },
    };
  },
});

const context = {
  user: {},
  openModal: jest.fn(),
  updateUser: jest.fn(),
  closeModal: jest.fn(),
  showNotification: jest.fn(),
  updateSession: jest.fn(),
  updateUserName: jest.fn(mockPromise),
  getEmailStatus: jest.fn(mockPromise),
};
const onSuccessCallback = jest.fn();
const setIsLoading = jest.fn();

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
          setIsLoading,
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
    callback(
      {
        setIsLoading,
      },
      () => {},
    );
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
    const response = callback(
      {
        setIsLoading,
      },
      () => {},
    );
    response.then((res) => {
      expect(res).toStrictEqual({
        success: false,
      });
    });
  });

  describe('updateUserNameHandler', () => {
    test('should call success callback when promise resolves successfully', async () => {
      const callback = updateUserNameHandler({
        ...context,
      });
      callback(
        {
          name: 'test',
          setIsLoading,
        },
        onSuccessCallback,
      );
      await waitFor(() => {
        expect(onSuccessCallback).toHaveBeenCalledTimes(1);
      });
      expect(setIsLoading).toHaveBeenCalledWith(false);
    });
  });

  describe('onEmailAdd', () => {
    test('should call success callback when promise resolves successfully', async () => {
      const callback = onEmailAdd({
        ...context,
      });
      callback(
        {
          email: 'test@razorpay.com',
          otpAuthToken: 'otpAuthToken',
          setIsLoading,
        },
        onSuccessCallback,
      );
      await waitFor(() => {
        expect(onSuccessCallback).toHaveBeenCalledTimes(1);
      });
      expect(setIsLoading).toHaveBeenCalledWith(false);
    });

    test('should show failure notification when promise fails to resolve', async () => {
      const callback = onEmailAdd({
        ...context,
      });
      callback(
        {
          email: 'no-error-message@razorpay.com',
          otpAuthToken: 'otpAuthToken',
          setIsLoading,
        },
        onSuccessCallback,
      );
      await waitFor(() => {
        expect(analyticsTrackSpy).toHaveBeenCalledWith({
          objectName: 'add email',
          actionName: 'result',
          screen: 'my account',
          properties: {
            result: 'Failure',
            failureMessage: null,
          },
        });
      });
      expect(context.showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'Some error occured. Please refresh',
      });
      expect(setIsLoading).toHaveBeenCalledWith(false);
      callback(
        {
          email: 'incorrect-email@razorpay.com',
          otpAuthToken: 'otpAuthToken',
          setIsLoading,
        },
        onSuccessCallback,
      );
      await waitFor(() => {
        expect(analyticsTrackSpy).toHaveBeenCalledWith({
          objectName: 'add email',
          actionName: 'result',
          screen: 'my account',
          properties: {
            result: 'Failure',
            failureMessage: 'incorrect-email',
          },
        });
      });
    });

    test('should show failure notification when email is not valid', async () => {
      const callback = onEmailAdd({
        ...context,
      });
      callback(
        {
          email: 'invalid',
          otpAuthToken: 'otpAuthToken',
          setIsLoading,
        },
        jest.fn(),
      );
      await waitFor(() => {
        expect(context.showNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Invalid email',
        });
      });
    });
  });

  describe('onEmailUpdate', () => {
    test('should call success callback when promise resolves successfully', async () => {
      const callback = onEmailUpdate({
        ...context,
      });
      callback(
        {
          email: 'test@razorpay.com',
          setContactEmail: true,
          setIsLoading,
        },
        onSuccessCallback,
      );
      await waitFor(() => {
        expect(onSuccessCallback).toHaveBeenCalledTimes(1);
      });
      expect(setIsLoading).toHaveBeenCalledWith(false);
    });
  });
});
