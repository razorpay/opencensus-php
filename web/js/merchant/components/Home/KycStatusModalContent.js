import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import SupportButton from './SupportButton';

import { Link } from 'react-router-dom';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, getCommonSegmentProperties } from 'common/utils/rzp-utils';

export const kycModalContent = (args = {}) => {
  const activationState = getActivationState(args.activationData, args.isUnregisteredBusiness);
  const L2_dedupe_blocked = activationState === 'L2_dedupe_blocked';

  switch (activationState) {
    case 'L2_dedupe_blocked':
    case 'L1_dedupe_blocked': {
      return {
        title: 'Business not supported',
        subtitle: null,
        body: (
          <div>
            <div>
              We cant support your business because it doesnt meet our compliance requirements
            </div>
            <div>If you think this is a mistake please reach out to our support</div>
            {L2_dedupe_blocked &&
              'In case you have pending settlements, you can raise a ticket and get your funds settled to your account.'}
          </div>
        ),
        background: 'warning',
        button: (
          <SupportButton
            type="button"
            buttonLabel="Contact Support"
            category="merchant"
            openSection="account-activation"
          />
        ),
      };
    }
    case 'payment_disabled':
    case 'poi_failed': {
      return {
        title: 'Few more details required',
        subtitle: null,
        body: (
          <div>
            For your business model we need a few more KYC details to alow you to accept payments
          </div>
        ),
        background: 'pending',
        button: (
          <button
            className="btn btn-primary"
            onClick={() => {
              args.tracking?.trackEvent(
                window.rzpQ.onbr().initiated('kyc.form_fill', {
                  clickSource: 'form submission popup',
                }),
              );
              analyticsTrack({
                objectName: 'L2 Start',
                actionName: 'form fill initiated',
                screen: 'home page',
                properties: {
                  clickSource: 'form submission popup',
                  ...getCommonSegmentProperties(),
                  milestone: 'L2 Start',
                },
              });
              args.onClose();
              args.goToActivationForm();
            }}
          >
            Complete KYC
          </button>
        ),
      };
    }
    case 'poi_initiated': {
      return {
        title: 'Reviewing your details',
        subtitle: null,
        body: (
          <div>
            We are reviewing your submitted KYC details. We will notify you when the review is
            complete. You can try out our products until then
          </div>
        ),
        background: 'pending',
        button: (
          <button
            className="btn btn-primary"
            onClick={() => {
              args.onClose();
              args.openPaymentAcceptModal();
            }}
          >
            Try our products
          </button>
        ),
      };
    }
    case 'poi_verified':
    case 'L1_instantly_activated': {
      if (args.activationData?.isAutoPLEnabled) {
        return null;
      }
      return {
        title: 'Payments have been enabled',
        subtitle: 'Ready to accept payments',
        body: (
          <div>
            You can now accept payments by integrating with our payment products upto <b>₹15000.</b>
            <br />
            <br />
            To extend the limit and to get your payments settled, please provide us a few more KYC
            details.
          </div>
        ),
        background: 'success',
        button: (
          <>
            <button
              className="btn btn-default KYC__more_details"
              onClick={() => {
                analyticsTrack({
                  objectName: 'L2 Start',
                  actionName: 'form fill initiated',
                  screen: 'home page',
                  properties: {
                    clickSource: 'form submission popup',
                    ...getCommonSegmentProperties(),
                    milestone: 'L2 Start',
                  },
                });
                args.onClose();
                args.goToActivationForm();
              }}
            >
              Complete KYC
            </button>
            <button
              className="btn btn-primary"
              onClick={() => {
                args.onClose();
                args.openPaymentAcceptModal();
              }}
            >
              Accept Payments
            </button>
          </>
        ),
      };
    }
    case 'under_review_without_tnc_partial': {
      return {
        title: 'KYC is under review',
        subtitle: 'Payments have been temporarily paused',
        body: (
          <div>
            Our compliance team and banking partners are reviewing your KYC and your payments have
            been temporarily paused. We will review your KYC and reach out to you for any
            clarifications within 3-4 days.
            <br />
            Meanwhile you can generate your Terms and Conditons page. Your KYC review might get
            delayed in case of delays in generating TnC
          </div>
        ),
        background: 'pending',
        button: (
          <button
            className="btn btn-primary"
            onClick={() => {
              args.tracking?.trackEvent(
                window.rzpQ.onbr().initiated('act.generate_page_now', {
                  clickSource: 'KYC submitted, TnC Popup',
                }),
              );
              analyticsTrack({
                objectName: 'Act Generate Page Now',
                actionName: 'initiated',
                screen: 'home page',
                properties: {
                  clickSource: 'post activation tnc popup',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              args.onClose();
              args.generatePage();
            }}
          >
            Generate TnC Page
          </button>
        ),
      };
    }

    case 'under_review_with_tnc_partial': {
      return {
        title: 'KYC Under Review',
        subtitle: 'Payments have been temporarily paused',
        body: (
          <div>
            Our compliance team and banking partners are reviewing your KYC and your payments have
            been temporarily paused.
            <br />
            We will review your KYC and reach out to you for any clarifications within 3-4 days.
          </div>
        ),
        background: 'pending',
        button: (
          <button className="btn btn-primary" onClick={args.onGoToDashboard}>
            Back to Dashboard
          </button>
        ),
      };
    }

    case 'under_review_without_tnc_passed': {
      return {
        title: 'KYC is under review',
        subtitle: 'Payment limits have been removed',
        body: (
          <div>
            Your payment limits have been removed and KYC is under review. It usually takes 3-4
            business days. We will reach out to you in case we need any clarifications
            <br />
            Meanwhile you can generate your Terms and Conditons page. Your KYC review might get
            delayed in case of delays in generating TnC
          </div>
        ),
        background: 'pending',
        button: (
          <button
            className="btn btn-primary"
            onClick={() => {
              args.tracking?.trackEvent(
                window.rzpQ.onbr().initiated('act.generate_page_now', {
                  clickSource: 'KYC submitted, TnC Popup',
                }),
              );
              analyticsTrack({
                objectName: 'Act Generate Page Now',
                actionName: 'initiated',
                screen: 'home page',
                properties: {
                  clickSource: 'post activation tnc popup',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              args.onClose();
              args.generatePage();
            }}
          >
            Generate TnC Page
          </button>
        ),
      };
    }

    case 'under_review_with_tnc_passed': {
      return {
        title: 'KYC Under Review',
        subtitle: 'Payment limits have been removed',
        body: (
          <div>
            Your payment limits have been removed and KYC is under review. KYC review process
            usually takes 3-4 working days.
            <br />
            We will notify you if we require any clarifications on your KYC.
          </div>
        ),
        background: 'pending',
        button: (
          <button className="btn btn-primary" onClick={args.onGoToDashboard}>
            Back to Dashboard
          </button>
        ),
      };
    }

    case 'under_review_without_tnc': {
      return {
        title: 'KYC is under review',
        subtitle: 'Our team is reviewing your KYC details',
        body: (
          <div>
            Your KYC details are under review. It usually takes 3-4 business days. We will reach out
            to you in case we need any clarifications
            <br />
            Meanwhile you can generate your Terms and Conditons page. Your KYC review might get
            delayed in case of delays in generating TnC
          </div>
        ),
        background: 'pending',
        button: (
          <button
            className="btn btn-primary"
            onClick={() => {
              args.tracking?.trackEvent(
                window.rzpQ.onbr().initiated('act.generate_page_now', {
                  clickSource: 'KYC submitted, TnC Popup',
                }),
              );
              analyticsTrack({
                objectName: 'Act Generate Page Now',
                actionName: 'initiated',
                screen: 'home page',
                properties: {
                  clickSource: 'post activation tnc popup',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              args.onClose();
              args.generatePage();
            }}
          >
            Generate TnC Page
          </button>
        ),
      };
    }

    case 'under_review_with_tnc': {
      return {
        title: 'KYC Under Review',
        subtitle: 'Our team is reviewing your KYC details',
        body: (
          <div>
            KYC review process usually takes 3-4 working days.
            <br />
            We will notify you if we require any clarifications on your KYC.
          </div>
        ),
        background: 'pending',
        button: (
          <button className="btn btn-primary" onClick={args.onGoToDashboard}>
            Back to Dashboard
          </button>
        ),
      };
    }

    case 'needs_clarification': {
      return {
        title: 'KYC Clarification',
        body: (
          <div>
            We need some clarification regarding your KYC details. Please clarify at the earliest to
            get your KYC approved
          </div>
        ),
        background: 'pending',
        button: (
          <Link to="/activation" onClick={() => args.onClose()} className="btn btn-primary">
            Update Details
          </Link>
        ),
      };
    }

    case 'needs_clarification_mcc_pending': {
      return {
        title: 'KYC Clarification',
        body: (
          <div>
            Your KYC details require further clarifications. Update required details within 1 day,
            otherwise your settlements might get paused.
          </div>
        ),
        background: 'pending',
        button: (
          <Link to="/activation" onClick={() => args.onClose()} className="btn btn-primary">
            Update Details
          </Link>
        ),
      };
    }

    case 'rejected': {
      return {
        title: 'Account Rejected',
        body: (
          <div>
            We can't support your business because it doesn't meet our compliance requirements.
            <br />
            Please raise a ticket to request for settlement of any payments accepted through your
            account
          </div>
        ),
        background: 'warning',
        button: (
          <SupportButton
            type="button"
            buttonLabel="Contact Support"
            category="merchant"
            openSection="account-activation"
          />
        ),
      };
    }

    case 'funds_on_hold': {
      return {
        title: 'KYC Under Review',
        subtitle: 'Funds  have been temporarily put on hold',
        body: (
          <div>
            Our compliance team and banking partners are reviewing your KYC and your payments have
            been temporarily paused. We will review your KYC and reach out to you for any
            clarifications within 3-4 days.
          </div>
        ),
        background: 'pending',
        button: (
          <button className="btn btn-primary" onClick={args.onGoToDashboard}>
            Back to Dashboard
          </button>
        ),
      };
    }

    default:
      return null;
  }
};
