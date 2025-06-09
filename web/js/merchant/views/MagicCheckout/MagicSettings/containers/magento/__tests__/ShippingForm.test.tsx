import React from 'react';
import { render, screen } from 'test-utils';

import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import ShippingForm from 'merchant/views/MagicCheckout/MagicSettings/containers/magento/ShippingForm';

const INIT_STATE = {
  magic_settings: {
    status: 'idle',
    shipping_info: 'https://test.com',
    cod_slabs: [],
    platform: 'magento',
    codSlabsSet: false,
    nestedTabsStatus: 'idle',
    one_cc_international_shipping: false,
    one_cc_capture_billing_address: false,
  },
};

const renderApp = ({ state = {}, ...props }: Record<string, any> = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <ShippingForm {...props} />
    </Provider>,
  );
};

describe('testing shipping form component', () => {
  test('component should render properly', () => {
    renderApp();
    expect(screen.getByText('Shipping Settings')).toBeInTheDocument();
  });
});
