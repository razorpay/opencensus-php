import Time from 'rzp/ui/Time';
import TableBody from '../TableBody';

const AccountsListItem = ({ account, canHighlightRow, onEdit }) => {
  return (
    <tr class={canHighlightRow ? 'luminate' : ''}>
      <td>
        <a onClick={onEdit}><code>{`acc_${account.id}`}</code></a>
      </td>
      <td>{account.email}</td>
      <td>{account.name}</td>
      <td>
        <Time value={account.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td>
        <span data-tip={account.activated ? 'Activated' : 'Not Activated'}>
          {account.activated
            ? <i class="fa fa-check text-success" />
            : <i class="fa fa-times text-danger" />}
        </span>
      </td>
      <td>
        <Time value={account.activated_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
    </tr>
  );
};

const AccountsList = ({ accounts, isLoading, highlightRow, onEdit }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Account Id</th>
            <th>Email</th>
            <th>Name</th>
            <th>Created At</th>
            <th>Activated</th>
            <th>Activated At</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={6}
          rows={accounts}
          emptyTableMsg="No Accounts found!"
        >
          {accounts.map(account => (
            <AccountsListItem
              key={account.id}
              account={account}
              canHighlightRow={highlightRow(account)}
              onEdit={() => onEdit(account)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

AccountsList.defaultProps = {
  highlightRow: () => {},
};

export default AccountsList;
