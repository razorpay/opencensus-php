import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { FormContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import ShippingSlab from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/Inputs/ShippingSlab';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';

import { getStateWithSelectedProfile } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/utils';
import { DEFAULT_PROFILE_NAME } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';

const renderShippingSlab = (newProps = {}, name: string) => {
  const state = getStateWithSelectedProfile(name);
  return render(
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <ShippingSlab {...newProps} />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );
};

describe('Shipping slab', () => {
  test('should show default  view', () => {
    renderShippingSlab({}, DEFAULT_PROFILE_NAME);
    const label = screen.queryByText(/Shipping Slab/i);
    const slabOptionsSelect = screen.getAllByRole('combobox')[0];
    const addCondition = screen.getByRole('button', { name: '+ Add condition' });
    const weightText = screen.queryByText(/kg/i);
    waitFor(() => {
      expect(label).toBeInTheDocument();
      expect(slabOptionsSelect).toBeInTheDocument();
      expect(addCondition).toBeInTheDocument();
      expect(weightText).not.toBeInTheDocument();
    });
  });

  test('should be to add weight condition', async () => {
    renderShippingSlab({}, DEFAULT_PROFILE_NAME);
    const label = screen.queryByText(/Shipping Slab/i);
    const weightText = screen.queryByText(/kg/i);
    const addCondition = screen.getByRole('button', { name: '+ Add condition' });
    waitFor(() => {
      expect(label).toBeInTheDocument();
      expect(weightText).not.toBeInTheDocument();
      expect(addCondition).toBeInTheDocument();
    });
    await userEvent.click(addCondition);
    waitFor(() => {
      expect(weightText).toBeInTheDocument();
      expect(addCondition).not.toBeInTheDocument();
    });
  });
  test('should be able swap conditions', async () => {
    renderShippingSlab({}, DEFAULT_PROFILE_NAME);
    const label = screen.queryByText(/Shipping Slab/i);
    const addCondition = screen.getByRole('button', { name: '+ Add condition' });

    expect(label).toBeInTheDocument();
    expect(screen.queryAllByText(/kg/i).length).toBe(0);
    expect(addCondition).toBeInTheDocument();

    await userEvent.click(addCondition);
    expect(screen.queryAllByText(/kg/i).length).toBe(2);

    const slabOptionsSelect = screen.getAllByRole('combobox')[0];
    await userEvent.selectOptions(slabOptionsSelect, 'weight');
  });

  test('should be able to delete weight condition', async () => {
    renderShippingSlab({}, DEFAULT_PROFILE_NAME);
    const label = screen.queryByText(/Shipping Slab/i);
    const addCondition = screen.getByRole('button', { name: '+ Add condition' });

    expect(label).toBeInTheDocument();
    expect(screen.queryByText(/kg/i)).not.toBeInTheDocument();
    expect(addCondition).toBeInTheDocument();

    await userEvent.click(addCondition);

    expect(screen.queryAllByText(/kg/i).length).toBe(2);
    expect(addCondition).not.toBeInTheDocument();

    waitFor(async () => {
      const deleteBtn = screen.getByRole('button', { name: 'delete' });
      await userEvent.click(deleteBtn);
      expect(screen.queryAllByText(/kg/i).length).toBe(0);
    });
  });

  test('should show invalid value', () => {
    renderShippingSlab({}, DEFAULT_PROFILE_NAME);
    const maxInput = screen.getAllByRole('spinbutton')[1];
    userEvent.type(maxInput, '-0');
    waitFor(() => {
      const errorText = screen.queryByText(/Invalid input/i);
      expect(errorText).toBeInTheDocument();
    });
  });
});
