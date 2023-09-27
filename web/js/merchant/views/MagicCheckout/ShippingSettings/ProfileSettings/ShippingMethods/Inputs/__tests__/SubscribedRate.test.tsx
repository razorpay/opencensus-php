import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import SubscribedRate from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/Inputs/SubscribedRate';
import { DEFAULT_PROFILE_NAME } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import { getStateWithSelectedProfile } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/utils';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';

const renderSubscribedRate = (newProps = {}, name: string) => {
  const state = getStateWithSelectedProfile(name);
  return render(
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <SubscribedRate {...newProps} />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );
};

describe('Subscribed Rate', () => {
  test('should show default view', () => {
    renderSubscribedRate({}, DEFAULT_PROFILE_NAME);
    const label = screen.queryByText(/Subscribed Rate/i);
    const rateToggle = screen.getByRole('button');

    waitFor(() => {
      expect(label).toBeInTheDocument();
      expect(rateToggle).toBeInTheDocument();
    });
  });
  test('should show enable rates view', () => {
    renderSubscribedRate({}, DEFAULT_PROFILE_NAME);
    const name = screen.queryByText(/Name/i);
    const rate = screen.queryByText(/Rate/i);
    const rateToggle = screen.getByRole('button');
    expect(name).not.toBeInTheDocument();
    userEvent.click(rateToggle);
    waitFor(() => {
      const addButton = screen.getByRole('button', { name: '+ Add more' });
      expect(name).toBeInTheDocument();
      expect(rate).toBeInTheDocument();
      expect(addButton).toBeInTheDocument();
    });
  });

  test('should show add rates view', () => {
    renderSubscribedRate({}, DEFAULT_PROFILE_NAME);
    const rateToggle = screen.getByRole('button');
    userEvent.click(rateToggle);
    waitFor(async () => {
      const inputs = screen.getAllByRole('spinbutton');
      expect(inputs).toHaveLength(2);
      const addButton = screen.getByRole('button', { name: '+ Add more' });
      expect(addButton).toBeInTheDocument();
      await userEvent.click(addButton);
      expect(inputs).toHaveLength(4);
    });
  });
});
