import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import CODPrepaidSavedConfigs from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/containers/CODPrepaidSavedConfigs';

import { SAVED_CONFIGS_VIEW_PROPS } from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/__tests__/mocks/fixtures';

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <CODPrepaidSavedConfigs {...props} />
    </Provider>,
  );
};

describe('testing COD prepaid saved configs view', () => {
  test('should show enable conversion for config if manual review is opted', () => {
    renderApp({ ...SAVED_CONFIGS_VIEW_PROPS });
    expect(screen.getByText(/enable conversion for/i)).toBeInTheDocument();
  });

  test('should not show enable conversion for config if manual review is not opted', () => {
    renderApp({ ...SAVED_CONFIGS_VIEW_PROPS, isManualReviewOpted: false });
    expect(screen.queryByText(/enable conversion for/i)).not.toBeInTheDocument();
  });

  test('should be able to click on edit cta', async () => {
    renderApp({ ...SAVED_CONFIGS_VIEW_PROPS });
    const editCta = screen.getByTestId('edit-cta');
    await userEvent.click(editCta);
  });
});
