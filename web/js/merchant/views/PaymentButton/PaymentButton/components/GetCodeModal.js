import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Input from 'common/new-ui/Input';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import ModalHeader from 'common/ui/ModalHeader';
import Spinner from 'common/ui/Spinner';
import { DocLink } from 'merchant/components/DocsLink';
import { fetchPaymentPageEntity as fetchPaymentButtonEntity } from 'merchant/views/PaymentPages/PaymentPages/model';

import { setIsPaymentButtonCodeUsed } from '../../utils';

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

      fetchPaymentButtonEntity(paymentButton.id)
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

    const embedBtnCode = `<form><script src="https://checkout.razorpay.com/v1/payment-button.js" data-payment_button_id="${paymentButton.id}" async> </script> </form>`;

    let children = this.props.children;

    if (!children) {
      children = (
        <div className="docs-link m-t">
          How to use this code?{' '}
          <DocLink
            target="_black"
            href="https://razorpay.com/docs/payment-button/"
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
          <div>Your payment button is ready to go!</div>

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
              to="/paymentbuttons"
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
