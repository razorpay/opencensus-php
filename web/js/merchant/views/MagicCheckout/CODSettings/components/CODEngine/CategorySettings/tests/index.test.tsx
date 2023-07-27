import React from 'react';
import { screen, render, waitFor, userEvent, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import CategorySettings from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/CategorySettings';
import {
  DB_FEE_RULE,
  DB_COUNTRIES,
} from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { createCategoryFailureHandler } from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/handlers';

jest.mock(
  'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle',
  () => (props) => {
    const { onToggle } = props;
    return (
      <button data-testId="product-category-toggle" onClick={onToggle}>
        Toggle click
      </button>
    );
  },
);

const initState = {
  magicCODEngine: {
    loading: {
      summary: false,
      fee_rules: false,
      zones: false,
      item_categories: false,
      mapping: false,
    },
    error: {},
    configs: {
      cod_engine: true,
      cod_engine_type: 'product',
      shop_id: 'magic-checkout-test-store-1',
      engine: 'Advance',
      rate_slabs: true,
    },
    fee_rules: [DB_FEE_RULE],
    zones: [DB_COUNTRIES],
    item_categories: [],
    validations: {
      fee_rules: true,
      zones: true,
      item_categories: true,
    },
  },
};

const openModalSpy = jest.spyOn(ModalActions, 'openModal');
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <CategorySettings {...props} />
    </Provider>
  );
};

describe('testing category settings', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
    showNotificationSpy.mockClear();
  });

  test('component should render properly', async () => {
    render(<App />);

    await waitFor(() => {
      expect(screen.getByText('Product categories')).toBeInTheDocument();
    });
  });

  test('should be able to toggle switch', async () => {
    server.use(createCategoryFailureHandler());

    render(<App />);
    let toogleSwitch;
    await waitFor(() => {
      toogleSwitch = screen.getByRole('button', {
        name: 'Toggle click',
      });
    });

    userEvent.click(toogleSwitch);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});
