import React from 'react';
import { render, screen } from 'test-utils';

import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import ShippingCard from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingCard';

const INIT_STATE = {
  magic_settings: {
    shipping_info: 'https://test.com',
    cod_slabs: [],
    one_cc_international_shipping: false,
    one_cc_capture_billing_address: false,
  },
};

const renderApp = ({ state = {}, ...props }: Record<string, any> = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <ShippingCard {...props} />
    </Provider>,
  );
};

describe('Magic Wocommerce shipping form', () => {
  test('component should render proeprly', () => {
    renderApp();
    expect(screen.getByText('Shipping Settings')).toBeInTheDocument();
  });
});
