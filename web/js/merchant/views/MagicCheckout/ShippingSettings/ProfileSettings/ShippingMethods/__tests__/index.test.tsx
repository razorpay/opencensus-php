import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import {
  DB_METHOD,
  DB_ZONE,
  DEFAULT_PROFILE_NAME,
} from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import { getStateWithSelectedProfile } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/utils';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import ShippingMethods from '..';
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

jest.setTimeout(30000);

const renderShippingMethods = (newProps = {}, name: string) => {
  const state = getStateWithSelectedProfile(name);
  return render(
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={bladeTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <ShippingMethods {...newProps} />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );
};

describe('Shipping methods', () => {
  test('should show table view', () => {
    renderShippingMethods({}, DEFAULT_PROFILE_NAME);
    const table = screen.getByRole('table');
    const tableHeaderItems = screen.getAllByRole('rowheader');
    const tableItems = screen.getAllByRole('row');
    const methodName = screen.queryByText(DB_METHOD.description);
    const addMore = screen.getAllByRole('button', { name: 'Add shipping method' })[0];
    expect(table).toBeInTheDocument();
    expect(methodName).toBeInTheDocument();
    expect(addMore).toBeInTheDocument();
    expect(tableHeaderItems).toHaveLength(1);
    expect(tableItems).toHaveLength(1);
  });
  test('show show add more view', async () => {
    renderShippingMethods({}, DEFAULT_PROFILE_NAME);
    const table = screen.getByRole('table');
    const addMore = screen.getAllByRole('button', { name: 'Add shipping method' })[0];

    expect(table).toBeInTheDocument();
    expect(addMore).toBeInTheDocument();
    await userEvent.click(addMore);
    waitFor(() => {
      const addMoreText = screen.queryByText(`Shipping Methods - ${DB_ZONE.name}`);
      const addMoreText2 = screen.queryByText(`Shipping Methods - Uploaded file 1`);
      expect(addMoreText).toBeInTheDocument();
      expect(addMoreText2).toBeInTheDocument();
    });
  });

  test('should show edit view', async () => {
    renderShippingMethods({}, DEFAULT_PROFILE_NAME);
    const table = screen.getByRole('table');
    const addMore = screen.getByRole('button', { name: 'edit' });

    expect(table).toBeInTheDocument();
    expect(addMore).toBeInTheDocument();
    await userEvent.click(addMore);
    const addMoreText = screen.queryByText(`Shipping Methods - ${DB_ZONE.name}`);
    expect(addMoreText).toBeInTheDocument();

    //commenting as we are not supporting tags

    // const methodTag = screen.queryByText(/loyal_customers/i);
    // expect(methodTag).toBeInTheDocument();

    const confirmButton = screen.getByTestId('confirm-button');
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});
