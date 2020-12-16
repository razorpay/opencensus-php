import { Link, NavLink } from 'react-router-dom';

import { prefixEntityValue } from 'merchant_common/helpers/data';

import Time from 'common/ui/Time';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { classList } from 'common/utils/rzp-utils';
import Popover, { PopoverBody } from 'common/ui/Popover';

import SwitchField from 'common/ui/Forms/SwitchField';

import { getUser } from 'merchant/store';

export const ToggleField = ({ children, onEdit, isDisabled, isDashboard }) => {
  if (isDisabled) {
    return (
      <small class="help-content">
        {children}
        <Popover align="top" theme="dark">
          <PopoverBody>
            <div>
              {isDashboard
                ? 'Please add Email id for this linked account to grant dashboard access'
                : 'Please add Email id for this linked account to allow refunds'}
              <br />
              <button class="btn-link pull-right" onClick={onEdit}>
                Add Email
              </button>
            </div>
          </PopoverBody>
        </Popover>
      </small>
    );
  }

  return children;
};

const AccountsListItem = ({
  account,
  showEditAccountModal,
  onEdit,
  onToggleDashboardAccess,
  onToggleAllowRefunds,
  isRouteCodeSupportEnabled,
  isDirectTransferEnabled,
}) => {
  let status = account.activation_details ? account.activation_details.status : account.activated;
  let timeStamp = account.activation_details
    ? account.activation_details.activated_at
    : account.activated_at;

  const user = getUser();
  const noLAEmail = user.merchants[user.current].email === account.email;

  return (
    <EntityItemRow id={account.id}>
      <td>
        <Link to={`/route/accounts/${account.id}`}>
          <code>{prefixEntityValue('account', account.id)}</code>
        </Link>
      </td>
      <td>
        {showEditAccountModal && noLAEmail ? (
          <button class="btn btn-link no-padding" onClick={() => showEditAccountModal(account)}>
            Add Email
          </button>
        ) : (
          <span>{account.email}</span>
        )}
      </td>
      <td>{account.name}</td>
      {isRouteCodeSupportEnabled && <td>{account.code || '-'}</td>}
      <td>
        <small class="help-content">
          <span>
            <span
              class={classList(
                'ModeIndicator',
                status == 'activated' ? 'ModeIndicator--live' : 'ModeIndicator--inactive',
              )}
            />
            {status === 'activated' ? 'Activated' : 'Not Activated'}
          </span>
          <Popover align="top" theme="dark">
            <PopoverBody>
              {status === 'activated' ? (
                <div>
                  Activated on <Time value={timeStamp} format="DD MMM YYYY, hh:mm:A" />
                </div>
              ) : (
                <div>
                  Please fill the Activation form to activate this account.
                  <br />
                  <button class="btn-link pull-right" onClick={onEdit}>
                    Add Details
                  </button>
                </div>
              )}
            </PopoverBody>
          </Popover>
        </small>
      </td>
      {onToggleDashboardAccess && (
        <td style={{ textAlign: 'center' }}>
          {
            <ToggleField
              onEdit={() => showEditAccountModal(account)}
              isDisabled={noLAEmail}
              isDashboard
            >
              <SwitchField
                checked={!!account.dashboard_access}
                onChange={onToggleDashboardAccess}
                disabled={noLAEmail}
                type="prime"
              />
            </ToggleField>
          }
        </td>
      )}
      {onToggleAllowRefunds && (
        <td style={{ textAlign: 'center' }}>
          {
            <ToggleField onEdit={() => showEditAccountModal(account)} isDisabled={noLAEmail}>
              <SwitchField
                checked={!!account.allow_reversals}
                onChange={onToggleAllowRefunds}
                disabled={noLAEmail}
                type="prime"
              />
            </ToggleField>
          }
        </td>
      )}

      {isDirectTransferEnabled && (
        <td style={{ textAlign: 'center' }}>
          <NavLink class="btn btn-default btn-xs" to="/route/transfers/direct_transfer">
            <i class="i i-plus" />
            Create Direct Transfer
          </NavLink>
        </td>
      )}
    </EntityItemRow>
  );
};

export default function AccountsList({
  accounts,
  isLoading,
  showEditAccountModal,
  onEdit,
  onToggleDashboardAccess,
  onToggleAllowRefunds,
  isRouteCodeSupportEnabled,
  isDirectTransferEnabled,
}) {
  return (
    <div class="table-responsive">
      <table class="table table-hover" id="accounts-list">
        <thead>
          <tr>
            <th>Account Id</th>
            <th>Email</th>
            <th>Name</th>
            {isRouteCodeSupportEnabled && <th>Account Code</th>}
            <th>Account Status</th>
            {onToggleDashboardAccess && <th style={{ textAlign: 'center' }}>Dashboard Access</th>}
            {onToggleAllowRefunds && (
              <th style={{ textAlign: 'center' }}>
                Allow Refunds
                <small class="help-content" style={{ paddingLeft: '4px' }}>
                  <i class="i i-help" />
                  <Popover align="right" theme="dark">
                    <PopoverBody>
                      <div style={{ textAlign: 'left' }}>
                        This allows Linked account to refund to the customer for a transfer.
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </th>
            )}
            {isDirectTransferEnabled && <th> </th>}
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={6}
          rows={accounts}
          emptyTableMsg="No Accounts found!"
        >
          {accounts.map((account) => (
            <AccountsListItem
              key={account.id}
              isDirectTransferEnabled={isDirectTransferEnabled}
              account={account}
              isRouteCodeSupportEnabled={isRouteCodeSupportEnabled}
              showEditAccountModal={showEditAccountModal}
              onEdit={() => onEdit(account)}
              onToggleDashboardAccess={
                onToggleDashboardAccess
                  ? (isChecked, cb) => onToggleDashboardAccess(account, cb)
                  : undefined
              }
              onToggleAllowRefunds={
                onToggleAllowRefunds
                  ? (isChecked, cb) => onToggleAllowRefunds(account, cb)
                  : undefined
              }
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
}
