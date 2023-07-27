import React from 'react';
import { screen, render, userEvent, waitFor } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import * as NotificationsActions from 'merchant_common/reducers/notifications';
import Modal from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/Modal';

import { MAPPING_TYPES } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';
import { DB_CATEGORY_WITH_ZONE } from 'merchant/views/MagicCheckout/CODSettings/__tests__/mocks/fixtures';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <Modal {...props} />
    </Provider>
  );
};

describe('testing Modal component', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  test('should render properly', () => {
    const props = {
      type: MAPPING_TYPES.CATEGORY,
      item: DB_CATEGORY_WITH_ZONE,
      subText: () => 'Total item: 1',
    };
    render(<App {...props} />);
    expect(screen.getByText('Category 1 configuration')).toBeInTheDocument();
  });

  test('should be able to save configuration', async () => {
    const props = {
      type: MAPPING_TYPES.CATEGORY,
      item: DB_CATEGORY_WITH_ZONE,
      subText: () => 'Total item: 1',
    };
    render(<App {...props} />);
    const saveConfigurationCta = screen.getByRole('button', {
      name: 'Save configuration',
    });

    userEvent.click(saveConfigurationCta);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});
