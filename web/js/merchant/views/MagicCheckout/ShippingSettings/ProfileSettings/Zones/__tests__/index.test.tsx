import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import {
  DB_CATEGORY,
  DEFAULT_PROFILE_NAME,
} from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import { getStateWithSelectedProfile } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/utils';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';

import ShippingZones from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/Zones';

const renderShippingZones = (newProps = {}, name: string) => {
  const state = getStateWithSelectedProfile(name);
  return render(
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <ShippingZones {...newProps} />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );
};

describe('Shipping zones', () => {
  test('should show table view', () => {
    renderShippingZones({}, DEFAULT_PROFILE_NAME);
    const table = screen.getByRole('table');
    const tableItems = screen.getAllByRole('row');
    const zoneName = screen.queryByText(/North/i);
    const addMore = screen.getByTestId('magic-add-more-button');
    expect(table).toBeInTheDocument();
    expect(zoneName).toBeInTheDocument();
    expect(addMore).toBeInTheDocument();
    expect(tableItems).toHaveLength(2);
  });
  test('should show edit view', async () => {
    renderShippingZones({}, DEFAULT_PROFILE_NAME);
    const table = screen.getByRole('table');
    const editButton = screen.getByRole('button', { name: 'edit' });

    expect(table).toBeInTheDocument();
    expect(editButton).toBeInTheDocument();
    await userEvent.click(editButton);
    waitFor(() => {
      const editText = screen.queryByText(/Edit shipping zones/i);
      expect(editText).toBeInTheDocument();
    });
  });
  test('should show add more view', async () => {
    renderShippingZones({}, DEFAULT_PROFILE_NAME);
    const table = screen.getByRole('table');
    const addMore = screen.getByTestId('magic-add-more-button');

    expect(table).toBeInTheDocument();
    expect(addMore).toBeInTheDocument();
    await userEvent.click(addMore);
    waitFor(() => {
      const addMoreText = screen.queryByText(/Create shipping zones/i);
      expect(addMoreText).toBeInTheDocument();
    });
  });
  test('should show create view', async () => {
    renderShippingZones({}, DB_CATEGORY.name);
    const table = screen.queryByRole('table');
    const addButton = screen.getByRole('button', { name: '+ Add zones' });

    expect(table).not.toBeInTheDocument();
    expect(addButton).toBeInTheDocument();
    await userEvent.click(addButton);
    waitFor(() => {
      const editText = screen.queryByText(/Create shipping zones/i);
      expect(editText).toBeInTheDocument();
    });
  });
});
