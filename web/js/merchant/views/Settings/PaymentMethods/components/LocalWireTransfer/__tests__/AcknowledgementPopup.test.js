import { render, screen, userEvent, waitFor } from 'test-utils';

import AcknowledgementPopup from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/AcknowledgementPopup';

import * as services from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';
import {
  activateAccountError,
  activateAccountPending,
  activateAccountSuccess,
} from 'merchant/reducers/b2bExports/actions';
import * as modalActions from 'merchant_common/reducers/modals';
import * as notifications from 'merchant_common/reducers/notifications';

jest.mock('merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services');

const renderComponent = (props = {}) => {
  return render(<AcknowledgementPopup {...props} />);
};

describe('Acknowledgement Popup flow', () => {
  const closeModal = jest.spyOn(modalActions, 'closeModal');
  const showNotification = jest.spyOn(notifications, 'showNotification');

  beforeEach(() => {
    closeModal.mockClear();
    showNotification.mockClear();
  });

  test('button should get disabled when checkbox is unchecked', async () => {
    renderComponent();

    //checkbox should be unchecked
    expect(screen.getByRole('checkbox')).toBeChecked();

    //fire event to uncheck the checkbox
    await userEvent.click(screen.getByRole('checkbox'));

    //checkbox should get unchecked
    expect(screen.getByRole('checkbox')).not.toBeChecked();

    //button should be visible and disabled
    expect(screen.getByText('Activate Now')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Activate Now'));
    expect(services.activateAccount).not.toHaveBeenCalled();
  });

  test('modal should close when api request results in an success', async () => {
    services.activateAccount.mockImplementation(() => (dispatch) => {
      dispatch(activateAccountPending({ type: 'localBankTransfer', va_currency: 'ach' }));
      return new Promise((resolve) => {
        setTimeout(() => {
          dispatch(activateAccountSuccess({ type: 'localBankTransfer', response: null }));
          resolve({ success: true });
        });
      });
    });

    renderComponent();

    //mocking activate now click event
    await userEvent.click(screen.getByText('Activate Now'));

    //close modal should have been called
    await waitFor(() => expect(closeModal).toHaveBeenCalledTimes(1));
    await waitFor(() => expect(showNotification).toHaveBeenCalledTimes(1));
    expect(showNotification).toHaveBeenCalledWith({
      type: 'success',
      message: 'Account have been successfully created!',
    });
  });

  test('modal should not close when api request results in an error', async () => {
    services.activateAccount.mockImplementation(() => (dispatch) => {
      dispatch(activateAccountPending({ type: 'localBankTransfer', va_currency: 'ach' }));
      return new Promise((_, reject) => {
        setTimeout(() => {
          dispatch(
            activateAccountError({ type: 'localBankTransfer', error: { errors: ['api error'] } }),
          );
          reject({ errors: ['api error'] });
        });
      });
    });

    renderComponent();

    //mocking activate now click event
    await userEvent.click(screen.getByText('Activate Now'));

    //close modal should have been called
    await waitFor(() => expect(showNotification).toHaveBeenCalledTimes(1));
    expect(closeModal).not.toHaveBeenCalled();
    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: ['api error'],
    });
  });
});
