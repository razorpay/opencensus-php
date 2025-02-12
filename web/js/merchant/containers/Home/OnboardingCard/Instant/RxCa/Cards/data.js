import moment from 'moment';
import { getTimeDiff } from 'merchant/containers/Home/OnboardingCard/Instant/RxCa/helpers';

export const benefits = [
  'Track and automate all your finances',
  'Transfer money 24*7 even on bank holidays',
  'Priority Support and early access to new features',
  'Chequebook and debit cards',
  'Add beneficiaries instantly',
];

export const currentAccountStatuses = {
  created: 'created',
  picked: 'picked',
  initiated: 'initiated',
  processing: 'processing',
  processed: 'processed',

  verification_call: 'verification_call',
  doc_collection: 'doc_collection',
  account_opening: 'account_opening',
  api_onboarding: 'api_onboarding',
  account_activation: 'account_activation',

  cancelled: 'cancelled',
  activated: 'activated',
  unserviceable: 'unserviceable',
  rejected: 'rejected',
  archived: 'archived',
};

export const currentAccountSubSubStatuses = {
  [currentAccountStatuses.archived]: {
    UNSERVICEABLE_PINCODE: 'unserviceable_pincode',
    NEGATIVE_PROFILE_SVR_ISSUE: 'negative_profile/svr_issue',
    CANCELLED: 'cancelled',
    OTHER: 'other',
  },
};

export const analyticsStatusMap = {
  created: 'request_received',
  picked: 'process_started',
  initiated: 'kyc_in_progress',
  processing: 'kyc_in_progress',
  processed: 'activation_in_progress',

  verification_call: 'kyc_in_progress',
  doc_collection: 'kyc_in_progress',
  account_opening: 'activation_in_progress',
  api_onboarding: 'activation_in_progress',
  account_activation: 'activation_in_progress',

  cancelled: 'request_cancelled',
  activated: 'account_activated',
  unserviceable: 'unserviceable',
  rejected: 'request_rejected',
};

const getTimeLineDates = (activatedAt) => {
  if (activatedAt) {
    return {
      applyCADate: moment.unix(activatedAt).add(15, 'days').format('ll'),
      documentSubmissionDate: moment.unix(activatedAt).add(60, 'days').format('ll'),
    };
  }

  return null;
};

export const getTimeLine = (hasAppliedCa, caStatus, activatedAt) => {
  const timeLineStatus = getTimeLineDates(activatedAt);
  if (!hasAppliedCa) {
    return (
      <div className="ca-apply-timeline">
        {getTimeDiff(activatedAt, 16) <= 0 ? (
          <i className="fa fa-times-circle date-extention-icon" aria-hidden="true" />
        ) : (
          <img src="/img/inactive-circle.svg" alt="Clients" />
        )}
        <div className="active">Apply by {timeLineStatus?.applyCADate}</div>
        <div className="dash-separator">- - - - - - </div>
        <img src="/img/active-circle.svg" alt="Clients" />
        <div className="inactive">Submit documents by {timeLineStatus?.documentSubmissionDate}</div>
      </div>
    );
  } else if (
    hasAppliedCa &&
    (!caStatus ||
      caStatus === currentAccountStatuses.created ||
      caStatus === currentAccountStatuses.picked)
  ) {
    return (
      <div className="ca-apply-timeline">
        <img src="/img/done-circle.svg" alt="Clients" />
        <div className="inactive">Apply by {timeLineStatus?.applyCADate}</div>
        <div className="dash-separator">- - - - - - </div>
        {getTimeDiff(activatedAt, 61) <= 0 ? (
          <i className="fa fa-times-circle date-extention-icon" aria-hidden="true" />
        ) : (
          <img src="/img/inactive-circle.svg" alt="Clients" />
        )}
        <div className="active">Submit documents by {timeLineStatus?.documentSubmissionDate}</div>
      </div>
    );
  } else if (
    hasAppliedCa &&
    [
      currentAccountStatuses.processing,
      currentAccountStatuses.initiated,
      currentAccountStatuses.activated,
      currentAccountStatuses.processed,
      currentAccountStatuses.verification_call,
      currentAccountStatuses.doc_collection,
      currentAccountStatuses.account_opening,
      currentAccountStatuses.api_onboarding,
      currentAccountStatuses.account_activation,
    ].includes(caStatus)
  ) {
    return (
      <div className="ca-apply-timeline">
        <img src="/img/done-circle.svg" alt="Clients" />
        <div className="inactive">Apply by {timeLineStatus?.applyCADate}</div>
        <div className="dash-separator">----------- </div>
        <img src="/img/done-circle.svg" alt="Clients" />
        <div className="inactive">Submit documents by {timeLineStatus?.documentSubmissionDate}</div>
      </div>
    );
  }
  return null;
};
