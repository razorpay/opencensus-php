import {
  getActivationState,
  getNcExpiryDate,
  redirectToEasyAfter1sec,
} from 'merchant/components/Activation/ActivationUtils';
import SupportButton from './SupportButton';
import { Link } from 'react-router-dom';
import { SAMPLE_TICKET } from 'merchant/views/TicketSupport/components/data';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

export const kycModalContent = (args = {}, navigate) => {
  const activationState = getActivationState(
    args.activationData,
    args.isUnregisteredBusiness,
    args.isNcEligibile,
  );
  const activationFormUrl = args.isActivationFormFullView ? '/kyc' : '/activation';
  const isSignupWithEasyOnboarding = args?.user?.user?.signup_campaign === EASY_ONBOARDING;
  const expiryDate = getNcExpiryDate(args.activationData?.kyc_clarification_reasons);

  switch (activationState) {
    case 'L2_dedupe_blocked':
    case 'L1_dedupe_blocked': {
      return {
        title: 'Business not supported',
        subtitle: null,
        body: (
          <div>
            Your current business category is not supported by our banking partners. If you wish to
            reconsider and update, please reach out to us via support.
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
            <p>
              For your business model we need a few more KYC details to allow you to accept payments
            </p>
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
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'Few more details required',
                  'CTA Label': 'Complete KYC',
                },
              });
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
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'Reviewing your details',
                  'CTA Label': 'Try our products',
                },
              });
            }}
          >
            Try our products
          </button>
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
            delayed in case of delays in generating TnC.
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
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC is under review',
                  'CTA Label': 'Generate TnC Page',
                },
              });
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
          <button
            className="btn btn-primary"
            onClick={() => {
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC is under review',
                  'CTA Label': ' Back to Dashboard',
                },
              });
              args.onGoToDashboard();
            }}
          >
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
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC is under review',
                  'CTA Label': 'Generate TnC Page',
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
          <button
            className="btn btn-primary"
            onClick={() => {
              analyticsTrack({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC is under review',
                  'CTA Label': 'Back to Dashboard',
                },
              });
              args.onGoToDashboard();
            }}
          >
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
            <br />
            <i>We will notify you if we require any clarifications on your KYC.</i>
          </div>
        ),
        background: 'pending',
        button: (
          <button
            className="btn btn-primary"
            onClick={() => {
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC under review',
                  'CTA Label': 'Back to Dashboard',
                },
              });
              args.onGoToDashboard();
            }}
          >
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
          <Link
            to={!isSignupWithEasyOnboarding ? activationFormUrl : ''}
            onClick={() => {
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC Clarification',
                  'CTA Label': 'Update Details',
                },
              });
              if (isSignupWithEasyOnboarding) {
                args.trackEvents({
                  objectName: 'redirect to easy-dashboard CTA',
                  actionName: 'Redirect',
                  screen: 'home page',
                  properties: {
                    'CTA Label': 'Update Details',
                  },
                });
                redirectToEasyAfter1sec();
              } else {
                navigate(activationFormUrl);
              }
              args.onClose();
            }}
            className="btn btn-primary"
          >
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
          <Link
            to={!isSignupWithEasyOnboarding ? activationFormUrl : ''}
            onClick={() => {
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC under review',
                  'CTA Label': 'Update Details',
                },
              });
              if (isSignupWithEasyOnboarding) {
                args.trackEvents({
                  objectName: 'redirect to easy-dashboard CTA',
                  actionName: 'Redirect',
                  screen: 'home page',
                  properties: {
                    'CTA Label': 'Update Details',
                  },
                });
                redirectToEasyAfter1sec();
              } else {
                navigate(activationFormUrl);
              }
              args.onClose();
            }}
            className="btn btn-primary"
          >
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
            clarifications within 3-4 days. Meanwhile, you can request a call from our team.
          </div>
        ),
        background: 'pending',
        button: (
          <button
            className="btn btn-primary"
            onClick={() => {
              if (window.rzpTicketSystem) {
                window.rzpTicketSystem.openModal(`#schedule-call`, {
                  ticket: SAMPLE_TICKET,
                });
              }
              args.trackEvents({
                objectName: 'Pop Up CTA',
                actionName: 'Clicked',
                screen: 'home page',
                properties: {
                  'Pop-up Label': 'KYC under review',
                  'CTA Label': 'Request a call',
                },
              });
              args.onClose();
            }}
          >
            Request a call
          </button>
        ),
      };
    }

    case 'needs_clarification_payments_settlement_enabled': {
      return {
        title: 'We need a few more details to complete KYC verification',
        body: (
          <div>
            {`You will not be able to receive payments in your bank account if the required details are not updated before ${expiryDate} `}
          </div>
        ),
        pill: 'ACTION REQUIRED',
        button: (
          <button type="button" className="btn btn-primary nc-button" onClick={args.goToNCOnEasy}>
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
          <button type="button" className="btn btn-primary nc-button" onClick={args.goToNCOnEasy}>
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
            You’ll be able to collect payments and receive them in your bank account only after the
            required details are updated
          </div>
        ),
        pill: 'ACTION REQUIRED',
        button: (
          <button type="button" className="btn btn-primary nc-button" onClick={args.goToNCOnEasy}>
            Resolve now
          </button>
        ),
      };
    }

    default:
      return null;
  }
};
