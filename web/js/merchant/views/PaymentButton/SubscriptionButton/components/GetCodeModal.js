import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Input from 'common/new-ui/Input';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import ModalHeader from 'common/ui/ModalHeader';
import Spinner from 'common/ui/Spinner';
import { DocLink } from 'merchant/components/DocsLink';
import { setIsPaymentButtonCodeUsed } from 'merchant/views/PaymentButton/utils';
import { fetchPaymentPageEntity as fetchsubscriptionButtonEntity } from 'merchant/views/PaymentPages/PaymentPages/model';

class GetCodeModal extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      paymentButton: props.paymentButton,
      isLoading: true,
    };
  }

  componentDidMount() {
    const { paymentButton } = this.state;

    if (!paymentButton.settings) {
      this.setState({
        isLoading: true,
      });

      fetchsubscriptionButtonEntity(paymentButton.id)
        .then((resp) => {
          this.setState({
            paymentButton: resp.data,
            isLoading: false,
          });
        })
        .catch(() => {});

      return;
    }

    this.setState({
      isLoading: false,
    });
  }

  onClickCopy = () => {
    setIsPaymentButtonCodeUsed({
      mid: this.props.user.current,
      mode: this.props.mode,
    });

    this.props.onCodeCopy && this.props.onCodeCopy();
  };

  onClickTextArea = () => {
    /* istanbul ignore next */
    if (!this.textarea) {
      return;
    }

    this.textarea.select();
    document.execCommand && document.execCommand('copy');
  };

  render() {
    if (this.state.isLoading) {
      return (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    const { className, title, afterEmbedButton, closeModal } = this.props;

    const { paymentButton } = this.state;

    const embedBtnCode = `<form><script src="https://cdn.razorpay.com/static/widget/subscription-button.js" data-subscription_button_id="${paymentButton.id}" data-button_theme="${paymentButton.settings.payment_button_theme}" async> </script> </form>`;

    let children = this.props.children;

    if (!children) {
      children = (
        <div className="docs-link m-t">
          How to use this code?{' '}
          <DocLink
            target="_black"
            href="https://razorpay.com/docs/payment-button/subscription-buttons/"
            onClick={this.props.onClickSeeDocumentation}
          >
            See documentation <i className="i i-external-link" />
          </DocLink>
        </div>
      );
    }

    return (
      <div className={className}>
        <ModalHeader title={title} onCloseClick={closeModal} />

        <div className="modal-body">
          <div>Your subscription button is ready to go!</div>

          <div className="embed-button-form">
            <Input.Textarea
              label={() => (
                <div>
                  <strong>HTML Code</strong>
                  <div className="description">
                    Copy & Paste this HTML in your code
                    <CustomClipboard value={embedBtnCode}>
                      <button onClick={this.onClickCopy} className="btn btn-xs copy-btn">
                        <i className="i i-copy m-r" />
                        COPY CODE
                      </button>
                    </CustomClipboard>
                  </div>
                </div>
              )}
              className="Input--vTop"
              value={embedBtnCode.trim()}
              readOnly
              setRef={(textarea) => (this.textarea = textarea)}
              onClick={this.onClickTextArea}
            />

            {afterEmbedButton}
          </div>

          {children}

          <div className="btn-toolbar">
            <Link
              className="btn btn-default btn-block m-t"
              to="/subscription_buttons"
              onClick={closeModal}
            >
              <b>Back to Dashboard</b>
            </Link>
          </div>
        </div>
      </div>
    );
  }
}

export default connect((state) => ({
  user: state.session.user,
  mode: state.session.mode,
}))(GetCodeModal);
