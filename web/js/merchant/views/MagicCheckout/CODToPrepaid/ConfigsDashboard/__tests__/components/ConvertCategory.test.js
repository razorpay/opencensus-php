import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import ConvertCategoryField from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/ConvertCategoryField';

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <ConvertCategoryField {...props} />
    </Provider>,
  );
};

describe('testing convert category field', () => {
  test('component should render properly', () => {
    renderApp({ convertRiskCategory: 'high', setConvertRiskCategory: jest.fn() });
    expect(screen.getByText(/high RTO risk orders/i)).toBeInTheDocument();
  });

  test('should be able to change the dropdown value', async () => {
    const setState = jest.fn();
    renderApp({ convertRiskCategory: 'high', setConvertRiskCategory: setState });

    const fieldElement = screen.getByRole('combobox');
    await userEvent.selectOptions(fieldElement, 'medium');

    expect(setState).toHaveBeenCalledWith('medium');
  });
});
