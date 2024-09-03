import { userEvent, waitFor, waitForElementToBeRemoved } from 'test-utils';
import { getUser } from 'merchant/store';
import { getKeys } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/__test__/mocks/fixtures';
import { stateWithKeys, stateWithNoKeys, renderApp } from './fixtures/GenerateKey';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import { titleCase } from 'common/utils/rzp-utils';
import { Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';

let showNotificationSpy, openModalSpy, closeModalSpy;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

jest.mock('common/ui/TwoFactorVerification/TwoFactorVerificationContext', () => ({
  useTwoFactorVerificationContext: () => ({
    criticalFlow: ({ onUserTwoFaVerified }) => {
      onUserTwoFaVerified();
    },
  }),
}));

describe('API Keys & Plugins - GenerateKey', () => {
  beforeAll(() => {
    showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
    openModalSpy = jest.spyOn(ModalActions, 'openModal');
    closeModalSpy = jest.spyOn(ModalActions, 'closeModal');
    // for copying to clipboard
    window.URL.createObjectURL = jest.fn();
    window.URL.revokeObjectURL = jest.fn();
    document.execCommand = jest.fn();
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          initiated: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });

  beforeEach(() => {
    openModalSpy.mockClear();
    closeModalSpy.mockClear();
    showNotificationSpy.mockClear();
  });

  test('should render component without errors', () => {
    expect(() => renderApp({ initialState: stateWithNoKeys })).not.toThrowError();
  });

  test('should allow generating Key if none is present', () => {
    const { getByRole, queryByRole } = renderApp({ initialState: stateWithNoKeys });
    expect(getByRole('button', { name: /generate key/i })).toBeEnabled();
    expect(queryByRole('button', { name: /generate new key/i })).not.toBeInTheDocument();
    expect(queryByRole('button', { name: /download keys/i })).not.toBeInTheDocument();
  });

  test('should show Generate New Key and hide secret if key is generated', () => {
    const { getByRole, queryByRole, queryByTestId } = renderApp({ initialState: stateWithKeys });
    expect(getByRole('button', { name: /generate new key/i })).toBeEnabled();
    expect(queryByRole('button', { name: /generate key/i })).not.toBeInTheDocument();
    expect(queryByRole('button', { name: /download keys/i })).not.toBeInTheDocument();
    expect(queryByTestId('key-secret')).not.toBeInTheDocument();
  });

  test('should generate and download API Key successfully', async () => {
    console.error = jest.fn();
    // find and click generate button
    const { getByText, getByRole, queryByTestId, getByTestId } = renderApp({
      initialState: stateWithNoKeys,
    });
    const generateButton = getByText(/generate key/i);
    expect(generateButton).toBeEnabled();
    await userEvent.click(generateButton);

    // wait for key and id to generate
    await waitForElementToBeRemoved(generateButton);
    expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    expect(showNotificationSpy).toHaveBeenCalledWith(expect.objectContaining({ type: 'success' }));
    showNotificationSpy.mockClear();

    // copy items and wait
    await userEvent.click(getByTestId('copy-id'));
    expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    expect(showNotificationSpy).toHaveBeenCalledWith(expect.objectContaining({ type: 'success' }));
    showNotificationSpy.mockClear();

    const copySecret = getByTestId('copy-secret');
    await userEvent.click(copySecret);
    await userEvent.click(copySecret);
    expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    expect(showNotificationSpy).toHaveBeenCalledWith(expect.objectContaining({ type: 'success' }));
    expect(queryByTestId('copied-secret')).toBeInTheDocument();
    showNotificationSpy.mockClear();
    await waitFor(
      () => {
        expect(queryByTestId('copied-secret')).not.toBeInTheDocument();
      },
      { timeout: 2000 },
    );

    // find and click download button and wait for file to download
    const downloadButton = getByRole('button', { name: /download keys/i });
    expect(downloadButton).toBeEnabled();
    await userEvent.click(downloadButton);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
      expect(showNotificationSpy).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'success' }),
      );
    });
  });

  test('should show Roll Key modal if 2FA is verified', async () => {
    const { getByText } = renderApp({ initialState: stateWithKeys });
    const regenerateButton = getByText(/generate new key/i);
    await userEvent.click(regenerateButton);
    expect(openModalSpy).toHaveBeenCalledTimes(1);
  });

  test('should show under review status if Merchant does not have key access and is in live mode', () => {
    const { getByText } = renderApp({
      initialState: {
        ...stateWithNoKeys,
        session: {
          modeFormatted: 'Live',
          user: {
            ...getUser(),
            has_key_access: false,
            business_website: 'https://google.com',
          },
        },
      },
      selectedPlatform: Platform.WEBSITE,
    });
    expect(getByText(/You can generate API keys in Test Mode/i)).toBeInTheDocument();
  });

  test('should show Generate Key if Merchant does not have key access and is in test mode', () => {
    const { getByRole } = renderApp({
      initialState: {
        ...stateWithNoKeys,
        session: {
          modeFormatted: 'Test',
          user: {
            ...getUser(),
            has_key_access: false,
            business_website: 'https://google.com',
          },
        },
      },
      selectedPlatform: Platform.WEBSITE,
    });
    expect(getByRole('button', { name: /Generate Key/i })).toBeInTheDocument();
  });

  test.each(['live', 'test'])('should have %s id if mode is %s', (mode) => {
    const initialState = {
      keys: {
        ...stateWithNoKeys.keys,
        keys: getKeys(mode as 'live' | 'test'),
      },
      session: {
        modeFormatted: titleCase(mode),
        user: getUser(),
      },
    };

    const { getByTestId } = renderApp({ initialState });
    expect(getByTestId('key-id').textContent).toMatch(mode);
  });
});
