import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

class SettingsModal extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isSending: false,
      isUpdated: false,
      paymentSuccessMessage: props.paymentSuccessMessage || '',
      showCustomMessage: !!props.paymentSuccessMessage,
    };
  }

  handleCustomMessage = (event) => {
    this.setState({
      paymentSuccessMessage: event.target.value,
      isUpdated: true,
    });
  };

  handleShowCustomMessage = () => {
    this.setState({
      showCustomMessage: !this.state.showCustomMessage,
    });
  };

  onClickSave = () => {
    this.setState({
      isSending: true,
      isUpdated: false,
    });

    return this.props
      .editPaymentButton({
        settings: {
          payment_success_message: this.state.showCustomMessage
            ? this.state.paymentSuccessMessage
            : '',
        },
      })
      .then((resp) => {
        this.setState({
          isSending: false,
        });

        // const track = this.props.track;

        // track && track.save();

        this.props.closeModal();
      })
      .catch((err) => {
        this.setState({
          isSending: false,
        });

        // track && track.saveFail(err);
      });
  };

  onCustomInputBlur = (e) => {
    // this.props.track && this.props.track.customMessage(e.target.value);
  };

  closeModal = () => {
    this.props.closeModal();

    // this.props.track && this.props.track.closeModal();
  };

  render() {
    const { isSending, isUpdated, paymentSuccessMessage, showCustomMessage } = this.state;

    const isDisabled = !isUpdated || isSending || paymentSuccessMessage.length < 5;

    return (
      <div className="ButtonSettingsModal">
        <ModalHeader title="Button Settings" />

        <div className="modal-body">
          <Input.Check
            fieldLabel="Show custom message after a payment"
            onChange={this.handleShowCustomMessage}
            defaultValue={showCustomMessage}
          />

          <Input.Textarea
            className="custom-message-input"
            disabled={!showCustomMessage}
            onChange={this.handleCustomMessage}
            defaultValue={paymentSuccessMessage}
            maxLength="80"
            validator={(value) => {
              if (value.length < 5) {
                return 'Success message must contain at least 5 characters.';
              }
            }}
            description={
              <span className="chars-pressed">
                {`${paymentSuccessMessage ? paymentSuccessMessage.length : '0'} / 80`}
              </span>
            }
            onBlur={this.onCustomInputBlur}
          />
        </div>

        <div className="Modal__actions">
          <button className="btn btn-link m-r" onClick={this.closeModal}>
            Cancel
          </button>
          <button className="btn btn-primary" disabled={isDisabled} onClick={this.onClickSave}>
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
