import React, { useState } from 'react';
import Modal from 'react-modal';
import RTracking from 'react-tracking';

const HubspotCAForm = ({ shouldShowModal, hideModal, fromWhere, tracking }) => {
  const [activeView, setActiveView] = useState('detail-view');

  const changeView = () => {
    setActiveView('form-view');
    trackEvents();
    if (window.hbspt) {
      window.hbspt.forms.create({
        portalId: '5558946',
        formId: 'e591bdcd-2304-458e-bc4c-72d3f41a75b8',
        target: '#hbspt-ca-form',
      });
    }
  };

  const handleClose = () => {
    setActiveView('detail-view');
    hideModal();
  };

  const trackEvents = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_popup_screen1_cta`),
    );
  };

  return (
    <Modal
      isOpen={shouldShowModal}
      onRequestClose={hideModal}
      ariaHideApp={false}
      id="hubspot-ca-form-modal"
    >
      <button type="button" class="close" onClick={handleClose}>
        <i class="i i-close" />
      </button>
      <div className="hbspt-form">
        {activeView === 'detail-view' ? (
          <div className="hbspt-form-details">
            <img className="rx-logo" src="https://lp.razorpay.com/hubfs/logo1.png" alt="rx-logo" />
            <div className="section">
              <div className="left-section">
                <h3 className="heading">
                  Get 1.65% pricing when you switch to a RazorpayX Current Account
                </h3>
                <ul className="list">
                  <li>
                    <img src="https://razorpay.com/assets/payouts/footer/footer-pointer.png"></img>
                    Make rule based payouts seamlessly
                  </li>
                  <li>
                    <img src="https://razorpay.com/assets/payouts/footer/footer-pointer.png"></img>
                    Transact 24*7 even on bank holidays
                  </li>
                  <li>
                    <img src="https://razorpay.com/assets/payouts/footer/footer-pointer.png"></img>
                    Get a consolidated view of your finances
                  </li>
                  <li>
                    <img src="https://razorpay.com/assets/payouts/footer/footer-pointer.png"></img>
                    Make Payouts via NEFT/IMPS/RTGS
                  </li>
                  <li>
                    <img src="https://razorpay.com/assets/payouts/footer/footer-pointer.png"></img>
                    Process thousands of payouts at once
                  </li>
                  <li>
                    <img src="https://razorpay.com/assets/payouts/footer/footer-pointer.png"></img>
                    Track & automate all your finances
                  </li>
                </ul>
                <div className="btn-wrapper">
                  <button class="btn btn-primary logout-btn" onClick={changeView}>
                    Apply For Offer
                  </button>
                </div>
              </div>
              <div className="right-section">
                <img src="https://razorpay.com/assets/x/macbook.svg" alt="macbook-img"></img>
              </div>
            </div>
          </div>
        ) : (
          <div className="hbspt-ca-form" id="hbspt-ca-form"></div>
        )}
      </div>
    </Modal>
  );
};

export default RTracking({
  page: 'ScheduledNitroBanner',
})(HubspotCAForm);
