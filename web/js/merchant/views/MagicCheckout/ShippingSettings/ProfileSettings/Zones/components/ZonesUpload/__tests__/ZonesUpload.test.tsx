import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen, waitFor, server } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { ZonesUploadProps } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/Zones/components/ZonesUpload/types';
import { INITIAL_STATE } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import ZonesUpload from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/Zones/components/ZonesUpload/ZonesUpload';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const zonesUploadProps: ZonesUploadProps = {
  closeModal: jest.fn(),
  createZoneUpload: jest.fn(),
  updateZoneUpload: jest.fn(),
  showNotification: jest.fn(),
  isOpen: true,
  itemCategoryId: 'item_category_id',
  zoneType: 'shipping',
  mode: 'edit',
  zone: {
    name: 'z1',
    type: 'shipping',
    locations: [
      {
        id: 'NbAB9wKGupVQXS',
        type: 'serviceable',
        location_type: 'country',
        zipcode: '',
        state_code: '',
        country_code: 'IN',
      },
    ],
  },
};

const renderZoneUploadModal = (newProps = {}) => {
  return render(
    <Provider store={storeWithInitialState({ ...INITIAL_STATE })}>
      <BladeProvider themeTokens={bladeTheme}>
        <ZonesUpload {...zonesUploadProps} {...newProps} />
      </BladeProvider>
    </Provider>,
  );
};

describe('Zone Upload Modal', () => {
  afterEach(() => server.resetHandlers());
  // mocking react virtualized - https://stackoverflow.com/a/62214834
  const originalOffsetHeight = Object.getOwnPropertyDescriptor(
    HTMLElement.prototype,
    'offsetHeight',
  );
  const originalOffsetWidth = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'offsetWidth');

  beforeAll(() => {
    server.listen();
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', { configurable: true, value: 50 });
    Object.defineProperty(HTMLElement.prototype, 'offsetWidth', { configurable: true, value: 50 });
  });

  afterAll(() => {
    server.close();
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', originalOffsetHeight);
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    Object.defineProperty(HTMLElement.prototype, 'offsetWidth', originalOffsetWidth);
  });

  beforeEach(() => {
    showNotificationSpy.mockClear();
  });
  test('Should render modal & Should show alert notification on edit', () => {
    renderZoneUploadModal();
    waitFor(async () => {
      expect(showNotificationSpy).toHaveBeenCalled();
      const name = await screen.findByText(/Upload Zipcodes/i);
      const uploadFileText = await screen.findByText(
        /Drop file here or click to upload (50.00 MB Max)/i,
      );
      expect(name).toBeInTheDocument();
      expect(uploadFileText).toBeInTheDocument();
    });
  });
});
