import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';
import { autoPrefixUrls } from 'common/utils/rzp-utils';
import { lenientUrl } from 'common/utils/validators';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

class SettingsModal extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isUpdating: false,
      paymentSuccessMessage: props.paymentSuccessMessage || '',
      paymentSuccessRedirectUrl: props.paymentSuccessRedirectUrl || '',
      showCustomMessage: !!props.paymentSuccessMessage,
      showRedirectUrl: !!props.paymentSuccessRedirectUrl,
    };
  }

  handleCustomMessage = (event) => {
    this.setState({
      paymentSuccessMessage: event.target.value,
    });
  };

  handleRedirectUrl = (event) => {
    this.setState({
      paymentSuccessRedirectUrl: event.target.value,
    });
  };

  handleShowCustomMessage = () => {
    const { track } = this.props;

    if (track && track.hasOwnProperty('customMessageCheckbox')) {
      track.customMessageCheckbox(!this.state.showCustomMessage);
    }
    this.setState((prevState) => ({
      showCustomMessage: !prevState.showCustomMessage,
    }));
  };

  handleShowRedirectUrl = () => {
    const { track } = this.props;

    if (track && track.hasOwnProperty('redirectURLCheckbox')) {
      track.redirectURLCheckbox(!this.state.showRedirectUrl);
    }

    this.setState((prevState) => ({
      showRedirectUrl: !prevState.showRedirectUrl,
    }));
  };

  onClickSave = () => {
    this.setState({
      isUpdating: true,
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
          isUpdating: false,
        });

        if (resp && resp.data) {
          const track = this.props.track;

          if (track) {
            track.save();
          }

          this.props.closeModal();
        }
      })
      .catch((err) => {
        const track = this.props.track;

        this.setState({
          isUpdating: false,
        });

        if (track) {
          track.saveFail(err);
        }
      });
  };

  onCustomInputBlur = (e) => {
    if (this.props.track) {
      this.props.track.customMessage(e.target.value);
    }
  };

  closeModal = () => {
    this.props.closeModal();

    if (this.props.track) {
      this.props.track.closeModal();
    }
  };

  render() {
    const {
      isUpdating,
      paymentSuccessMessage,
      paymentSuccessRedirectUrl,
      showCustomMessage,
      showRedirectUrl,
    } = this.state;

    return (
      <div className="ButtonSettingsModal">
        <ModalHeader title="Button Settings" />

        <div className="modal-body">
          <Input.Check
            fieldLabel="Show a custom message"
            onChange={this.handleShowCustomMessage}
            defaultValue={showCustomMessage}
          />

          <Input.Textarea
            className="custom-message-input"
            disabled={!showCustomMessage}
            onChange={this.handleCustomMessage}
            placeholder="Add your message here."
            defaultValue={paymentSuccessMessage}
            maxLength="80"
            // eslint-disable-next-line consistent-return
            validator={(value) => {
              if (value && value.length < 5) {
                return 'Success message must contain at least 5 characters.';
              }
            }}
            description={
              <div>
                <span className="chars-pressed">
                  {`${paymentSuccessMessage ? paymentSuccessMessage.length : '0'} / 80`}
                </span>
                This message is shown after Successful payment and on Payment receipt
              </div>
            }
            onBlur={this.onCustomInputBlur}
          />

          <br />
          <div className="divider" />
          <br />

          <Input.Check
            fieldLabel="Redirect URL"
            onChange={this.handleShowRedirectUrl}
            defaultValue={showRedirectUrl}
          />
          <Input
            className="custom-message-input"
            disabled={!showRedirectUrl}
            onChange={this.handleRedirectUrl}
            defaultValue={paymentSuccessRedirectUrl}
            validator={lenientUrl('Please enter a valid URL')}
            placeholder="Add redirect URL here"
            onBlur={this.onCustomInputBlur}
            description="The customer would be redirected to this URL after Successful payment"
          />
        </div>

        <div className="Modal__actions">
          <button className="btn btn-link m-r" onClick={this.closeModal}>
            Cancel
          </button>
          <button className="btn btn-primary" disabled={isUpdating} onClick={this.onClickSave}>
            Save
          </button>
        </div>
      </div>
    );
  }
}

export default connect(null, {
  openModal,
  closeModal,
  showNotification,
})(SettingsModal);
