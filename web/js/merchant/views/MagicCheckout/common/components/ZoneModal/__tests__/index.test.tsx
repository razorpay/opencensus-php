import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';
import { act } from 'react-dom/test-utils';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import {
  DB_CATEGORY,
  DB_ZONE,
  INITIAL_STATE,
} from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import ZoneModal from '..';

const createZone = jest.fn();
const updateZone = jest.fn();

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const renderZoneModal = (newProps = {}) => {
  return render(
    <Provider store={storeWithInitialState({ ...INITIAL_STATE })}>
      <BladeProvider themeTokens={bladeTheme}>
        <ZoneModal
          isOpen={true}
          closeModal={jest.fn()}
          createZone={createZone}
          updateZone={updateZone}
          mode="create"
          loading={false}
          zoneType="shipping"
          isZoneFetching={false}
          countriesUrl="1cc/shipping/countries"
          {...newProps}
        />
      </BladeProvider>
    </Provider>,
  );
};

describe('Zone Modal', () => {
  // mocking react virtualized - https://stackoverflow.com/a/62214834
  const originalOffsetHeight = Object.getOwnPropertyDescriptor(
    HTMLElement.prototype,
    'offsetHeight',
  );
  const originalOffsetWidth = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'offsetWidth');

  beforeAll(() => {
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', { configurable: true, value: 50 });
    Object.defineProperty(HTMLElement.prototype, 'offsetWidth', { configurable: true, value: 50 });
  });

  afterAll(() => {
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
  test('Should render modal', () => {
    renderZoneModal();
    waitFor(async () => {
      const name = await screen.findByText(/Zone name/i);
      const select = await screen.findByText('Select country, city, state');
      expect(name).toBeInTheDocument();
      expect(select).toBeInTheDocument();
      expect(await screen.findAllByTestId('zone-item')).toHaveLength(5);
    });
  });

  test('should check item in zone', async () => {
    renderZoneModal();
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const checkbox = screen.getByRole('checkbox', { name: 'India' });
    expect(checkbox).not.toBeChecked();
    await userEvent.click(checkbox);
    expect(checkbox).toBeChecked();
  });

  test('should collapse states', async () => {
    renderZoneModal();
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const statesToggle = screen.getByText('0 of 2 states');
    expect(screen.queryByRole('checkbox', { name: 'Andhra Pradesh' })).not.toBeInTheDocument();
    await userEvent.click(statesToggle);
    expect(screen.queryByRole('checkbox', { name: 'Andhra Pradesh' })).toBeInTheDocument();
  });

  test('should filter countries based on search', async () => {
    renderZoneModal();
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const searchInput = screen.getByRole('textbox', { name: 'Select country, city, state' });
    await act(async () => {
      await userEvent.type(searchInput, 'Andhra');
    });

    await waitFor(
      () => {
        expect(screen.queryByRole('checkbox', { name: 'Andhra Pradesh' })).toBeInTheDocument();
        expect(screen.queryByRole('checkbox', { name: 'Afghanistan' })).not.toBeInTheDocument();
      },
      { timeout: 2000 },
    );
    await act(async () => {
      await userEvent.clear(searchInput);
    });
    await waitFor(
      () => {
        expect(screen.queryByRole('checkbox', { name: 'Afghanistan' })).toBeInTheDocument();
      },
      { timeout: 500 },
    );
  });

  test('should mark parent as indeterminate', async () => {
    renderZoneModal();
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const searchInput = screen.getByRole('textbox', { name: 'Select country, city, state' });
    await userEvent.type(searchInput, 'India');

    const checkbox = await screen.findByRole('checkbox', { name: 'Andhra Pradesh' });

    await userEvent.click(checkbox);

    await waitFor(() => {
      expect(screen.queryByRole('checkbox', { name: 'India' })).toBePartiallyChecked();
    });
  });

  test('should save zone', async () => {
    renderZoneModal({
      zone: DB_ZONE,
      item_category_id: DB_CATEGORY.id,
      mode: 'edit',
    });
    const ZoneModal = await screen.findByTestId('zone-modal');
    expect(ZoneModal).toBeInTheDocument();
    const nameInput = screen.getByRole('textbox', { name: 'Zone name' });
    await waitFor(async () => {
      await userEvent.clear(nameInput);
      expect(nameInput).toHaveValue('');
    });
    await userEvent.type(nameInput, 'New Zone');
    await waitFor(async () => {
      expect(nameInput).toHaveValue('New Zone');
      const saveBtn = screen.getByTestId('confirm-button');
      await userEvent.click(saveBtn);
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});
