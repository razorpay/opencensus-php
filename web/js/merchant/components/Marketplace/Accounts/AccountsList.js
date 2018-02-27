import { prefixEntityValue } from 'common/data';

import Time from 'rzp/ui/Time';
import CheckIcon from 'rzp/ui/CheckIcon';
import TableBody from 'rzp/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const AccountsListItem = ({ account, onEdit }) => {
  let status = account.activation_details
    ? account.activation_details.status
    : account.activated;
  let timeStamp = account.activation_details
    ? account.activation_details.activated_at
    : account.activated_at;

  return (
    <EntityItemRow id={account.id}>
      <td>
        <a onClick={onEdit}>
          <code>{prefixEntityValue('account', account.id)}</code>
        </a>
      </td>
      <td>{account.email}</td>
      <td>{account.name}</td>
      <td>
        <Time value={account.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td>
        <span data-tip={status == 'activated' ? 'Activated' : 'Not Activated'}>
          <CheckIcon value={status == 'activated'} />
        </span>
      </td>
      <td>
        <Time value={timeStamp} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
    </EntityItemRow>
  );
};

export default ({ accounts, isLoading, onEdit }) => {
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
              onEdit={() => onEdit(account)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
