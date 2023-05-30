import { screen, render, userEvent, waitFor } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ConfirmSettings from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/ConfirmSettings';

import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <ConfirmSettings {...props} />
    </Provider>
  );
};

describe('ConfirmSettings', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
    closeModalSpy.mockClear();
  });

  test('should render ConfirmSettings modal', () => {
    render(<App />);
    expect(screen.getByText('Save & apply settings?')).toBeInTheDocument();
  });

  test('should apply settings & close modal', async () => {
    render(<App />);
    const saveBtn = screen.getByText('Save & apply settings');
    expect(saveBtn).toBeInTheDocument();
    await userEvent.click(saveBtn);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
      expect(closeModalSpy).toHaveBeenCalled();
    });
  });
});
