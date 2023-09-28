import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import {
  DB_PRODUCTS,
  INITIAL_STATE,
} from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';
import ProductItem from 'merchant/views/MagicCheckout/common/components/ProductsModal/Item';

const renderProductItem = (newProps) => {
  return render(
    <Provider store={storeWithInitialState({ ...INITIAL_STATE })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ProductItem item={{ ...DB_PRODUCTS[0], selected: false }} {...newProps} />
      </BladeProvider>
    </Provider>,
  );
};

describe('Product Item', () => {
  test('Should render item', () => {
    const handleSelectedProduct = jest.fn();
    renderProductItem({ handleSelectedProduct, isDisabled: false });
    const name = screen.queryByText(/Product Variant - T Shirt/i);
    const checkbox = screen.getByRole('checkbox');
    expect(name).toBeInTheDocument();
    expect(checkbox).toBeInTheDocument();

    userEvent.click(checkbox);
    waitFor(() => {
      expect(handleSelectedProduct).toHaveBeenCalled();
    });
  });
});
