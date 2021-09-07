import React, { useMemo } from 'react';
import moment from 'moment';
import { Link } from 'react-router-dom';

import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';

import { LOANS_BASE_URL, LOANS_SECTIONS, StatusPillClasses, STATUS_LABELS } from '../../constants';
import { getCollectionMethod, isRepaymentSuccess } from '../util';

const ListItem = ({ repayment }) => {
  const { id, created_at, amount, outstandingBalance, status = [] } = repayment;
  const repaymentDetailsViewLink = `${LOANS_BASE_URL}${LOANS_SECTIONS.REPAYMENTS_HISTORY}/${id}`;
  const createdAt = created_at ? moment.unix(created_at).format('D MMM') : '--';
  const collectionMethod = getCollectionMethod(repayment);

  return (
    <EntityItemRow id={id}>
      <td className="text-xsm">
        <Link to={repaymentDetailsViewLink}>
          <code>{id}</code>
        </Link>
      </td>
      <td className="text-xsm">{createdAt}</td>
      <td className="collection-method text-xsm">{collectionMethod}</td>
      <td className="amount text-xsm text-right">
        <Amount value={Number(amount)} />
      </td>
      <td className="text-xs text-center flex justify-center">
        <span class={`status-pill status-pill-${StatusPillClasses[status]}`}>
          {STATUS_LABELS[status]}
        </span>
      </td>
      <td className="text-xsm text-right">
        <Amount value={Number(outstandingBalance)} />
      </td>
    </EntityItemRow>
  );
};

export default function RepaymentList({ repayments, loanAmount }) {
  return (
    <div className="table-responsive loans-repayments-list">
      <table className="table table-hover">
        <thead>
          <tr className="text-sm">
            <th>Repayment ID</th>
            <th>Collected On</th>
            <th>Collected Method</th>
            <th className="text-right">Amount Collected</th>
            <th className="text-center">Repayment Status</th>
            <th className="text-right">Outstanding Balance</th>
          </tr>
        </thead>
        <TableBody
          colSpan={7}
          rows={repayments}
          emptyTableMsg={
            <div className="d-flex justify-content-center align-items-center no-repayments">
              <p className="no-repayments-text">There are no repayments yet!</p>
            </div>
          }
        >
          {repayments.map((repayment) => (
            <ListItem key={repayment.id} repayment={repayment} loanAmount={loanAmount} />
          ))}
        </TableBody>
      </table>
    </div>
  );
}
