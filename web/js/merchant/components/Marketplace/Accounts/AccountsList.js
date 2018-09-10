import { prefixEntityValue } from 'common/data';

import Time from 'rzp/ui/Time';
import TableBody from 'rzp/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { classList } from 'common/util';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

import SwitchField from 'rzp/ui/Forms/SwitchField';

import store from 'merchant/store';

const AccountsListItem = ({
  account,
  showEditAccountModal,
  onEdit,
  onToggleAccess,
}) => {
  let status = account.activation_details
    ? account.activation_details.status
    : account.activated;
  let timeStamp = account.activation_details
    ? account.activation_details.activated_at
    : account.activated_at;

  const user = store.getState().session.user;

  return (
    <EntityItemRow id={account.id}>
      <td>
        <a onClick={onEdit}>
          <code>{prefixEntityValue('account', account.id)}</code>
        </a>
      </td>
      <td>
        {showEditAccountModal &&
        user.merchants[user.current].email === account.email ? (
          <button
            class="btn btn-link no-padding"
            onClick={() => showEditAccountModal(account)}
          >
            Add Email
          </button>
        ) : (
          <span>
            {showEditAccountModal && (
              <a
                class="p-r"
                onClick={() => showEditAccountModal(account)}
                title="Edit Email"
              >
                <i class="i i-edit" />
              </a>
            )}
            {account.email}
          </span>
        )}
      </td>
      <td>{account.name}</td>
      <td>
        <small class="help-content">
          <span>
            <span
              class={classList(
                'ModeIndicator',
                status == 'activated'
                  ? 'ModeIndicator--live'
                  : 'ModeIndicator--inactive'
              )}
            />
            {status === 'activated' ? 'Activated' : 'Not Activated'}
          </span>
          <Popover align="top" theme="dark">
            <PopoverBody>
              {status === 'activated' ? (
                <div>
                  Activated on{' '}
                  <Time value={timeStamp} format="DD MMM YYYY, hh:mm:A" />
                </div>
              ) : (
                <div>
                  Please fill the Activation form to activate this account.
                  <br />
                  <button className="btn-link pull-right" onClick={onEdit}>
                    Add Details
                  </button>
                </div>
              )}
            </PopoverBody>
          </Popover>
        </small>
      </td>
      <td style={{ textAlign: 'center' }}>
        <SwitchField
          defaultChecked={false}
          onChange={onToggleAccess}
          type="prime"
        />
      </td>
    </EntityItemRow>
  );
};

export default ({
  accounts,
  isLoading,
  showEditAccountModal,
  onEdit,
  onToggleAccess,
}) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover" id="accounts-list">
        <thead>
          <tr>
            <th>Account Id</th>
            <th>Email</th>
            <th>Name</th>
            <th>Account Status</th>
            <th style={{ textAlign: 'center' }}>Dashboard Access</th>
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
              showEditAccountModal={showEditAccountModal}
              onEdit={() => onEdit(account)}
              onToggleAccess={isChecked => onToggleAccess(account, isChecked)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
