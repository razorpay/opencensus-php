import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import LinkValidity from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/LinkValidity';

import { LINK_VALIDITY_PROPS } from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/__tests__/mocks/fixtures';

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <LinkValidity {...props} />
    </Provider>,
  );
};

describe('testing the link validity component', () => {
  test('component should render properly', () => {
    renderApp();
    expect(screen.getByText(/^Order conversion validity?/i)).toBeInTheDocument();
  });

  test('should be able to select from drop down', async () => {
    const setState = jest.fn();

    renderApp({ ...LINK_VALIDITY_PROPS, setValidityType: setState });
    const fieldElement = screen.getByRole('combobox');

    await userEvent.selectOptions(fieldElement, '30 minutes');
    expect(setState).toHaveBeenCalledWith('1800');
  });
});
