import { getTimeDiff } from '../helpers';

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
  cancelled: 'cancelled',
  activated: 'activated',
  unserviceable: 'unserviceable',
  rejected: 'rejected',
};

export const analyticsStatusMap = {
  created: 'request_received',
  picked: 'process_started',
  initiated: 'kyc_in_progress',
  processing: 'kyc_in_progress',
  processed: 'activation_in_progress',
  cancelled: 'request_cancelled',
  activated: 'account_activated',
  unserviceable: 'unserviceable',
  rejected: 'request_rejected',
};

export const getTimeLine = (hasAppliedCa, caStatus, activatedAt) => {
  const timeLineStatus = getTimeLineDates(activatedAt);
  if (!hasAppliedCa) {
    return (
      <div className="ca-apply-timeline">
        {getTimeDiff(activatedAt, 16) <= 0 ? (
          <i class="fa fa-times-circle date-extention-icon" aria-hidden="true"></i>
        ) : (
          <img src="/img/inactive-circle.svg" alt="Clients" />
        )}
        <div className="active">Apply by {timeLineStatus.applyCADate}</div>
        <div className="dash-separator">- - - - - - </div>
        <img src="/img/active-circle.svg" alt="Clients" />
        <div className="inactive">Submit documents by {timeLineStatus.documentSubmissionDate}</div>
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
        <div className="inactive">Apply by {timeLineStatus.applyCADate}</div>
        <div className="dash-separator">- - - - - - </div>
        {getTimeDiff(activatedAt, 61) <= 0 ? (
          <i class="fa fa-times-circle date-extention-icon" aria-hidden="true"></i>
        ) : (
          <img src="/img/inactive-circle.svg" alt="Clients" />
        )}
        <div className="active">Submit documents by {timeLineStatus.documentSubmissionDate}</div>
      </div>
    );
  } else if (
    hasAppliedCa &&
    (caStatus === currentAccountStatuses.processing ||
      caStatus === currentAccountStatuses.initiated ||
      caStatus === currentAccountStatuses.activated ||
      caStatus === currentAccountStatuses.processed)
  ) {
    return (
      <div className="ca-apply-timeline">
        <img src="/img/done-circle.svg" alt="Clients" />
        <div className="inactive">Apply by {timeLineStatus.applyCADate}</div>
        <div className="dash-separator">----------- </div>
        <img src="/img/done-circle.svg" alt="Clients" />
        <div className="inactive">Submit documents by {timeLineStatus.documentSubmissionDate}</div>
      </div>
    );
  }
};

const getTimeLineDates = (activatedAt) => {
  if (activatedAt) {
    return {
      applyCADate: moment.unix(activatedAt).add(15, 'days').format('ll'),
      documentSubmissionDate: moment.unix(activatedAt).add(60, 'days').format('ll'),
    };
  }
};
