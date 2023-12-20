import React from 'react';
import styled from 'styled-components';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Link as Redirect } from 'react-router-dom';
import Links from '@razorpay/blade-old/src/atoms/Link';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import {
  isUnregisteredBusiness,
  checkIfDedupe,
  isL1Submitted,
  getFormatedCurrency,
  getNcExpiryDate,
} from 'merchant/views/onboarding/mobile/services/utils';
import { showProductsModal } from 'merchant/reducers/home';
import { getMode, switchMode } from 'common/services/mode';
import Info from './Info';
import Buttons from './Buttons';
import * as Messages from './Constants';
import { useApp } from 'common/context/App';
import useTrackEvents from 'merchant/hooks/useTrackEvents';
import { IReferee } from 'merchant/views/onboarding/mobile/Screens/Home';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { isMobileDevice } from 'merchant/components/Home/data';
import useEligibility from 'merchant/views/onboarding/mobile/hooks/useEligibility';
import {
  checkEligibilityForFeeBasedGating,
  handleFeeBasedGatingNavigation,
} from 'merchant/utils/feeBasedGatingUtils';

const InlineText = styled.span`
  color: #162f5661;
`;

const Pill = styled.span`
  background: #d12d2d;
  border-radius: 12px;
  padding: 4px 12px;
  color: white;
  margin-bottom: 16px;
  display: block;
  width: fit-content;
  font-size: 10px;
`;

const CurrentActivationProgress: React.FC<
  RouteComponentProps & {
    data: any;
    escalation: any;
    referee: IReferee | undefined;
    showProductModal?: any;
  }
