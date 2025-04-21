import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import {
  getNcExpiryDate,
  isNewNcActivationStatus,
} from 'merchant/components/Activation/ActivationUtils';
import ImgNcKyc from 'assets/onboarding/ncKyc.svg';
import Image from 'common/ui/Image';
import { analyticsTrack } from 'common/utils/analytics';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

const NCModal = ({
  isActivationFormFullView,
  onClose,
  activationState,
  kycClarificationsReasons,
  goToNCOnEasy,
  user,
}) => {
  const activationUrl = isActivationFormFullView ? '/kyc' : '/activation';

  const sessionExpired = window.session_id !== window.sessionStorage.getItem('isNewNc');

  const getContent = () => {
    switch (activationState) {
      case 'needs_clarification_payments_settlement_enabled': {
        const expiryDate = getNcExpiryDate(kycClarificationsReasons);
        return {
          title: 'We need a few more details to complete KYC verification',
          body: (
            <div>
              {`You will not be able to receive payments in your bank account if the required details are not updated before ${expiryDate} `}
            </div>
          ),
          pill: 'ACTION REQUIRED',
          button: (
            <button className="btn btn-primary nc-button" type="button" onClick={goToNCOnEasy}>
              Resolve now
            </button>
          ),
        };
      }

      case 'needs_clarification_with_payments_enabled': {
        return {
          title: 'We need a few more details to complete KYC verification',
          body: (
            <div>
              You’ll be able to receive collected payments in your account only after the required
              details are updated
            </div>
          ),
          pill: 'ACTION REQUIRED',
          button: (
            <button className="btn btn-primary nc-button" type="button" onClick={goToNCOnEasy}>
              Resolve now
            </button>
          ),
        };
      }

      case 'needs_clarification_with_payment_disabled': {
        return {
          title: 'We need a few more details to complete KYC verification',
          body: (
            <div>
              You’ll be able to collect payments and receive them in your bank account only after
              the required details are updated
            </div>
          ),
          pill: 'ACTION REQUIRED',
          button: (
            <button className="btn btn-primary nc-button" type="button" onClick={goToNCOnEasy}>
              Resolve now
            </button>
          ),
        };
      }
      case 'bdd_needs_clarification': {
        return {
          title: 'Action Required: Update Your KYC Details',
          body: (
            <div>
              You won’t be able to collect payments from customers or receive settlements in your
              bank account until you update the required KYC details.
            </div>
          ),
          pill: 'ACTION REQUIRED',
          button: (
            <button className="btn btn-primary nc-button" type="button" onClick={goToNCOnEasy}>
              Resolve now
            </button>
          ),
        };
      }
      default:
        return null;
    }
  };

  const content = getContent();
  const showNewNCModal = isNewNcActivationStatus && sessionExpired && !!content;
  useEffect(() => {
    if (showNewNCModal) {
      analyticsTrack({
        objectName: 'NC Entry Modal',
        actionName: 'Loaded',
        screen: 'home page',
        properties: {
          activationState,
          funnelStage: 'NC',
          formName: content?.title,
          ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
          deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
          ...getCommonSegmentProperties(),
        },
        toCleverTap: true,
      });
    }
  }, []);

  const getContentJsx = () => {
    if (showNewNCModal) {
      return (
        <ModalMask>
          <Modal className="pan-status-modal nc-modal" showCloseBtn={false}>
            <div className="modal-header">
              <Image src={ImgNcKyc} alt="nc kyc" className="nc-img" />
            </div>
            <div className="modal-body">
              {content.pill ? <span className="status-pill">{content.pill}</span> : null}
              <h1>{content.title}</h1>
              <div className="modal-description">{content.body}</div>
              {content.button}
            </div>
          </Modal>
        </ModalMask>
      );
    }
    if (!isNewNcActivationStatus) {
      return (
        <ModalMask>
          <Modal className="nc-status-modal" onClose={onClose}>
            <div className="modal-header warning">
              <h1>KYC Clarification</h1>
            </div>
            <div className="modal-body">
              <div className="modal-description">
                <div>
                  <p>We need clarifications on few details to complete KYC verification</p>
                  <br />
                  <p>
                    Update these details to help us activate your account faster once we resume
                    onboarding new businesses
                  </p>
                </div>
              </div>
              <Link to={activationUrl}>
                <button className="btn btn-primary" onClick={onClose} type="button">
                  Add Clarifications
                </button>
              </Link>
            </div>
          </Modal>
        </ModalMask>
      );
    }
    return null;
  };

  return <div>{getContentJsx()}</div>;
};

export default NCModal;
