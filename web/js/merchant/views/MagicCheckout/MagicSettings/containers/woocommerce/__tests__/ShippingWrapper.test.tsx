import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import ShippingSettingsWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingSettingsWrapper';

import { WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE } from 'merchant/views/MagicCheckout/MagicSettings/constants';

jest.mock('merchant/views/MagicCheckout/Settings/containers/ShippingSettingsTab', () => () => {
  return <div>Magic shipping engine</div>;
});

jest.mock(
  'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper',
  () => () => {
    return <div>Woocommerce shipping settings</div>;
  },
);

const abExperiments = {
  magic_shopify_shipping_engine: {
    variables: {
      result: 'on',
    },
  },
};

const renderApp = ({ ...props }: Record<string, any> = {}) => {
  render(<ShippingSettingsWrapper abExperiments={abExperiments} {...props} />);
};

describe('testing wooc shipping settings wrapper', () => {
  test('component should render properly', () => {
    renderApp();
    expect(screen.getByText('Magic shipping engine')).toBeInTheDocument();
    expect(screen.getByText(WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE)).toBeInTheDocument();
  });

  test('should be able to select wooc shipping type', async () => {
    renderApp();

    const element = screen.getByRole('combobox', {
      name: 'Shipping type:',
    });

    await userEvent.click(element);

    await userEvent.click(screen.getByText('Woocommerce Shipping'));

    await waitFor(() => {
      expect(screen.getByText('Woocommerce shipping settings')).toBeInTheDocument();
      expect(screen.queryByText(WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE)).not.toBeInTheDocument();
    });
  });

  test('should not show any shipping type filter if experimentation is disabled', () => {
    const customExperiment = {
      magic_shopify_shipping_engine: {
        variables: {
          result: 'off',
        },
      },
    };
    renderApp({ abExperiments: customExperiment });
    expect(screen.getByText('Woocommerce shipping settings')).toBeInTheDocument();
    expect(screen.queryByText(WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE)).not.toBeInTheDocument();

    const element = screen.queryByRole('combobox', {
      name: 'Shipping type:',
    });

    expect(element).not.toBeInTheDocument();
  });
});