> = ({ data, escalation, history, referee, showProductModal }) => {
  const { user, experiments, submerchantId } = useApp();
  const trackEvents = useTrackEvents();
  const eligibilityData = useEligibility();
  const isReferredMerchant = referee?.status === 'signup';
  const activationFormUrl = experiments.isActivationFormFullView ? 'kyc' : 'activation';
  const isSignupWithEasyOnboarding = user?.user?.signup_campaign === EASY_ONBOARDING;
  const isEasyNcEnabled = eligibilityData?.nc_revamp_enabled;
  const isEligibleForFeeBasedGating = checkEligibilityForFeeBasedGating(user);

  const expiryDate = getNcExpiryDate(user?.kyc_clarification_reasons);

  const onCTAClick = () => {
    if (submerchantId) {
      history.push(`/partners/submerchants/onboarding/acc_${submerchantId}/steps`);
    } else {
      history.push('/onboarding/steps');
    }
    trackEvents({
      objectName: `${isL1Submitted(data.activation_form_milestone) ? 'L2' : 'L1'} Form`,
      actionName: 'initiated',
      screen: 'home page',
      properties: {
        ctaLabel: 'Submit KYC',
        ctaLocation: 'Obnoarding banner',
      },
      toCleverTap: true,
    });

    trackEvents({
      objectName: `${isL1Submitted(data.activation_form_milestone) ? 'L2' : 'L1'} Start`,
      actionName: 'form fill',
      screen: 'home page',
      eventAction: 'initiated',
      properties: {
        milestone: `${isL1Submitted(data.activation_form_milestone) ? 'L2' : 'L1'} Start`,
      },
      activationType: isL1Submitted(data.activation_form_milestone) ? 'kyc' : 'act',
    });
  };

  const isTestMode = getMode(user.current) === 'test';

  const contactSupport = () => {
    window.rzpTicketSystem?.openModal('#ticket');
  };

  const goToNcFlow = (trackProps = {}) => {
    if (isEasyNcEnabled) {
      trackEvents({
        objectName: 'NC Resolve Now',
        actionName: 'Clicked',
        screen: 'home page',
        properties: {
          funnelStage: 'NC',
          ctaClicked: 'Resolve Now',
          clickSource: 'Onboarding banner',
          ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
          deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
          ...trackProps,
        },
      });
      window.open(`${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`);
    } else if (submerchantId) {
      history.push(`/partners/submerchants/acc_${submerchantId}/activation`);
    } else {
      history.push(activationFormUrl);
    }
  };

  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const dedupeStatus = checkIfDedupe({ ...data, isInstantActivationEnabled });
  const statusLog = data?.activationStatusChangeLogs || [];

  if (
    (dedupeStatus === 'blocked' ||
      (data.activation_flow === 'blacklist' && isSignupWithEasyOnboarding && data.submitted)) &&
    (isL1Submitted(data.activation_form_milestone) || data.submitted) &&
    !data.activated &&
    (data.activation_status !== 'activated' || data.activation_status !== 'rejected')
  ) {
    return (
      <>
        <Info
          title={isInstantActivationEnabled ? Messages.DEDUPE.title : Messages.DEDUPE.old_title}
          description={
            isInstantActivationEnabled
              ? Messages.DEDUPE.description
              : Messages.DEDUPE.old_description
          }
          titleColor="negative.900"
          hasError
          descriptionJSX={
            data.activation_form_milestone === 'L2' ? (
              <InlineText>{Messages.DEDUPE.L2_description}</InlineText>
            ) : (
              <span />
            )
          }
        />
        <Buttons.Primary onClick={() => contactSupport()} title="Contact Support" />
      </>
    );
  }

  if (data.submitted) {
    if (
      experiments.canGenerateTnCPage &&
      !data.business_website &&
      !data.merchant_tnc &&
      ['under_review', 'kyc_qualified_unactivated', 'activated_mcc_pending'].includes(
        data.activation_status,
      )
    ) {
      let title = 'Payments and Settlements have been enabled, Generate TnC';
      let titleColor = 'shade.970';
      let desc = isTestMode
        ? Messages.GENERATE_TNC.mcc_pending.old_description_with_test
        : Messages.GENERATE_TNC.mcc_pending.old_description_with_live;
      let descriptionJSX: React.ReactNode = isTestMode && (
        <Redirect to="/tncform">Generate TnC</Redirect>
      );

      if (
        data.activation_status === 'under_review' ||
        data.activation_status === 'kyc_qualified_unactivated'
      ) {
        title = Messages.GENERATE_TNC.under_review.old_title;
        titleColor = 'neutral.960';
        desc = Messages.GENERATE_TNC.under_review.description;
        descriptionJSX = <Redirect to="/onboarding/steps">View submitted details</Redirect>;
        if (dedupeStatus === 'partial_match' && isInstantActivationEnabled) {
          title = Messages.GENERATE_TNC.under_review.partial_match_title;
          desc = Messages.GENERATE_TNC.under_review.partial_match_desc;
        } else if (
          !statusLog.includes('needs_clarification') &&
          !!data.activated &&
          isInstantActivationEnabled
        ) {
          title = Messages.GENERATE_TNC.under_review.title;
        }
      } else if (data.activation_status === 'activated_mcc_pending' && isInstantActivationEnabled) {
        title = Messages.GENERATE_TNC.mcc_pending.title;
        desc = Messages.GENERATE_TNC.mcc_pending.description;
      }

      return (
        <>
          <Info
            title={title}
            titleColor={titleColor}
            description={desc}
            descriptionJSX={descriptionJSX}
          />
          {data.activation_status === 'under_review' ||
          data.activation_status === 'kyc_qualified_unactivated' ||
          (data.activation_status === 'activated_mcc_pending' &&
            (!isTestMode || isInstantActivationEnabled)) ? (
            <Buttons.Primary
              onClick={() => {
                history.push('/tncform');
                trackEvents({
                  objectName: 'Act',
                  actionName: 'generate page now',
                  screen: 'home page',
                  properties: { clickSource: 'onboarding card' },
                  eventAction: 'initiated',
                });
              }}
              title="Generate Terms And Conditions"
            />
          ) : (
            data.activation_status === 'activated_mcc_pending' &&
            isTestMode && (
              <Buttons.Secondary
                onClick={() => {
                  switchMode(user.current, 'live');
                  window.location.reload();
                }}
                title="Switch To Live Mode"
              />
            )
          )}
        </>
      );
    }

    if (data.isHardLimitReached && data.merchant.hold_funds) {
      const title = isInstantActivationEnabled
        ? Messages.HARD_LIMIT_REACHED.title
        : Messages.HARD_LIMIT_REACHED.old_title;

      return (
        <Info
          title={title}
          titleColor="neutral.960"
          description={Messages.HARD_LIMIT_REACHED.description}
          descriptionJSX={
            <Links
              href="https://knowledgebase.razorpay.com/support/solutions/articles/11000103841-why-is-my-settle[%E2%80%A6]ld-and-my-account-under-review-after-getting-activated"
              target="_blank"
              rel="noreferrer noopener"
            >
              More details
            </Links>
          }
        />
      );
    }

    if (
      data.activation_status === 'under_review' ||
      data.activation_status === 'kyc_qualified_unactivated'
    ) {
      let title = Messages.ACTIVATION_STATUS_UNDER_REVIEW.old_flow.title;
      let description = '';
      let titleColor = 'neutral.960';

      if (isUnregisteredBusiness(data.business_type)) {
        description = `This process usually takes ${
          data.kyc_clarification_reasons?.nc_count ? ' 3 ' : ' 3 - 4 '
        } working days after your first transaction. If we need any more information we will reach out to you on your registered email id.`;
      } else if (!data.isAutoKycDone) {
        description = `KYC Review process usually takes ${
          data.kyc_clarification_reasons?.nc_count ? ' 3 ' : '3 - 4'
        } working days. We will notify you if we require any clarifications on your KYC.`;
      } else {
        description = Messages.ACTIVATION_STATUS_UNDER_REVIEW.old_flow.description;
      }

      if (isInstantActivationEnabled) {
        if (dedupeStatus === 'partial_match') {
          title = Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.partial_match_title;
          description = Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.partial_match_desc;
        } else if (statusLog.includes('needs_clarification') || !data.activated) {
          description = Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.post_nc_description;
        } else {
          title = Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.title;
          titleColor = 'positive.960';
          description =
            Messages.ACTIVATION_STATUS_UNDER_REVIEW.new_flow.description_with_payment_enable;
        }
      }
      return (
        <>
          <Info title={title} titleColor={titleColor} description={description} />
          <Buttons.LinkButton
            onClick={onCTAClick}
            title="View Submitted Details"
            icon="arrowRight"
          />
        </>
      );
    }

    if (data.activation_status === 'kyc_qualified_unactivated') {
      const title = 'KYC verified successfully';
      const titleColor = 'positive.960';
      const description =
        'We are working to take your account live in the next 2-3 days and you will be able to start accepting payments immediately post that. There is no action required from your end. We thank you for your patience.';
      return (
        <>
          <Info title={title} titleColor={titleColor} description={description} />
        </>
      );
    }

    if (data.activation_status === 'needs_clarification') {
      let description = Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.description.normal;
      if (isInstantActivationEnabled) {
        if (!data?.merchant?.hold_funds && statusLog.includes('activated_mcc_pending')) {
          description =
            Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.description.activated_mcc_pending;
        } else if (statusLog.includes('activated_mcc_pending') && data?.merchant?.hold_funds) {
          description = Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.description.funds_onhold;
        }
      }

      if (isEasyNcEnabled) {
        let description = '';
        if (data.activated && !data.merchant.hold_funds) {
          description = `Update the required details before ${expiryDate} to avoid settlements for your account being put on-hold`;
        } else if (data.activated && data.merchant.hold_funds) {
          description =
            'You’ll be able to receive collected payments in your account only after the required details are updated';
        } else if (!data.activated) {
          description =
            'You’ll be able to collect payments and receive them in your bank account only after the required details are updated';
        }
        const title = Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.title;
        return (
          <>
            <Pill>{Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.pill}</Pill>
            <Info title={title} description={description} isNewNCEnabled />
            <Buttons.Primary
              onClick={() => goToNcFlow({ formName: title })}
              title={Messages.NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS.buttonText}
            />
          </>
        );
      }

      return (
        <>
          <Info
            title={Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.title}
            description={description}
            titleColor="negative.900"
            hasError
          />
          <Buttons.Primary onClick={() => goToNcFlow()} title="Clarify Details" />
        </>
      );
    }

    if (isEligibleForFeeBasedGating) {
      const title = 'KYC Verification Pending';
      const description = 'Get your business KYC verified to start collecting live payments.';
      const titleColor = 'neutral.960';
      return (
        <>
          <Info title={title} titleColor={titleColor} description={description} />
          <Buttons.LinkButton
            onClick={() => handleFeeBasedGatingNavigation({ ctaLocation: 'Activation Card' })}
            title="Get KYC verified"
          />
        </>
      );
    }

    if (data.activation_status === 'rejected') {
      let description = Messages.ACTIVATION_STATUS_REJECTED.old_description;
      let title = Messages.ACTIVATION_STATUS_REJECTED.old_title;
      if (isInstantActivationEnabled) {
        description = Messages.ACTIVATION_STATUS_REJECTED.description;
        title = Messages.ACTIVATION_STATUS_REJECTED.title;
      }
      return <Info title={title} description={description} titleColor="negative.900" hasError />;
    }

    if (data.activation_status === 'activated') {
      return (
        <>
          <Info
            title={Messages.ACTIVATION_STATUS_ACTIVATED.title}
            description={Messages.ACTIVATION_STATUS_ACTIVATED.description}
          />
          {isTestMode && (
            <Buttons.Secondary
              onClick={() => {
                switchMode(user.current, 'live');
                window.location.reload();
              }}
              title="Switch To Live Mode"
            />
          )}
        </>
      );
    }

    if (data.activation_status === 'activated_mcc_pending') {
      if (
        data.activation_progress === 90 &&
        experiments.isActivationMccPendingProgressbarDisabled
      ) {
        const description =
          Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.when_progress_bar_not_required;
        return (
          <>
            <Info title="" description={description} />

            <Buttons.Secondary
              onClick={() => {
                showProductModal();
              }}
              title="Accept Payments Now"
            />
          </>
        );
      } else {
        let title = Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.title;
        let description = Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.description;
        if (isInstantActivationEnabled) {
          title = Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.new_title;
          description = Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.new_description;
        }
        return (
          <>
            <Info title={title} description={description} />
            {isTestMode && (
              <Buttons.Secondary
                onClick={() => {
                  switchMode(user.current, 'live');
                  window.location.reload();
                }}
                title="Switch To Live Mode"
              />
            )}
          </>
        );
      }
    }

    if (data.bank_details_verification_status === 'failed') {
      return (
        <>
          <Info
            title={Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.title}
            description={Messages.BANK_DETAILS_VERIFICATION_STATUS.failed.description}
            titleColor="negative.900"
          />
          <Buttons.Primary
            onClick={onCTAClick}
            title="Upload Bank Account Proof"
            icon="arrowRight"
          />
        </>
      );
    }
  } else if (isUnregisteredBusiness(data.business_type) || !isInstantActivationEnabled) {
    if (
      ((data.poi_verification_status === 'incorrect_details' ||
        data.poi_verification_status === 'not_matched') &&
        !experiments.canSkipPoiValidation) ||
      (data.poi_verification_status === 'initiated' &&
        data.activation_form_milestone === 'L1' &&
        experiments.isL2AllowedForPoiInitiated)
    ) {
      return (
        <>
          <Info
            title={Messages.POI_VERIFICATION_STATUS.incorrect_details.title}
            description={Messages.POI_VERIFICATION_STATUS.incorrect_details.description}
            titleColor="negative.900"
            hasError
          />
          <Buttons.Primary onClick={onCTAClick} title="Review Details" icon="arrowRight" />
        </>
      );
    }

    if (data.poi_verification_status === 'failed' && !experiments.canSkipPoiValidation) {
      return (
        <>
          <Info
            title={Messages.POI_VERIFICATION_STATUS.failed.title}
            description={Messages.POI_VERIFICATION_STATUS.failed.description}
            titleColor="negative.900"
            hasError
          />
          <Buttons.Primary onClick={onCTAClick} title="Try Again" />
        </>
      );
    }

    if (data.poi_verification_status === 'pending' && !isInstantActivationEnabled) {
      return (
        <>
          <Info
            title={Messages.POI_VERIFICATION_STATUS.pending.title}
            description={Messages.POI_VERIFICATION_STATUS.pending.description}
            titleColor="neutral.960"
          />
          {!data.submitted && experiments.canSkipPoiValidation && (
            <Buttons.LinkButton
              onClick={onCTAClick}
              title="Fill Remaining Details"
              icon="arrowRight"
            />
          )}
        </>
      );
    }
    if (
      data.poi_verification_status === 'initiated' &&
      data.activation_form_milestone === 'L1' &&
      experiments.canSkipPoiValidation &&
      isInstantActivationEnabled &&
      !experiments.isL2AllowedForPoiInitiated
    ) {
      return (
        <>
          <Info
            title={Messages.POI_VERIFICATION_STATUS.initiated.title}
            description={Messages.POI_VERIFICATION_STATUS.initiated.description}
            titleColor="neutral.960"
            titleJSX={<i className="fa fa-spinner fa-spin spin-big" />}
          />
          <Buttons.LinkButton
            onClick={onCTAClick}
            title="View Submitted Details"
            icon="arrowRight"
          />
        </>
      );
    }
  }

  if (data.activation_form_milestone === 'L1' && isInstantActivationEnabled) {
    const isLimitReached = escalation && escalation?.amount >= escalation?.limit?.payment;
    let greListedDescription = Messages.GREYLIST_STEP.description;
    if (isReferredMerchant) {
      greListedDescription = `${greListedDescription} and unlock ${getFormatedCurrency(
        referee?.referral_amount,
      )} credits`;
    }

    if (!!data.activated || isLimitReached) {
      return (
        <>
          <Info
            title={Messages.PAYMENT_ACTIVATED.title}
            description={
              isLimitReached
                ? Messages.PAYMENT_ACTIVATED.limit_breach_desc
                : Messages.PAYMENT_ACTIVATED.description
            }
          />
          <Buttons.Primary onClick={onCTAClick} title="Complete KYC" icon="arrowRight" />
        </>
      );
    }
    return (
      <>
        <Info title={Messages.GREYLIST_STEP.title} description={greListedDescription} />
        <Buttons.Primary onClick={onCTAClick} title="Submit KYC" icon="arrowRight" />
      </>
    );
  }
  if (!isL1Submitted(data.activation_form_milestone) || !isInstantActivationEnabled) {
    let description = Messages.ACTIVATION_PROGRESS.description;
    if (isReferredMerchant) {
      description = `Complete this step to start transcating and unlock ${getFormatedCurrency(
        referee?.referral_amount,
      )} credits`;
    }
    return (
      <>
        <Info title={Messages.ACTIVATION_PROGRESS.title} description={description} />
        <Buttons.Primary
          onClick={onCTAClick}
          title={isInstantActivationEnabled ? 'Submit KYC' : 'Fill Remaining Details'}
          icon="arrowRight"
        />
      </>
    );
  }

  return null;
};

export default compose<any>(
  withRouter,
  connect(null, {
    showProductModal: showProductsModal,
  }),
)(CurrentActivationProgress);
