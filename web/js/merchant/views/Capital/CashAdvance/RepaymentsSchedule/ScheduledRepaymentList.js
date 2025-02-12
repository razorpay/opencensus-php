import PropTypes from 'prop-types';
import React from 'react';
import moment from 'moment';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import TableBody from 'common/ui/TableBody';
import Amount from 'common/ui/Amount';

const ListItem = ({ repayment }) => {
  const date = repayment.repayment_date * 1000;
  const now = moment();
  const completed = date > now.unix();
  const focus = moment(date).isSame(now, 'day');
  const listItemClass = focus ? 'text-strong' : completed ? 'text-faded' : '';

  const {
    id,
    payment,
    interest,
    principal,
    repayment_date,
    interest_collected,
    principal_collected,
  } = repayment;
  const repaymentDate = moment.unix(repayment_date);

  const amountCollected = Number(interest_collected) + Number(principal_collected);

  return (
    <EntityItemRow id={id}>
      <td className={listItemClass}>
        {repaymentDate.format('Do MMM YYYY')}
        {focus ? '(Today)' : null}
      </td>
      <td className={listItemClass}>
        <Amount value={Number(payment)} />
      </td>
      <td className={listItemClass}>
        <Amount value={Number(principal)} />
      </td>
      <td className={listItemClass}>
        <Amount value={Number(interest)} />
      </td>
      <td className={`${listItemClass} highlight border-left`}>
        {amountCollected ? <Amount value={Number(amountCollected)} /> : '--'}
      </td>
    </EntityItemRow>
  );
};

const ScheduledRepaymentsList = ({ repayments, loading }) => {
  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            <th>Repayment Date</th>
            <th>Daily Installment</th>
            <th>Principal Component</th>
            <th>Interest Component</th>
            <th className="highlight">Amount Collected</th>
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
    </div>
  );
};

ScheduledRepaymentsList.propTypes = {
  repayments: PropTypes.array,
  loading: PropTypes.bool,
};

ScheduledRepaymentsList.defaultProps = {
  repayments: [],
  loading: false,
};

export default ScheduledRepaymentsList;
