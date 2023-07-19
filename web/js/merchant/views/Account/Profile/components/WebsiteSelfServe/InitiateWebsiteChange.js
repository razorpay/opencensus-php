import { useContext } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import UpdateWebsiteDetails from './UpdateWebsiteDetails';
import TwoFactorVerificaionContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { FLOWS } from './Constants';

function InitiateWebsiteChange(props) {
  const context = useContext(TwoFactorVerificaionContext);

  const onContactVerified = () => {
    const { user } = props;

    props.openModal({
      size: 'small',
      component: <UpdateWebsiteDetails flowType={props.flowType} />,
      overlayStyles: { display: 'block' },
    });

    // Avoid tracking for additional website flow
    if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) return;

    analyticsTrack({
      objectName: `Website 2fa result`,
      actionName: '2FA request',
      screen: 'My account',
      properties: {
        flow: user.has_key_access ? 'Website edit' : 'Website add',
        result: 'Success',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const onProceedClick = () => {
    const { user } = props;
    let analyticsObject;

    context.criticalFlow({
      modes: ['test', 'live'],
      onUserTwoFaVerified: () => {
        onContactVerified();
      },
      onWrongOtpCallback: () => {
        analyticsTrack({
          objectName: `Website 2fa result`,
          actionName: '2FA request',
          screen: 'My account',
          properties: {
            flow: user.has_key_access ? 'Website edit' : 'Website add',
            result: 'Failure',
            reason: 'Wrong OTP submitted',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
    });

    // Avoid tracking for additional website flow
    if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) return;

    // Edit flow
    if (user.has_key_access) {
      analyticsObject = {
        objectName: `Bmc acknowledged`,
        actionName: 'Proceed clicked',
        screen: 'My account',
        properties: {
          flow: 'Website edit',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    } else {
      // Add flow
      analyticsObject = {
        objectName: `Bmc acknowledged`,
        actionName: 'Proceed clicked',
        screen: 'My account',
        properties: {
          flow: 'Website add',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    }

    analyticsTrack(analyticsObject);
  };

  const title =
    props.flowType === FLOWS.ADDITIONAL_WEBSITE
      ? `Add Website/App`
      : props.user.has_key_access
      ? 'Update Website/App'
      : 'Add new Website/App';

  // user should have key access, user should not fully activated and user has payment enabled
  if (props.user.has_key_access && !props.user.isAccepted && props.user.isActivated) {
    return (
      <div class="website-self-serve-initiate-modal">
        <ModalHeader
          title="You can add a new website/app URL later"
          onCloseClick={props.closeModal}
        />
        <p class="amp-content">
          We're currently reviewing the website/app you've already shared. Once the review is
          complete, you'll be able to add a new website/app without any hassle.
        </p>
      </div>
    );
  }

  return (
    <div class="website-self-serve-initiate-modal">
      <ModalHeader title={title} onCloseClick={props.closeModal} />
      <div class="img-container">
        <img src="https://cdn.razorpay.com/static/assets/website-self-serve/Website-change.svg" />
      </div>
      <div class="content">
        <strong>Keep in mind</strong>
        <span>
          <ol>
            <li>
              The product/services that you are selling on the website/app should fall under
              “e-commerce” category
            </li>
            <li>
              Open a new Razorpay account if your new website/app falls under a different category
            </li>
          </ol>
        </span>
      </div>
      <div class="action">
        <button class="btn btn-primary" onClick={onProceedClick}>
          Proceed to update website/app
          <i class="i i-chevron-right" />
        </button>
      </div>
    </div>
  );
}

export default InitiateWebsiteChange;
