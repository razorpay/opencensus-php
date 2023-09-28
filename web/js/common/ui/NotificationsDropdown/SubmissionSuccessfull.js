import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import './modalStyle.styl';
import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';

const SubmissionSuccessfull = ({ closeModal }) => {
  const url = 'https://razorpay.com/docs/razorpayx/current-account/';
  const handleClick = () => {
    window.open(url);
  };

  if (isMobileAndTablet()) {
    return (
      <div className="submission-modal" id="submission-modal">
        <div className="header-icons">
          <img
            src={`${window.cdnBaseUrl}/static/assets/neostone-exclusive-offer/xBanking.png`}
            alt="Nitro Campaign XBanking Logo"
          />
          <button type="button" className="modal-close-button" onClick={closeModal}>
            <i className="i i-close" />
          </button>
        </div>
        <img
          src={`${window.cdnBaseUrl}/static/assets/growth-assets/mweb-exclusive-offer/current-account-mweb.svg`}
          alt="Submission Successfull"
          className="gs-image"
        />
        <div className="cta-wrapper">
          <AsyncBtn.Primary className="btn" onClick={handleClick}>
            View Documents Required
          </AsyncBtn.Primary>
        </div>
      </div>
    );
  }

  return (
    <div className="rxca-submit-finish-modal">
      <div className="header">
        <div className="title">
          Congratulations! We're processing your request for a RazorpayX powered Current Account.
        </div>
        <button type="button" className="close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="description">
        <p>
          Our sales heroes will get in touch with you shortly. In the meantime, we highly recommend
          you keep the required documents ready so we can speed up the process.
        </p>
        <a
          href="https://razorpay.com/docs/razorpayx/current-account/"
          target="_blank"
          rel="noreferrer noopener"
        >
          <Button.Primary className="btn btn-primary" type="button">
            View Documents Required
          </Button.Primary>
        </a>
      </div>
      <p className="footer">Once your Current Account is created, your offer will be activated.</p>
    </div>
  );
};

export default compose(
  withRouter,
  connect(
    (state) => {
      return {
        ...state.session.user,
      };
    },
    {
      closeModal: closeModalProp,
    },
  ),
)(SubmissionSuccessfull);
