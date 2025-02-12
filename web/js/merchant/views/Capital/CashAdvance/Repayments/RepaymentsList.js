import React from 'react';
import PropTypes from 'prop-types';
import moment from 'moment';
import { Link } from 'react-router-dom';

import Pager from 'common/ui/Pager';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import { classList } from 'common/utils/rzp-utils';
import {
  STATUS_LABELS,
  StatusPillClasses,
  CASH_ADVANCE_BASE_URL,
  CASH_ADVANCE_SECTIONS,
} from '../constants';
import { computePrincipalAndInterest } from '../utils';

const ListItem = ({ repayment }) => {
  const { id, created_at, amount, status, breakups = [] } = repayment;
  const repaymentDetailsViewLink = `${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.REPAYMENTS}/${id}`;
  const createdAt = created_at ? moment.unix(created_at).format('LLL') : '--';
  const { BALANCE_TYPE_PRINCIPAL = 0, BALANCE_TYPE_INTEREST = 0 } = computePrincipalAndInterest(
    breakups,
  );

  return (
    <EntityItemRow id={id}>
      <td>
        <Link to={repaymentDetailsViewLink}>
          <code>{id}</code>
        </Link>
      </td>
      <td>{createdAt}</td>
      <td>
        <Amount value={Number(amount)} />
      </td>
      <td>
        <Amount value={Number(BALANCE_TYPE_INTEREST)} />
      </td>
      <td>
        <Amount value={Number(BALANCE_TYPE_PRINCIPAL)} />
      </td>
      <td>
        <span className={`status-label label ${StatusPillClasses[status]}`}>
          {STATUS_LABELS[status]}
        </span>
      </td>
    </EntityItemRow>
  );
};

const RepaymentsList = ({ repayments, loading, paginationConfig, onPaginate }) => {
  return (
    <div
      className={classList(
        'table-responsive',
        loading && 'cash-advance-repayments__table--loading',
      )}
    >
      <table className="table table-hover">
        <thead>
          <tr>
            <th>Repayment ID</th>
            <th>Created at</th>
            <th>Repaid Amount</th>
            <th>Interest Repaid</th>
            <th>Principal Repaid</th>
            <th>Repayment Status</th>
          </tr>
        </thead>
        <TableBody
          isLoading={loading}
          colSpan={7}
          rows={repayments}
          emptyTableMsg={
            <div className="no-results-container flex">
              <div className="m-r">
                <img src={require("assets/capital/no_results.svg")} height={240} width={240} />
              </div>
              <div className="content">
                <p className="m-b">
                  <strong>Unlock your Repayments View</strong>
                </p>
                <small className="text-faded">
                  Make your first repayment to unlock the List and details view of Repayments.
                </small>
              </div>
            </div>
          }
        >
          {repayments.map((repayment) => (
            <ListItem key={repayment.id} repayment={repayment} />
          ))}
        </TableBody>
      </table>
      <div className="cash-advance-repayments__table-pager">
        <Pager
          count={paginationConfig.count}
          skip={paginationConfig.skip}
          length={repayments.length}
          onClick={onPaginate}
        />
      </div>
    </div>
  );
};

RepaymentsList.propTypes = {
  repayments: PropTypes.array,
  loading: PropTypes.bool,
};

RepaymentsList.defaultProps = {
  repayments: [],
  loading: false,
};

export default RepaymentsList;
