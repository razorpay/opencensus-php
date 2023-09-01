import AcknowledgementPopup from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/AcknowledgementPopup';
import {
  VA_USD,
  VA_SWIFT,
  ACTIVATION_POPUP_CONTENT,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import * as services from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';
import * as modalActions from 'merchant_common/reducers/modals';
import * as notifications from 'merchant_common/reducers/notifications';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock('merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services');

const renderComponent = (props = {}) => {
  return render(<AcknowledgementPopup {...props} />);
};

const activateAccountSuccess = () => {
  services.activateAccount.mockImplementation(
    () =>
      new Promise((resolve) => {
        setTimeout(() => {
          resolve({ success: true });
        });
      }),
  );
};

const activateAccountFailure = (errors) => {
  services.activateAccount.mockImplementation(
    () =>
      new Promise((_, reject) => {
        setTimeout(() => {
          reject({ errors, success: false });
        });
      }),
  );
};

describe('Acknowledgement Popup flow', () => {
  const closeModal = jest.spyOn(modalActions, 'closeModal');
  const showNotification = jest.spyOn(notifications, 'showNotification');

  beforeEach(() => {
    closeModal.mockClear();
    showNotification.mockClear();
  });

  test('should disable activate button if checkbox is unchecked', async () => {
    renderComponent();

    const content = ACTIVATION_POPUP_CONTENT[VA_USD];

    expect(screen.queryByText(content.title)).toBeInTheDocument();

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

  test('should close the modal when api request results in an success', async () => {
    activateAccountSuccess();

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

  test('should not close the modal when api request results in an list of errors', async () => {
    const errors = ['Something went wrong. Please try again later', 'Status 400'];

    activateAccountFailure(errors);

    renderComponent();

    //mocking activate now click event
    await userEvent.click(screen.getByText('Activate Now'));

    //close modal should have been called
    await waitFor(() => expect(showNotification).toHaveBeenCalledTimes(1));
    expect(closeModal).not.toHaveBeenCalled();
    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: errors[0],
    });
  });

  test('should not close the modal when api request results in an single error', async () => {
    const error = 'Something went wrong. Please try again later';

    activateAccountFailure(error);

    renderComponent();

    //mocking activate now click event
    await userEvent.click(screen.getByText('Activate Now'));

    //close modal should have been called
    await waitFor(() => expect(showNotification).toHaveBeenCalledTimes(1));
    expect(closeModal).not.toHaveBeenCalled();
    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: error,
    });
  });

  test('should render SWIFT account with terms and condition checkbox', async () => {
    renderComponent({
      account: VA_SWIFT,
    });

    const content = ACTIVATION_POPUP_CONTENT[VA_SWIFT];

    expect(screen.queryByRole('checkbox')).toBeInTheDocument();
    expect(screen.queryByText('Activate Now')).toBeInTheDocument();
    expect(screen.queryByText(content.title)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Activate Now'));
    expect(services.activateAccount).toHaveBeenCalledWith(VA_SWIFT, 1);
  });

  test('should not render Terms and conditions checkbox if showTnC is false', async () => {
    renderComponent({
      account: VA_SWIFT,
      showTnC: false,
    });

    expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    await userEvent.click(screen.getByText('Activate Now'));
    expect(services.activateAccount).toHaveBeenCalledWith(VA_SWIFT, 0);
  });

  test('should show notification when api request results in an list of errors', async () => {
    const errors = ['Something went wrong. Please try again later', 'Status 400'];

    activateAccountFailure(errors);

    renderComponent();

    await userEvent.click(screen.getByText('Activate Now'));
    expect(services.activateAccount).toHaveBeenCalledWith(VA_USD, 1);

    await waitFor(() =>
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: errors[0],
      }),
    );
  });

  test('should show notification when api request results in an one error', async () => {
    const error = 'Something went wrong. Please try again later';

    activateAccountFailure(error);

    renderComponent();

    await userEvent.click(screen.getByText('Activate Now'));
    expect(services.activateAccount).toHaveBeenCalledWith(VA_USD, 1);

    await waitFor(() =>
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: error,
      }),
    );
  });
});
