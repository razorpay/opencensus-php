import React from 'react';
import moment from 'moment';

import DownArrowIcon from 'merchant/views/Capital/components/DownArrowIcon';

import { STATUSES } from './constants';

function Step({ label, active, date, status, icon }) {
  return (
    <li className={`step ${active ? 'active' : ''} ${status}`}>
      <div className="icon-wrapper">{icon}</div>
      <div className="step__summary">
        <p className="status-label">{label}</p>
        {status !== 'not_started' && moment(date).isValid() ? (
          <span className="text--secondary">{moment(date).format('LL')}</span>
        ) : (
          '--'
        )}
        {status !== 'not_started' && moment(date).isValid() && (
          <small className="text-faded">{moment(date).format('hh:mm A')}</small>
        )}
      </div>
    </li>
  );
}

function WithdrawalStatus({ status, withdrawalDetails }) {
  const { drawn_at, created_at, updated_at, processed_at } = withdrawalDetails;

  const getLastRepaidDate = () => {
    if (!withdrawalDetails.repayments) return '--';

    const repaymentDates = withdrawalDetails.repayments
      .reduce((acc, curr) => [...acc, ...(curr?.repayment_breakdowns || curr)], [])
      .map((repayment) => moment(repayment.created_at));
    return moment.max(repaymentDates);
  };

  const getSteps = (status) => {
    const steps = [
      {
        status:
          status === STATUSES.INITIATED || status === STATUSES.CREATED
            ? 'half-complete'
            : 'completed',
        label: 'Requested',
        active: status === STATUSES.INITIATED || status === STATUSES.CREATED,
        date: STATUSES.INITIATED ? created_at : drawn_at,
        icon: <i className="i fa fa-inr" />,
      },
      ...(status === STATUSES.REJECTED || status === STATUSES.FAILED
        ? [
            {
              status: 'failed',
              label: status === STATUSES.FAILED ? 'Failed' : 'Rejected',
              active: true,
              date: updated_at,
              icon: <i class="i i-close text-danger" />,
            },
          ]
        : []),
      {
        status:
          status === STATUSES.REPAID ||
          status === STATUSES.PARTIALLY_REPAID ||
          status === STATUSES.PROCESSED
            ? 'completed'
            : 'not_started',
        label: 'Disbursed',
        active: status === STATUSES.PROCESSED,
        date: processed_at,
        icon: (
          <DownArrowIcon
            color={
              status === STATUSES.PROCESSED ||
              status === STATUSES.REPAID ||
              status === STATUSES.PARTIALLY_REPAID
                ? '#24A831'
                : 'rgba(22, 47, 86, 0.36)'
            }
          />
        ),
      },
      ...(status === STATUSES.PARTIALLY_REPAID
        ? [
            {
              status: '',
              label: 'Partially Repaid',
              active: true,
              date: getLastRepaidDate(),
              icon: (
                <img src={require(`assets/capital/partially_repaid.svg`)} alt="Loading icon" />
              ),
            },
          ]
        : []),
      {
        status: status === STATUSES.REPAID ? 'completed' : 'not_started',
        label: 'Repaid',
        active: status === STATUSES.REPAID,
        date: getLastRepaidDate(),
        icon: <i class="i i-check" />,
      },
    ];

    return steps;
  };

  return (
    <div className="stepper-container">
      <ul className="stepper">
        {getSteps(status).map((step, idx) => (
          <Step key={idx} {...step} />
        ))}
      </ul>
    </div>
  );
}

export default WithdrawalStatus;
