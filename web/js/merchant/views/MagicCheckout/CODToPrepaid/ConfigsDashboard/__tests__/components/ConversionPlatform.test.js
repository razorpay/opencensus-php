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
});
