import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import { INITIAL_STATE } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';

import PreviewView from '..';

const renderPreviewView = (newProps = {}) => {
  return render(
    <Provider store={storeWithInitialState({ ...INITIAL_STATE })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <PreviewView {...newProps} />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );
};

describe('Preview View', () => {
  test('Should render view', () => {
    renderPreviewView();
    const defaultProfiles = screen.queryByText(/Default Shipping Profile/i);
    const generalProfiles = screen.queryByText(/General Shipping Profile/i);
    waitFor(() => {
      expect(defaultProfiles).toBeInTheDocument();
      expect(generalProfiles).toBeInTheDocument();
    });
  });
});
