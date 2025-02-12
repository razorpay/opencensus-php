import React from 'react';
import moment from 'moment';
import { Link } from 'react-router-dom';

import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import Pager from 'common/ui/Pager';

import { LOANS_BASE_URL, LOANS_SECTIONS, StatusPillClasses, STATUS_LABELS } from '../../constants';
import { getCollectionMethod } from '../util';

const ListItem = ({ repayment }) => {
  const { id, created_at, amount, status = [] } = repayment;
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
        <span className={`status-pill status-pill-${StatusPillClasses[status]}`}>
          {STATUS_LABELS[status]}
        </span>
      </td>
    </EntityItemRow>
  );
};

export default function RepaymentList({
  repayments,
  isLoading,
  isError,
  paginationConfig,
  onPaginate,
}) {
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
          </tr>
        </thead>
        <TableBody
          colSpan={5}
          isLoading={isLoading}
          rows={repayments}
          emptyTableMsg={
            <div className="d-flex justify-content-center align-items-center no-repayments">
              <p className="no-repayments-text">
                {isError
                  ? 'Something went wrong, Please try again.'
                  : 'There are no repayments yet!'}
              </p>
            </div>
          }
        >
          {repayments.map((repayment) => (
            <ListItem key={repayment.id} repayment={repayment} />
          ))}
        </TableBody>
      </table>
      <div className="loans-repayments-list__table-pager">
        <Pager
          count={paginationConfig.count}
          skip={paginationConfig.skip}
          length={repayments?.length}
          onClick={onPaginate}
        />
      </div>
    </div>
  );
}
