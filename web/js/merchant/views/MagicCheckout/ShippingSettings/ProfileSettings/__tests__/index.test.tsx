import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import cloneDeep from 'lodash/cloneDeep';
import { Provider } from 'react-redux';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import {
  DB_CATEGORY,
  DEFAULT_PROFILE_NAME,
  INITIAL_STATE,
} from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';

import ProfileSettings from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings';

const getStateWithSelectedProfile = (name) => {
  const newState = cloneDeep(INITIAL_STATE);
  newState.magicShippingEngine.selected_profile = name;
  return newState;
};

const renderProfileSettings = (newProps = {}, name: string) => {
  const state = getStateWithSelectedProfile(name);
  return render(
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <ProfileSettings {...newProps} />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );
};

describe('Profile Settings View', () => {
  test('default profile view', async () => {
    renderProfileSettings({}, DEFAULT_PROFILE_NAME);
    const defaultProfiles = screen.queryByText(/Default shipping profiles/i);
    const categoryText = screen.queryByText(/All products not in other profiles*/i);
    const zoneText = screen.queryByText(/Shipping zone*/i);
    const methodText = screen.queryByText(/Shipping method & rate*/i);
    const goBack = screen.getByRole('button', { name: 'Go back' });
    waitFor(() => {
      expect(defaultProfiles).toBeInTheDocument();
      expect(categoryText).toBeInTheDocument();
      expect(zoneText).toBeInTheDocument();
      expect(methodText).toBeInTheDocument();
      expect(goBack).toBeInTheDocument();
    });
    await userEvent.click(goBack);
    waitFor(() => {
      expect(screen.queryByText(/Go back*/i)).not.toBeInTheDocument();
    });
  });
  test('general profile view', () => {
    renderProfileSettings({}, DB_CATEGORY.name);
    const defaultProfiles = screen.queryByText(/Default shipping profiles/i);
    const generalProfiles = screen.queryByText(/Custom shipping profiles*/i);
    const goBack = screen.getByRole('button', { name: 'Go back' });
    waitFor(() => {
      expect(defaultProfiles).not.toBeInTheDocument();
      expect(generalProfiles).toBeInTheDocument();
      expect(goBack).toBeInTheDocument();
    });
  });

  test('add profile view', () => {
    renderProfileSettings({}, ADD_PROFILE);
    const defaultProfiles = screen.queryByText(/Default shipping profiles/i);
    const generalProfiles = screen.queryByText(/Custom shipping profiles*/i);
    const addButton = screen.getByRole('button', { name: '+ Add category' });
    const goBack = screen.getByRole('button', { name: 'Go back' });
    waitFor(() => {
      expect(defaultProfiles).not.toBeInTheDocument();
      expect(generalProfiles).toBeInTheDocument();
      expect(goBack).toBeInTheDocument();
      expect(addButton).toBeInTheDocument();
    });
  });
});
