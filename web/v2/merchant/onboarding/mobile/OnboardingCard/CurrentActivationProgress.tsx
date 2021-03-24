import React from 'react';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import { isUnregisteredBusiness } from '../services/utils';
import RemainingStepsInfo from './RemainingStepsInfo';
import Info from './Info';
import Buttons from './Buttons';
import * as Messages from './Constants';

const CurrentActivationProgress: React.FC<RouteComponentProps & { data: any; payments: any }> = ({
  data,
  payments,
  history,
}) => {
  const onCTAClick = () => {
    history.push('/onboarding/steps');
  };
  if (data.onboarding_milestone === 'activation_flow') {
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

  if (data.submitted) {
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

    if (data.activation_status === 'under_review') {
      let description = '';
      if (isUnregisteredBusiness(data.business_type)) {
        description =
          'This  process usually takes 1-2 working days after your first transaction. If we need any more information we will reach out to you on your registered email id.';
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
          <Buttons.LinkButton
            onClick={onCTAClick}
            title="View Submitted Details"
            icon="arrowRight"
          />
        </>
      );
    }

    if (
      data.activation_status === 'activated' ||
      data.activation_status === 'activated_mcc_pending'
    ) {
      return (
        <Info
          title={Messages.ACTIVATION_STATUS_ACTIVATED.title}
          description={Messages.ACTIVATION_STATUS_ACTIVATED.description}
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

  return null;
};

export default withRouter(CurrentActivationProgress);
