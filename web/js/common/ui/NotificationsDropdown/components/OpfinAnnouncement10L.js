import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import Button from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import Spinner from 'common/ui/Spinner';
import { classList } from 'common/utils/rzp-utils';
import RTracking from 'react-tracking';

// STYLE - opfin-announcement.styl

const hubspotFormMap = {
  'announcement-Nov20-Opfin-NitroV4-cta1': {
    portalId: '5558946',
    formId: '2b883e1b-b6b1-446b-8c58-3ea195692678',
  },
};

const OpfinAnnouncement = ({ id, openModal, closeModal, onClose, tracking }) => {
  const handleCloseAnnouncement = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`announcement_click_popup_screen1_close`, {
        popUpId: id,
      }),
    );
    onClose();
  };

  const handleCloseHubspot = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`announcement_click_popup_screen2_close`, {
        popUpId: id,
      }),
    );
    closeModal();
  };

  const showHubspotForm = () => {
    closeModal();

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`announcement_click_popup_screen1_cta`, {
        popUpID: id,
      }),
    );

    openModal({
      component: (
        <HubspotForm
          onClose={handleCloseHubspot}
          portalId={hubspotFormMap[id].portalId}
          formId={hubspotFormMap[id].formId}
        />
      ),
      size: 'xlarge',
      className: 'OpfinAnnouncement__HubspotForm--modal',
    });
  };

  return (
    <div className="opfin-announcement-wrapper">
      <ModalHeader onCloseClick={handleCloseAnnouncement} />
      <h2 className="opfin-announcement-title">
        Get exclusive benefits when you use Opfin Payroll
      </h2>
      <div className="opfin-announcement-offer">
        <img src="/dist/css/assets/opfin/credits_offer_icon.svg" />
        <div>
          <h4 className="opfin-announcement-offer-title">Get 10,00,000 rupees of free credits </h4>
          <p className="opfin-announcement-offer-description">
            Once the offer is applied Razorpay will not charge any fees for upto 10L rupees on your
            account.
          </p>
        </div>
      </div>
      <div className="opfin-announcement-offer">
        <img src="/dist/css/assets/opfin/payroll_offer_icon.svg" />
        <div>
          <h4 className="opfin-announcement-offer-title">3 months of free payroll software</h4>
          <p className="opfin-announcement-offer-description">
            Get 3 months of Opfin's payroll software where you can automate salary payouts, tax
            filing & more.
          </p>
        </div>
      </div>

      <Button.Primary className="opfin-announcement-cta" onClick={showHubspotForm}>
        Get this offer
      </Button.Primary>
    </div>
  );
};

const HubspotForm = ({ onClose, portalId, formId }) => {
  useEffect(() => {
    if (window.hbspt) {
      window.hbspt.forms.create({
        portalId,
        formId,
        target: '#hbspt-opfin-nitro-form',
        onFormReady: () => {
          setHasFormLoaded(true);
        },
      });
    }
  }, []);

  const [hasFormLoaded, setHasFormLoaded] = useState(false);

  return (
    <div>
      <button
        type="button"
        className={classList('close', hasFormLoaded && 'inverted-close')}
        onClick={onClose}
      >
        <i class="i i-close" />
      </button>
      <div className="hbspt-opfin-nitro-wrapper">
        <div id="hbspt-opfin-nitro-form"></div>
        {hasFormLoaded ? (
          <div className="hbspt-opfin-nitro-features">
            <p className="hbspot-opfin-nitro-features-title">
              Get your exclusive offer <br />
              when you use Opfin Payroll
            </p>
            <div className="hbspot-opfin-nitro-offer">
              <img src="/dist/css/assets/opfin/credits_offer_icon.svg" />
              <span>10L of free credits on Razorpay</span>
            </div>
            <div className="hbspot-opfin-nitro-offer">
              <img src="/dist/css/assets/opfin/payroll_offer_icon.svg" />
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
        ) : (
          <Spinner />
        )}
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
