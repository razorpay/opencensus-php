import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import { STATUS_LABELS, STATUSES, StatusPillClasses } from './constants';
import { Link, withRouter } from 'react-router-dom';
import { AsyncBtn } from 'common/new-ui/Button';

const ListItem = ({ withdrawal, onEdit, repay, history, trackGA }) => {
  return (
    <EntityItemRow id={withdrawal.id} onClick={onEdit}>
      <td>
        <Link to={`/capital/cash-advance/withdrawals/${withdrawal.id}`}>
          <code>{withdrawal.id}</code>
        </Link>
      </td>
      <td>
        <Amount value={withdrawal.amount} />
      </td>
      <td>
        <span
          class={`status-label label ${StatusPillClasses[withdrawal.status]}`}
        >
          {STATUS_LABELS[withdrawal.status]}
        </span>
      </td>
      {/*tODO convert from utc*/}
      <td>
        {withdrawal.processed_at && moment(withdrawal.processed_at).isValid()
          ? moment(withdrawal.processed_at).format('LLL')
          : '--'}
      </td>
      <td>{moment(withdrawal.due_date).format('LL')}</td>
      <td class="row-action">
        <div className="btn-group">
          {withdrawal.status === STATUSES.PROCESSED ||
          withdrawal.status === STATUSES.PARTIALLY_REPAID ? (
            <AsyncBtn.Primary
              class="btn btn-primary btn-xs btn-outline Button--small"
              onClick={() => repay(withdrawal)}
            >
              Repay
            </AsyncBtn.Primary>
          ) : (
            <button
              className="btn btn-xs btn-default"
              onClick={() => {
                trackGA({
                  eventAction: 'List View | Specific Withdrawal',
                });
                history.push(
                  `/capital/cash-advance/withdrawals/${withdrawal.id}`
                );
              }}
            >
              <span>view</span>
            </button>
          )}
        </div>
      </td>
    </EntityItemRow>
  );
};

const ListItemWrapper = withRouter(ListItem);

export default ({ withdrawals, loading, viewWithdrawal, repay, trackGA }) => {
  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            <th>Withdrawal ID</th>
            <th>Withdrawal Amount</th>
            <th>Withdrawal Status</th>
            <th>Disbursed At</th>
            <th>Repayment At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <TableBody
          isLoading={loading}
          colSpan={5}
          rows={withdrawals}
          emptyTableMsg={
            <div class="no-results-container flex">
              <div class="m-r">
                <img
                  src="/dist/css/assets/capital/no_results.svg"
                  height={240}
                  width={240}
                />
              </div>
              <div class="content">
                <p class="m-b">
                  <strong>Unlock your Withdrawals View</strong>
                </p>
                <small class="text-faded">
                  Make your first withdrawal to unlock the List and details view
                  of Withdrawals.
                </small>
              </div>
            </div>
          }
        >
          {withdrawals.map(withdrawal => (
            <ListItemWrapper
              key={withdrawal.id}
              withdrawal={withdrawal}
              viewWithdrawal={viewWithdrawal}
              repay={repay}
              trackGA={trackGA}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
