import React from 'react';
import { render, server, userEvent, waitFor } from 'test-utils';
import { rest } from 'msw';
import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';

import AddLinkModal from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/AddLinkModal';
import { Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import {
  INTEGRATION_TITLE,
  PLATFORM_TITLE,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import { PLATFORM_LINKS } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/__test__/mocks/fixtures';
import * as analytics from 'common/utils/analytics';

const storeData = store.getState();
let showNotificationSpy, closeModalSpy, analyticsSpy, openModalSpy;

const getUpdatedUser = (user) => {
  const clonedStore = cloneDeep(storeData);
  return {
    ...clonedStore.session.user,
    ...user,
  };
};

describe('API Keys & Plugins - Add Link Modal', () => {
  beforeAll(() => {
    showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
    closeModalSpy = jest.spyOn(ModalActions, 'closeModal');
    openModalSpy = jest.spyOn(ModalActions, 'openModal');
    analyticsSpy = jest.spyOn(analytics, 'analyticsTrack');
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render modal with the correct Platform Name', () => {
    const { getByText } = render(<AddLinkModal platform={Platform.WEBSITE} />);
    const title = `Add your ${PLATFORM_TITLE[Platform.WEBSITE]} link`;
    expect(getByText(title)).toBeInTheDocument();
  });

  test('should close modal on close button click', async () => {
    const { getByText, getByTestId } = render(<AddLinkModal platform={Platform.ANDROID} />);
    const title = `Add your ${PLATFORM_TITLE[Platform.ANDROID]} link`;
    expect(getByText(title)).toBeInTheDocument();

    await userEvent.click(getByTestId('close'));
    expect(closeModalSpy).toHaveBeenCalledTimes(1);
  });

  test('should show Required error if link is not provided', async () => {
    const { getByText, getByRole } = render(<AddLinkModal platform={Platform.WEBSITE} />);
    const saveButton = getByRole('button', { name: /save/i });
    expect(saveButton).toBeInTheDocument();
    await userEvent.click(saveButton);
    await waitFor(() => {
      expect(getByText(/required/i)).toBeInTheDocument();
    });
  });

  test.each([
    [Platform.WEBSITE, PLATFORM_LINKS.FAILURE.business_website],
    [Platform.ANDROID, PLATFORM_LINKS.FAILURE.playstore_url],
    [Platform.IOS, PLATFORM_LINKS.FAILURE.appstore_url],
  ])('should show incorrect link Notification if %s link is incorrect', async (platform, link) => {
    server.use(
      rest.post('*/merchant/api/:mode/merchant/activation', (req, res, ctx) => {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        return res(ctx.errors(['Some error occurred']), ctx.delay(50));
      }),
    );

    const { getByRole } = render(<AddLinkModal product="PH" platform={platform} />);
    const input = getByRole('textbox');
    const saveButton = getByRole('button', { name: /save/i });
    await userEvent.type(input, link);
    await userEvent.click(saveButton);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledTimes(2);
      expect(analyticsSpy).toHaveBeenLastCalledWith(
        expect.objectContaining({
          screen: 'API Keys & Plugins',
          objectName: 'API Keys Save Link',
          actionName: 'Result',
          properties: expect.objectContaining({
            product: 'PH',
            paymentChannel: INTEGRATION_TITLE[platform],
            status: 'Failure',
          }),
        }),
      );
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
      expect(showNotificationSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'error',
        }),
      );
    });
  });

  test.each([
    [Platform.WEBSITE, PLATFORM_LINKS.SUCCESS.business_website],
    [Platform.ANDROID, PLATFORM_LINKS.SUCCESS.playstore_url],
    [Platform.IOS, PLATFORM_LINKS.SUCCESS.appstore_url],
  ])('should save link if %s link is correct', async (platform, link) => {
    server.use(
      rest.post('*/merchant/api/:mode/merchant/activation', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: getUpdatedUser(req.body),
          }),
          ctx.delay(50),
        );
      }),
    );

    const { getByRole } = render(<AddLinkModal product="PG" platform={platform} />);
    const input = getByRole('textbox');
    const saveButton = getByRole('button', { name: /save/i });
    await userEvent.type(input, link);
    await userEvent.click(saveButton);
    await waitFor(() => {
      expect(saveButton).toBeDisabled();
      // expect(saveButton).toBeEnabled();
    });

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalledTimes(1);
    });

    await waitFor(() => {
      // one for click and one for api result
      expect(analyticsSpy).toHaveBeenCalledTimes(2);
      expect(analyticsSpy).toHaveBeenLastCalledWith(
        expect.objectContaining({
          screen: 'API Keys & Plugins',
          objectName: 'API Keys Save Link',
          actionName: 'Result',
          properties: {
            product: 'PG',
            paymentChannel: INTEGRATION_TITLE[platform],
            status: 'Success',
          },
        }),
      );
      expect(closeModalSpy).toHaveBeenCalledTimes(1);
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Link added successfully',
      });
    });
  });
});
