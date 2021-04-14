import React from 'react';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import { isUnregisteredBusiness, checkIfDedupe } from '../services/utils';
import RemainingStepsInfo from './RemainingStepsInfo';
import { getMode, switchMode } from 'v2/services/mode';
import Info from './Info';
import Buttons from './Buttons';
import * as Messages from './Constants';
import { analyticsTrack } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

const CurrentActivationProgress: React.FC<RouteComponentProps & { data: any; payments: any }> = ({
  data,
  payments,
  history,
}) => {
  const { user } = useApp();
  const onCTAClick = () => {
    history.push('/onboarding/steps');
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'form fill',
      screen: 'home page',
      eventAction: 'initiated',
      user,
    });
  };

  const isTestMode = getMode(user.current) === 'test';

  const contactSupport = () => {
    window.rzpTicketSystem.openModal('#ticket');
  };

  const goToNcFlow = () => {
    history.push('activation');
  };

  const isDedupeState = checkIfDedupe(data);

  if (data.submitted) {
    if (isDedupeState) {
      return (
        <>
          <Info
            title={Messages.DEDUPE.title}
            description={Messages.DEDUPE.description}
            titleColor="negative.900"
            hasError
          />
          <Buttons.Primary onClick={() => contactSupport()} title="Contact support" />
        </>
      );
    }
    if (data.activation_status === 'under_review') {
      let description = '';
      if (isUnregisteredBusiness(data.business_type)) {
        description = `This process usually takes ${
          data.isAutoKycDone ? ' 3 - 5 ' : ' 8 - 10 '
        } working days after your first transaction. If we need any more information we will reach out to you on your registered email id.`;
      } else if (!data.isAutoKycDone) {
        description =
          'Your documents are under review. It generally takes around 8 - 10 working days. Our team will reach out to you in case of any clarification';
      } else {
        description = Messages.ACTIVATION_STATUS_UNDER_REVIEW.registered.description;
      }
      return (
        <>
          <Info
            title={Messages.ACTIVATION_STATUS_UNDER_REVIEW.registered.title}
            titleColor="neutral.960"
            description={description}
          />
          <Buttons.LinkButton
            onClick={onCTAClick}
            title="View Submitted Details"
            icon="arrowRight"
          />
        </>
      );
    }

    if (data.activation_status === 'needs_clarification') {
      return (
        <>
          <Info
            title={Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.title}
            description={Messages.ACTIVATION_STATUS_NEEDS_CLARIFICATION.description}
            titleColor="negative.900"
            hasError
          />
          <Buttons.Primary onClick={() => goToNcFlow()} title="Clarify Details" />
        </>
      );
    }

    if (data.activation_status === 'activated') {
      return (
        <>
          <Info
            title={Messages.ACTIVATION_STATUS_ACTIVATED.title}
            description={
              isTestMode
                ? Messages.ACTIVATION_STATUS_ACTIVATED.description
                : Messages.ACTIVATION_STATUS_ACTIVATED.description_live_mode
            }
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
      return (
        <>
          <Info
            title={Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.title}
            description={Messages.ACTIVATION_STATUS_ACTIVATED_MCC_PENDING.description}
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
  } else {
    if (
      data.poi_verification_status === 'incorrect_details' ||
      data.poi_verification_status === 'not_matched'
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

    if (data.poi_verification_status === 'failed') {
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

    if (data.poi_verification_status === 'pending') {
      return (
        <Info
          title={Messages.POI_VERIFICATION_STATUS.pending.title}
          description={Messages.POI_VERIFICATION_STATUS.pending.description}
          titleColor="neutral.960"
        />
      );
    }
  }

  if (
    data.onboarding_milestone === 'activation_flow' ||
    data.onboarding_milestone === 'L1' ||
    data.onboarding_milestone === 'L2'
  ) {
    return <RemainingStepsInfo data={data} payments={payments} />;
  }

  if (data.activation_progress >= 24) {
    return (
      <>
        <Info
          title={Messages.ACTIVATION_PROGRESS.title}
          description={Messages.ACTIVATION_PROGRESS.description}
        />
        <Buttons.Primary onClick={onCTAClick} title="Fill Remaining Details" icon="arrowRight" />
      </>
    );
  }

  return null;
};

export default withRouter(CurrentActivationProgress);
