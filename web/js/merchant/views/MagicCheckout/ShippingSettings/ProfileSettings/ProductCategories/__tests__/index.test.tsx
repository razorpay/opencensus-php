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
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { ShippingSettingsRouteContextProvider } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';

import ProductCategories from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ProductCategories';

const observe = jest.fn();
const unobserve = jest.fn();

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe,
  unobserve,
}));

const renderProductCategories = (newProps = {}, name: string) => {
  const state = getStateWithSelectedProfile(name);
  return render(
    <Provider store={storeWithInitialState({ ...state })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ShippingSettingsRouteContextProvider>
          <FormContextProvider>
            <ProductCategories {...newProps} />
          </FormContextProvider>
        </ShippingSettingsRouteContextProvider>
      </BladeProvider>
    </Provider>,
  );
};

describe('Product categories', () => {
  test('should show default profile view', () => {
    renderProductCategories({}, DEFAULT_PROFILE_NAME);
    const categoryText = screen.queryByText(/All products not in other profiles*/i);
    const addButton = screen.queryByRole('button', { name: '+ Add category' });
    waitFor(() => {
      expect(categoryText).toBeInTheDocument();
      expect(addButton).not.toBeInTheDocument();
    });
  });
  test('should show table view', () => {
    renderProductCategories({}, DB_CATEGORY.name);
    const table = screen.getByRole('table');
    waitFor(() => {
      expect(table).toBeInTheDocument();
    });
  });
  test('should show add category view', async () => {
    renderProductCategories({}, ADD_PROFILE);
    const addButton = screen.getByRole('button', { name: '+ Add category' });
    waitFor(() => {
      expect(addButton).toBeInTheDocument();
    });
    await userEvent.click(addButton);
    waitFor(() => {
      expect(screen.queryByText(/Create category/i)).toBeInTheDocument();
    });
  });
});
