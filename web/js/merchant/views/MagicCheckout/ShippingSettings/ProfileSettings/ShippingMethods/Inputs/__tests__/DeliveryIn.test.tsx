import React from 'react';
import { render, fireEvent } from '@testing-library/react';
import DeliveryIn from '../DeliveryIn';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import { getStateWithSelectedProfile } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/utils';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';
import { storeWithInitialState } from 'merchant/store';
import { DEFAULT_PROFILE_NAME } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';

const renderComponent = () => {
  const state = getStateWithSelectedProfile(DEFAULT_PROFILE_NAME);
  return render(
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
};

describe('DeliveryIn', () => {
  test('should render without crashing', () => {
    const { getByPlaceholderText, getByText } = renderComponent();
    expect(getByPlaceholderText('Min')).toBeInTheDocument();
    expect(getByPlaceholderText('Max')).toBeInTheDocument();
    expect(getByText('to')).toBeInTheDocument();
  });

  test('should update min_timeframe and max_timeframe correctly', () => {
    const { getByPlaceholderText, getByText, queryByText } = renderComponent();

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
});
