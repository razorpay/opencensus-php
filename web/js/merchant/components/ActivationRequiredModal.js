import { useEffect } from 'react';
import { NavLink } from 'react-router-dom';
import ModalHeader from 'common/ui/ModalHeader';
import ShowWhen from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { PARTNER_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';
import {
  checkEligibilityForFeeBasedGating,
  handleFeeBasedGatingNavigation,
} from 'merchant/utils/feeBasedGatingUtils';
import { getCookie } from 'common/utils/cookies';

export default ({ onCloseClick, user }) => {
  const activationName =
    !user.showInstantActivation || !user.instantActivation.isL1Submitted ? 'Activation' : 'KYC';

  let modalTitle;
  if (user.isHardLimitReached) {
    modalTitle = 'Account Under Review';
  } else {
    modalTitle = `${activationName} Required`;
  }

  useEffect(() => {
    if (user.isOrgCurlec) {
      analyticsTrack({
        objectName: 'Test Mode Toggle Curlec',
        actionName: 'clicked',
        screen: 'home page',
        properties: {
          new: 'test',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }, []);

  let isSGMerchant = false;
  useEffect(() => {
    isSGMerchant = getCookie('rzp_user_merchant_region') === 'SG';
  }, []);

  const activationUrl = user.isActivationFormFullView ? '/kyc' : '/activation';

  const shouldRedirectToEasyFlow =
    user.partner_type === PARTNER_TYPE.AGGREGATOR ||
    user.partner_type === PARTNER_TYPE.PURE_PLATFORM;

  const isEligibleForFeeBasedGating = checkEligibilityForFeeBasedGating(user);

  let modalBody = (
    <div>
      You can only use Razorpay in test mode until your account is activated. <br />
      <ShowWhen
        additionalCondition={() => Boolean(user?.isAllowedEdit) && user.isAllowedEdit('activation')}
      >
        {user.isOrgAxis
          ? 'Please reach out to the Axis Bank to get yourself activated'
          : isSGMerchant
          ? 'Sales will reach out to you for more details'
          : `Please fill and submit the ${activationName} Form to access live mode.`}
        {!user.isOrgAxis && !isSGMerchant ? (
          <div class="Modal__actions text-right">
            {!shouldRedirectToEasyFlow ? (
              <button class="btn btn-primary btn-block" onClick={redirectToEasyAfter1sec}>
                Fill {activationName} Form
              </button>
            ) : (
              <NavLink to={activationUrl} onClick={onCloseClick}>
                <button class="btn btn-primary btn-block">Fill {activationName} Form</button>
              </NavLink>
            )}
          </div>
        ) : null}
      </ShowWhen>
    </div>
  );
  if (user.isOrgCurlec) {
    modalBody = (
      <div>
        Thank you for expressing your interest. We will reach out to you within 24 hours to activate
        your account.
      </div>
    );
  }

  if (user.isSubmitted || user.isRejected || user.needsClarification) {
    const modalAction = (
      <div class="Modal__actions text-right">
        <button class="btn btn-primary btn-block" onClick={onCloseClick}>
          Okay!
        </button>
      </div>
    );

    if (user.isRejected) {
      modalBody = (
        <div>
          You cannot switch to live mode as your activation request was not accepted by our partner
          banks. We would not be able support your business at this moment. We have sent you an
          email with details.
          {modalAction}
        </div>
      );
    } else if (user.needsClarification) {
      modalBody = (
        <div>
          You cannot switch to live mode as your account isn&apos;t activated yet.
          {modalAction}
        </div>
      );
    } else if (user.isHardLimitReached) {
      modalBody = (
        <div>
          Our compliance team and partner banks carry out routine audits of your KYC documents. We
          might temporarily pause your settlements during this time, but don&apos;t worry, just look
          for clarifications asked by our team on your registered email. Once we receive the
          clarifications, we will resume your settlements. Upon receiving your response, we will be
          able to process the application within 2 days and re enable settlements for you. Please
          note, you can still accept payments from your customers.{' '}
          <a
            href="https://knowledgebase.razorpay.com/support/solutions/articles/11000103841-why-is-my-settle[%E2%80%A6]ld-and-my-account-under-review-after-getting-activated"
            target="_blank"
            rel="noreferrer noopener"
          >
            More details
          </a>
          {modalAction}
        </div>
      );
    } else if (isEligibleForFeeBasedGating) {
      modalTitle = 'KYC Verification Required';
      const feeBasedGatingModalAction = (
        <div className="Modal__actions text-right">
          <button
            className="btn btn-primary btn-block"
            onClick={() => {
              handleFeeBasedGatingNavigation({ ctaLocation: 'Activation Required Modal' });
              onCloseClick();
            }}
          >
            Get KYC Verified
          </button>
        </div>
      );
      modalBody = (
        <div>
          You can only use Razorpay in test mode until your account is activated.
          <br />
          Please get your KYC verified to access live mode.
          {feeBasedGatingModalAction}
        </div>
      );
    } else {
      modalBody = (
        <div>
          You can only use Razorpay in test mode until your account is activated.
          <br />
          Your account is Under Review. We will reach out on your contact email for all updates or
          any clarifications that we may require.
          {modalAction}
        </div>
      );
    }
  }

  return (
    <div>
      <ModalHeader title={modalTitle} onCloseClick={onCloseClick} />
      <div className="modal-body">{modalBody}</div>
    </div>
  );
};
