import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import ConversionPlatform from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/ConversionPlatform';

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <ConversionPlatform {...props} />
    </Provider>,
  );
};

describe('testing conversion platform component', () => {
  test('component should render properly', () => {
    renderApp({ convertOn: 'whatsapp', setConvertOn: jest.fn() });
    expect(screen.getByText(/^WhatsApp message?/i)).toBeInTheDocument();
  });

  test('should be able to change the dropdown value', async () => {
    const setState = jest.fn();
    renderApp({ convertOn: 'whatsapp', setConvertOn: setState });
    const fieldElement = screen.getByRole('combobox');
    await userEvent.selectOptions(fieldElement, 'whatsapp');

    expect(setState).toHaveBeenCalledWith('whatsapp');
  });

  test('should have whatsapp as the only value when platform is woocommerce', () => {
    const setState = jest.fn();
    renderApp({ convertOn: 'whatsapp', setConvertOn: setState, platform: 'woocommerce' });
    const fieldElement = screen.getByRole('combobox');

    expect(fieldElement).not.toHaveTextContent('Order status page');
    expect(fieldElement).toHaveTextContent('WhatsApp message');
  });

  test('should have more than one option for shopify platform', () => {
    const setState = jest.fn();
    renderApp({ convertOn: 'whatsapp', setConvertOn: setState, platform: 'shopify' });
    const fieldElement = screen.getByRole('combobox');

    expect(fieldElement).toHaveTextContent('Order status page');
    expect(fieldElement).toHaveTextContent('WhatsApp message');
    expect(fieldElement).toHaveTextContent('Both WhatsApp message & Order status page');
  });
});
