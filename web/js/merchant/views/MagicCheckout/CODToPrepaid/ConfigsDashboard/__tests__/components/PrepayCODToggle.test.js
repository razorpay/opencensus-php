import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import PrepayCODToggle from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/PrepayCODToggle';

import * as ModalActions from 'merchant_common/reducers/modals';

import { PREPAY_TOGGLE_PROPS } from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/__tests__/mocks/fixtures';

jest.mock('common/ui/Forms/SwitchField', () => (props) => {
  const { checked, onChange } = props;
  return (
    <div>
      <p>{checked ? 'Checked' : 'Unchecked'}</p>
      <button type="button" onClick={onChange}>
        Toggle
      </button>
    </div>
  );
});

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <PrepayCODToggle {...props} />
    </Provider>,
  );
};

describe('testing prepay cod toggle component', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  test('component should render properly', () => {
    renderApp({ ...PREPAY_TOGGLE_PROPS });
    expect(screen.getByText(/enabled/i)).toBeInTheDocument();
  });

  test('should be able to click on toggle', async () => {
    renderApp({ ...PREPAY_TOGGLE_PROPS });
    const toggleElement = screen.getByRole('button', {
      name: 'Toggle',
    });

    await userEvent.click(toggleElement);
    expect(openModalSpy).toHaveBeenCalled();
  });

  test('should be able to enable toggle if disabled', async () => {
    const setState = jest.fn();
    jest.spyOn(React, 'useState').mockImplementationOnce((initState) => [initState, setState]);

    renderApp({
      ...PREPAY_TOGGLE_PROPS,
      isPrepayCODEnabled: false,
      setIsPrepayCODEnabled: setState,
    });
    expect(screen.getByText(/disabled/i)).toBeInTheDocument();

    const toggleElement = screen.getByRole('button', {
      name: 'Toggle',
    });

    await userEvent.click(toggleElement);
    expect(setState).toBeCalled();
  });
});
