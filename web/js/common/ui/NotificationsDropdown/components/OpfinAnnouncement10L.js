import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Button from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { classList } from 'common/utils/rzp-utils';
import RTracking from 'react-tracking';
import OpfinAnnouncementForm from './OpfinAnnouncementForm';

import CreditsOfferIcon from "assets/opfin/credits_offer_icon.svg"
import PayrollOfferIcon from "assets/opfin/payroll_offer_icon.svg"

// STYLE - opfin-announcement.styl
const OpfinAnnouncement = ({ id, openModal, closeModal, onClose, tracking }) => {
  const handleCloseAnnouncement = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`announcement_click_popup_screen1_close`, {
        popUpId: id,
      }),
    );
    onClose();
  };

  const handleCloseFormModal = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`announcement_click_popup_screen2_close`, {
        popUpId: id,
      }),
    );
    closeModal();
  };

  const showFormModal = () => {
    closeModal();

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`announcement_click_popup_screen1_cta`, {
        popUpID: id,
      }),
    );

    openModal({
      component: <FormModal onClose={handleCloseFormModal} id={id} tracking={tracking} />,
      size: 'xlarge',
      className: 'OpfinAnnouncement__FormModal--modal',
    });
  };

  return (
    <div className="opfin-announcement-wrapper">
      <ModalHeader onCloseClick={handleCloseAnnouncement} />
      <h2 className="opfin-announcement-title">
        Get exclusive benefits when you use Opfin Payroll
      </h2>
      <div className="opfin-announcement-offer">
        <img src={CreditsOfferIcon} />
        <div>
          <h4 className="opfin-announcement-offer-title">Get 10,00,000 rupees of free credits </h4>
          <p className="opfin-announcement-offer-description">
            Once the offer is applied Razorpay will not charge any fees for upto 10L rupees on your
            account.
          </p>
        </div>
      </div>
      <div className="opfin-announcement-offer">
        <img src={PayrollOfferIcon} />
        <div>
          <h4 className="opfin-announcement-offer-title">3 months of free payroll software</h4>
          <p className="opfin-announcement-offer-description">
            Get 3 months of Opfin's payroll software where you can automate salary payouts, tax
            filing & more.
          </p>
        </div>
      </div>

      <Button.Primary className="opfin-announcement-cta" onClick={showFormModal}>
        Get this offer
      </Button.Primary>
    </div>
  );
};

const FormModal = ({ id, onClose, tracking }) => {
  return (
    <div>
      <button type="button" className={classList('close', 'inverted-close')} onClick={onClose}>
        <i class="i i-close" />
      </button>
      <div className="hbspt-opfin-nitro-wrapper">
        <OpfinAnnouncementForm id={id} tracking={tracking} />
        <div className="hbspt-opfin-nitro-features">
          <p className="hbspot-opfin-nitro-features-title">
            Get your exclusive offer <br />
            when you use Opfin Payroll
          </p>
          <div className="hbspot-opfin-nitro-offer">
            <img src={CreditsOfferIcon} />
            <span>10L of free credits on Razorpay</span>
          </div>
          <div className="hbspot-opfin-nitro-offer">
            <img src={PayrollOfferIcon} />
            <span>
              3 months of Opfin Payroll <br /> software for FREE
            </span>
          </div>
          <ul className="hbspt-opfin-nitro-features-list">
            <li>Automated Payroll</li>
            <li>Compliance Processing</li>
            <li>Easy to understand dashboards</li>
            <li>Easy Reimbursements</li>
            <li>Time & Leave Management</li>
            <li>Employee Self-Service</li>
          </ul>
        </div>
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) => ({
  openModal: bindActionCreators(openModal, dispatch),
  closeModal: bindActionCreators(closeModal, dispatch),
});

export default compose(
  connect(null, mapDispatchToProps),
  RTracking(() => window.rzpQ.component('OpfinAnnouncement')),
)(OpfinAnnouncement);
