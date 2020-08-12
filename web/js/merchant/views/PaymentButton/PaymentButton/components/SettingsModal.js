import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { lenientUrl } from 'common/utils/validators';
import { autoPrefixUrls } from 'common/utils/rzp-utils';

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
      paymentSuccessMessage: props.paymentSuccessMessage || '',
      paymentSuccessRedirectUrl: props.paymentSuccessRedirectUrl || '',
      showCustomMessage: !!props.paymentSuccessMessage,
      showRedirectUrl: !!props.paymentSuccessRedirectUrl,
    };
  }

  handleCustomMessage = (event) => {
    this.setState({
      paymentSuccessMessage: event.target.value,
      isUpdated: true,
    });
  };

  handleRedirectUrl = (event) => {
    this.setState({
      paymentSuccessRedirectUrl: event.target.value,
      isUpdated: true,
    });
  };

  handleShowCustomMessage = () => {
    this.setState({
      showCustomMessage: !this.state.showCustomMessage,
    });
  };

  handleShowRedirectUrl = () => {
    this.setState({
      showRedirectUrl: !this.state.showRedirectUrl,
    });
  };

  onClickSave = () => {
    this.setState({
      isSending: true,
      isUpdated: false,
    });

    const paymentSuccessMessage = this.state.showCustomMessage
      ? this.state.paymentSuccessMessage
      : '';
    const paymentSuccessRedirectUrl = this.state.showRedirectUrl
      ? autoPrefixUrls(this.state.paymentSuccessRedirectUrl)
      : '';

    return this.props
      .editPaymentButton({
        settings: {
          payment_success_message: paymentSuccessMessage,
          payment_success_redirect_url: paymentSuccessRedirectUrl,
        },
      })
      .then((resp) => {
        this.setState({
          isSending: false,
        });

        const track = this.props.track;

        track && track.save();

        this.props.closeModal();
      })
      .catch((err) => {
        this.setState({
          isSending: false,
        });

        track && track.saveFail(err);
      });
  };

  onCustomInputBlur = (e) => {
    this.props.track && this.props.track.customMessage(e.target.value);
  };

  closeModal = () => {
    this.props.closeModal();

    this.props.track && this.props.track.closeModal();
  };

  render() {
    const {
      isSending,
      isUpdated,
      paymentSuccessMessage,
      paymentSuccessRedirectUrl,
      showCustomMessage,
      showRedirectUrl,
    } = this.state;

    const isDisabled = !isUpdated || isSending || paymentSuccessMessage.length < 5;

    return (
      <div class="ButtonSettingsModal">
        <ModalHeader title="Button Settings" />

        <div class="modal-body">
          <Input.Check
            fieldLabel="Show a custom message"
            onChange={this.handleShowCustomMessage}
            defaultValue={showCustomMessage}
          />

          <Input.Textarea
            class="custom-message-input"
            disabled={!showCustomMessage}
            onChange={this.handleCustomMessage}
            placeholder="Add your message here."
            defaultValue={paymentSuccessMessage}
            maxLength="80"
            validator={(value) => {
              if (value && value.length < 5) {
                return 'Success message must contain at least 5 characters.';
              }
            }}
            description={
              <div>
                <span class="chars-pressed">
                  {(paymentSuccessMessage ? paymentSuccessMessage.length : '0') + ' / 80'}
                </span>
                This message is shown after Successful payment and on Payment receipt
              </div>
            }
            onBlur={this.onCustomInputBlur}
          />

          <br />
          <div class="divider" />
          <br />

          <Input.Check
            fieldLabel="Redirect URL"
            onChange={this.handleShowRedirectUrl}
            defaultValue={showRedirectUrl}
          />
          <Input
            class="custom-message-input"
            disabled={!showRedirectUrl}
            onChange={this.handleRedirectUrl}
            defaultValue={paymentSuccessRedirectUrl}
            validator={lenientUrl('Please enter a valid URL')}
            placeholder="Add redirect URL here"
            onBlur={this.onCustomInputBlur}
            description="The customer would be redirected to this URL after Successful payment"
          />
        </div>

        <div class="Modal__actions">
          <button class="btn btn-link m-r" onClick={this.closeModal}>
            Cancel
          </button>
          <button class="btn btn-primary" disabled={isDisabled} onClick={this.onClickSave}>
            Save
          </button>
        </div>
      </div>
    );
  }
}
