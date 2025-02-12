import React, { useState } from 'react';
import moment from 'moment';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import TableBody from 'common/ui/TableBody';
import Amount from 'common/ui/Amount';

import { StatusPillClasses, STATUS_LABELS } from '../../constants';
import { getCollectionMethod } from '../util';

const ListItem = ({ repayment }) => {
  const { id, created_at, amount, status = [] } = repayment;
  const createdAt = created_at ? moment.unix(created_at).format('D MMM, h:mm a') : '--';
  const collectionMethod = getCollectionMethod(repayment);

  return (
    <EntityItemRow id={id}>
      <td className="text-xsm">{createdAt}</td>
      <td className="text-xsm collection-method">{collectionMethod}</td>
      <td className="text-xsm amount text-right">
        <Amount value={Number(amount)} />
      </td>
      <td className="text-xsm text-center flex justify-center">
        <span className={`status-pill status-pill-${StatusPillClasses[status]}`}>
          {STATUS_LABELS[status]}
        </span>
      </td>
    </EntityItemRow>
  );
};

export default function RecentRepayments({ repayments, loanAmount }) {
  const [displayTable, toggleDisplayTable] = useState(false);
  return (
    <div className="card recent-repayments">
      <div className="recent-payment-heading" onClick={() => toggleDisplayTable(!displayTable)}>
        <span className="font-bold">Recent Repayments</span>
        <i className={`i i-chevron-up ${displayTable ? '' : 'fa-rotate-180'}`} />
      </div>
      <div className={`table-section ${displayTable ? 'add-margin' : 'sub-margin'}`}>
        <div
          className={`table-responsive loans-repayments-list ${
            displayTable ? 'show-table' : 'hide-table'
          }`}
        >
          <table className="table table-hover">
            <thead>
              <tr className="text-sm">
                <th>Collected On</th>
                <th>Collected Method</th>
                <th className="text-right">Amount Collected</th>
                <th className="text-center">Repayment Status</th>
              </tr>
            </thead>
            <TableBody
              colSpan={7}
              rows={repayments}
              emptyTableMsg={
                <div className="d-flex justify-content-center align-items-center no-repayments">
                  <p className="no-repayments-text text-sm">There are no repayments yet!</p>
                </div>
              }
            >
              {repayments.map((repayment) => (
                <ListItem key={repayment.id} repayment={repayment} loanAmount={loanAmount} />
              ))}
            </TableBody>
          </table>
        </div>
      </div>
    </div>
  );
}
