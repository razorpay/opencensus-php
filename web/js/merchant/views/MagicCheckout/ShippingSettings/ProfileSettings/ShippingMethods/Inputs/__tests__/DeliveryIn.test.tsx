import React from 'react';
import { render, fireEvent, screen, waitFor } from '@testing-library/react';
import DeliveryIn from '../DeliveryIn';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import { getStateWithSelectedProfile } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/utils';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';
import { storeWithInitialState } from 'merchant/store';
import { DEFAULT_PROFILE_NAME } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';

const renderComponent = (initialDisplayValue = true) => {
  const state = getStateWithSelectedProfile(DEFAULT_PROFILE_NAME);
  // Add estimated_delivery_details to state if needed
  if (state.magicCheckout?.shippingProfiles?.profiles?.[DEFAULT_PROFILE_NAME]) {
    state.magicCheckout.shippingProfiles.profiles[DEFAULT_PROFILE_NAME].estimated_delivery_details = {
      value: {
        display: initialDisplayValue,
        min_timeframe: '',
        max_timeframe: '',
        unit: 'days',
      },
      error: null,
    };
  }

  const result = render(
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={bladeTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <DeliveryIn />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );

  // Helper function to ensure the toggle is in the desired state
  const ensureToggleState = async (desiredState: boolean) => {
    const toggle = result.getByLabelText('Toggle DarkMode') as HTMLInputElement;
    const isChecked = toggle.checked;

    if (isChecked !== desiredState) {
      fireEvent.click(toggle);
      await waitFor(() => {
        const newToggle = result.getByLabelText('Toggle DarkMode') as HTMLInputElement;
        expect(newToggle.checked).toBe(desiredState);
      });
    }
  };

  return {
    ...result,
    ensureToggleState,
  };
};

describe('DeliveryIn', () => {
  test('should render display toggle switch correctly', () => {
    const { getByLabelText, getByText } = renderComponent();
    expect(getByLabelText('Toggle DarkMode')).toBeInTheDocument();
    expect(getByText('DisplayDelivery in')).toBeInTheDocument();
  });

  test('should show delivery inputs when display is enabled', async () => {
    const { getByPlaceholderText, getByText, ensureToggleState } = renderComponent(true);

    // Ensure toggle is on
    await ensureToggleState(true);

    expect(getByPlaceholderText('Min')).toBeInTheDocument();
    expect(getByPlaceholderText('Max')).toBeInTheDocument();
    expect(getByText('to')).toBeInTheDocument();
    expect(getByText('Delivery in')).toBeInTheDocument();
  });

  test('should hide delivery inputs when display is disabled', async () => {
    const { queryByPlaceholderText, queryByText, ensureToggleState } = renderComponent(false);

    // Ensure toggle is off
    await ensureToggleState(false);

    expect(queryByPlaceholderText('Min')).not.toBeInTheDocument();
    expect(queryByPlaceholderText('Max')).not.toBeInTheDocument();
    expect(queryByText('Delivery in')).not.toBeInTheDocument();
  });

  test('should update min_timeframe and max_timeframe correctly when visible', async () => {
    const { getByPlaceholderText, getByText, queryByText, ensureToggleState } = renderComponent(true);

    // Ensure toggle is on
    await ensureToggleState(true);

    const minInput = getByPlaceholderText('Min') as HTMLInputElement;
    const maxInput = getByPlaceholderText('Max') as HTMLInputElement;

    fireEvent.change(minInput, { target: { value: '2' } });
    expect(minInput.value).toBe('2');

    fireEvent.change(maxInput, { target: { value: '5' } });
    expect(maxInput.value).toBe('5');

    fireEvent.change(maxInput, { target: { value: '1' } });
    expect(getByText('Maximum timeframe must be greater than or equal to the minimum timeframe.')).toBeInTheDocument();
    fireEvent.change(maxInput, { target: { value: '6' } });
    expect(queryByText('Maximum timeframe must be greater than or equal to the minimum timeframe.')).toBeNull();
  });

  test('should show unit dropdown and allow selection', async () => {
    const { getByRole, ensureToggleState } = renderComponent(true);

    // Ensure toggle is on
    await ensureToggleState(true);

    // Find the select element (unit dropdown)
    const unitSelect = getByRole('combobox');
    expect(unitSelect).toBeInTheDocument();

    // Should default to days
    expect((unitSelect as HTMLSelectElement).value).toBe('days');

    // Change to weeks
    fireEvent.change(unitSelect, { target: { value: 'weeks' } });
    expect((unitSelect as HTMLSelectElement).value).toBe('weeks');
  });

  test('should toggle display of delivery inputs when switch is clicked', async () => {
    const { getByLabelText, queryByPlaceholderText, ensureToggleState } = renderComponent(true);

    // Ensure toggle is initially on
    await ensureToggleState(true);
    expect(queryByPlaceholderText('Min')).toBeInTheDocument();

    // Turn off
    const switchToggle = getByLabelText('Toggle DarkMode');
    fireEvent.click(switchToggle);
    expect(queryByPlaceholderText('Min')).not.toBeInTheDocument();

    // Turn back on
    fireEvent.click(switchToggle);
    expect(queryByPlaceholderText('Min')).toBeInTheDocument();
  });
});