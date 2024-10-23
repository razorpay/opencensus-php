import { server, userEvent, waitFor, waitForElementToBeRemoved } from 'test-utils';
import { rest } from 'msw';
import { stateWithKeys, stateWithNoKeys, renderApp } from './fixtures/GenerateKey';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import { useSplitzService } from 'common/splitz';

let showNotificationSpy, openModalSpy;

jest.mock('common/splitz', () => ({
  useSplitzService: jest.fn(() => ({
    abExperiments: {},
  })),
}));

jest.mock('common/ui/TwoFactorVerification/TwoFactorVerificationContext', () => ({
  useTwoFactorVerificationContext: () => ({
    criticalFlow: ({ onFlowTermination }) => {
      onFlowTermination();
    },
  }),
}));

describe('API Keys & Plugins - GenerateKey', () => {
  beforeAll(() => {
    showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
    openModalSpy = jest.spyOn(ModalActions, 'openModal');
  });
  beforeEach(() => {
    jest.clearAllMocks();
  });

  beforeEach(() => {
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    useSplitzService.mockReturnValue({
      abExperiments: {},
    });
  });

  test('should not show Roll key modal is 2FA is terminated', async () => {
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    useSplitzService.mockReturnValue({
      abExperiments: {
        enable_2fa_for_protected_flows: {
          experimentId: 'enable_2fa_for_protected_flows',
          variables: { result: 'on' },
        },
      },
    });
    const { getByText } = renderApp({ initialState: stateWithKeys });

    const regenerateButton = getByText(/generate new key/i);
    await userEvent.click(regenerateButton);
    expect(openModalSpy).toHaveBeenCalledTimes(0);
  });

  test('should show error on Generate Key failure', async () => {
    server.use(
      rest.post('*/merchant/api/:mode/keys/', (req, res, ctx) => {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        return res(ctx.errors(['Some error occurred']), ctx.delay(50));
      }),
    );
    const { getByText } = renderApp({ initialState: stateWithNoKeys });
    const generateButton = getByText(/generate key/i);
    await userEvent.click(generateButton);

    expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    expect(showNotificationSpy).toHaveBeenCalledWith(expect.objectContaining({ type: 'error' }));
  });

  test('should show error if file could not download', async () => {
    console.error = jest.fn();
    server.use(
      rest.post('*/keys/csv', (req, res, ctx) => {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        return res(ctx.errors(['Some error occurred']), ctx.delay(50));
      }),
    );

    // find and click generate button
    const { getByText, getByRole } = renderApp({ initialState: stateWithNoKeys });
    const generateButton = getByText(/generate key/i);
    expect(generateButton).toBeEnabled();
    await userEvent.click(generateButton);
    await waitForElementToBeRemoved(generateButton);
    showNotificationSpy.mockClear();

    // find and click download button and wait for file to download
    const downloadButton = getByRole('button', { name: /download keys/i });
    expect(downloadButton).toBeEnabled();
    await userEvent.click(downloadButton);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
      expect(showNotificationSpy).toHaveBeenCalledWith(expect.objectContaining({ type: 'error' }));
    });
  });
});
