import moment from 'moment';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import { STATUS_LABELS, StatusPillClasses } from './constants';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';

const ListItem = ({ withdrawal, onEdit, history, trackGA }) => {
  const disbursedAtDate = withdrawal.disbursed_at || withdrawal.processed_at;

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
        <span class={`status-label label ${StatusPillClasses[withdrawal.status]}`}>
          {STATUS_LABELS[withdrawal.status]}
        </span>
      </td>
      {/*tODO convert from utc*/}
      <td>
        {disbursedAtDate && moment(disbursedAtDate).isValid()
          ? moment(disbursedAtDate).format('LLL')
          : '--'}
      </td>
      <td>{withdrawal.due_date ? moment(withdrawal.due_date).format('LL') : '--'}</td>
      <td class="row-action">
        <div className="btn-group">
          <button
            className="btn btn-xs btn-default"
            onClick={() => {
              trackGA({
                eventAction: 'List View | Specific Withdrawal',
              });
              history.push(`/capital/cash-advance/withdrawals/${withdrawal.id}`);
            }}
          >
            <span>view</span>
          </button>
        </div>
      </td>
    </EntityItemRow>
  );
};

const ListItemWrapper = withRouter(ListItem);

export default ({ withdrawals, loading, trackGA }) => {
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
                <img src={require("assets/capital/no_results.svg")} height={240} width={240} />
              </div>
              <div class="content">
                <p class="m-b">
                  <strong>Unlock your Withdrawals View</strong>
                </p>
                <small class="text-faded">
                  Make your first withdrawal to unlock the List and details view of Withdrawals.
                </small>
              </div>
            </div>
          }
        >
          {withdrawals.map((withdrawal) => (
            <ListItemWrapper key={withdrawal.id} withdrawal={withdrawal} trackGA={trackGA} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
