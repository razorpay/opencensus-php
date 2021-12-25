import React from 'react';
import styled from 'styled-components';
import { withRouter, RouteComponentProps, Link as Redirect } from 'react-router-dom';
import {
  isUnregisteredBusiness,
  checkIfDedupe,
  isL1Submitted,
  getFormatedCurrency,
} from '../services/utils';
import Link from '@razorpay/commander-shield/src/shared/Link';
import { getMode, switchMode } from 'common/services/mode';
import Info from './Info';
import Buttons from './Buttons';
import * as Messages from './Constants';
import { useApp } from 'common/context/App';
import useTrackEvents from 'merchant/hooks/useTrackEvents';
import { IReferee } from '../Screens/Home';

const InlineText = styled.span`
  color: #162f5661;
`;

const CurrentActivationProgress: React.FC<
  RouteComponentProps & { data: any; escalation: any; referee: IReferee | undefined }
> = ({ data, escalation, history, referee }) => {
  const { user, experiments } = useApp();
  const trackEvents = useTrackEvents();
  const isReferredMerchant = referee?.status === 'signup';
  const activationFormUrl = experiments.isActivationFormFullView ? 'kyc' : 'activation';

  const onCTAClick = () => {
    history.push('/onboarding/steps');
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

  const goToNcFlow = () => {
    history.push(activationFormUrl);
  };

  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const dedupeStatus = checkIfDedupe({ ...data, isInstantActivationEnabled });
  const statusLog = data?.activationStatusChangeLogs || [];

  if (
    dedupeStatus === 'blocked' &&
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
      ['under_review', 'activated_mcc_pending'].includes(data.activation_status)
    ) {
      let title = 'Payments and Settlements have been enabled, Generate TnC';
      let titleColor = 'shade.970';
      let desc = isTestMode
        ? Messages.GENERATE_TNC.mcc_pending.old_description_with_test
        : Messages.GENERATE_TNC.mcc_pending.old_description_with_live;
      let descriptionJSX: React.ReactNode = isTestMode && (
        <Redirect to="/tncform">Generate TnC</Redirect>
      );

      if (data.activation_status === 'under_review') {
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
            <Link
              href="https://knowledgebase.razorpay.com/support/solutions/articles/11000103841-why-is-my-settle[%E2%80%A6]ld-and-my-account-under-review-after-getting-activated"
              target="_blank"
            >
              More details
            </Link>
          }
        />
      );
    }

    if (data.activation_status === 'under_review') {
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

export default withRouter(CurrentActivationProgress);
