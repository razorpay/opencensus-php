import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';
import Popover, { PopoverBody } from 'common/ui/Popover';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(null, {
  openModal,
  closeModal,
  showNotification,
})
export default class SettingsModal extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isSending: false,
      isUpdated: false,
      paymentSuccessMessage: props.paymentSuccessMessage,
      showCustomMessage: !!props.paymentSuccessMessage,
    };
  }

  handleCustomMessage = event => {
    this.setState({
      paymentSuccessMessage: event.target.value,
      isUpdated: true,
    });
  };

  handleShowCustomMessage = () => {
    this.setState({
      showCustomMessage: !this.state.showCustomMessage,
      isUpdated: true,
    });
  };

  onClickSave = () => {
    this.setState({
      isSending: true,
    });

    return this.props
      .editPaymentButton({
        settings: {
          payment_success_message: this.state.showCustomMessage
            ? this.state.paymentSuccessMessage
            : '',
        },
      })
      .then(resp => {
        this.setState({
          isSending: false,
        });
        this.props.closeModal();
      })
      .catch(err => {
        this.setState({
          isSending: false,
        });
      });
  };

  togglePageReceiptModal = () => {
    this.props.togglePageReceiptModal();

    this.props.preservePaymentSuccessMessage(this.state.paymentSuccessMessage);
  };

  render() {
    const { closeModal } = this.props;
    const {
      isSending,
      isUpdated,
      paymentSuccessMessage,
      showCustomMessage,
    } = this.state;

    const isDisabled =
      !isUpdated ||
      isSending ||
      (paymentSuccessMessage && paymentSuccessMessage.length < 5);

    return (
      <div class="ButtonSettingsModal">
        <ModalHeader title="Button Settings" />

        <div class="modal-body">
          <div class="payment-receipt">
            <div class="receipt-heading">
              <strong>Payment Receipts</strong>
              <button class="btn-link" onClick={this.togglePageReceiptModal}>
                Configure <i class="i i-arrow-forward" />
              </button>
            </div>
            <div class="description">
              Send automated payment receipts to your customers on successful
              payments.
            </div>
          </div>

          <hr />

          <Input.Check
            fieldLabel="Show custom message after a payment"
            onChange={this.handleShowCustomMessage}
            defaultValue={showCustomMessage}
          />

          <Input.Textarea
            class="custom-message-input"
            disabled={!showCustomMessage}
            onChange={this.handleCustomMessage}
            defaultValue={paymentSuccessMessage}
            maxLength="80"
            validator={value => {
              if (value.length < 5) {
                return 'Success message must contain at least 5 characters.';
              }
            }}
            description={
              <span class="chars-pressed">
                {(paymentSuccessMessage ? paymentSuccessMessage.length : '0') +
                  ' / 80'}
              </span>
            }
          />
        </div>

        <div class="Modal__actions">
          <button class="btn btn-link m-r" onClick={closeModal}>
            Cancel
          </button>
          <button
            class="btn btn-primary"
            disabled={isDisabled}
            onClick={this.onClickSave}
          >
            Save
          </button>
        </div>
      </div>
    );
  }
}
