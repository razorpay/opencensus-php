import { withRouter } from 'common/deprecated/withRouter';

import GetCodeModal from './GetCodeModal';
import { DocLink } from 'merchant/components/DocsLink';
import React from 'react';

class SuccessModal extends React.Component {
  onClickButtonSettings = () => {
    this.props.history.push(`/paymentbuttons/${this.props.paymentButton.id}/payments`);

    this.props.updateHighlightButtonSettings(this.props.paymentButton.id);

    this.props.onClickButtonSettings && this.props.onClickButtonSettings();
  };

  render() {
    const { paymentButton, isEditExistingId, ...extraProps } = this.props;

    const title = isEditExistingId ? 'Button updated successfully' : 'Button created successfully';

    const description = (
      <>
        <b>{paymentButton.title}</b> button is ready to go!
      </>
    );

    const modalTitle = (
      <>
        <img src={require("assets/success-tick-green.svg")} /> {title}
      </>
    );

    const docLink = (
      <div className="docs-link m-t">
        How to use this code?{' '}
        <DocLink
          target="_blank"
          href="https://razorpay.com/docs/payment-button/"
          onClick={this.props.onClickSeeDocumentation}
        >
          See our documentation <i className="i i-external-link" />
        </DocLink>
      </div>
    );

    return (
      <GetCodeModal
        paymentButton={paymentButton}
        title={modalTitle}
        description={description}
        className="success-screen"
        afterEmbedButton={docLink}
        {...extraProps}
      >
        <div className="receipt-description">
          <div className="description-title">Actions After a Successful Payment</div>
          <div className="description-list">
            <li> Show a custom message.</li>
            <li> Send automated payment receipts.</li>
            <div>
              Configure these options in
              <button className="btn-link" onClick={this.onClickButtonSettings}>
                Button Settings <i className="i i-arrow-forward" />
              </button>
            </div>
          </div>
        </div>
      </GetCodeModal>
    );
  }
}

export default withRouter(SuccessModal);
