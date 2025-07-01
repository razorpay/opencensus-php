import { Link } from 'react-router-dom';

import { prefixEntityValue } from 'merchant_common/helpers/data';

import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Popover, { PopoverBody } from 'common/ui/Popover';

import SwitchField from 'common/ui/Forms/SwitchField';

import { getUser } from 'merchant/store';
import { AccountStatusListView as AccountStatusLabel } from './AccountStatusLabel';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

export const ToggleField = ({
  onEdit,
  isLAEmailAbsent,
  isDashboard,
  isLACreationDisabled,
  onChange,
  checked,
  isDisabled = false,
}) => {
  const isSwitchDisabled =
    (isLACreationDisabled && isLAEmailAbsent) || isLAEmailAbsent || isDisabled;

  const _SwitchField = (
    <SwitchField checked={checked} onChange={onChange} disabled={isSwitchDisabled} type="prime" />
  );

  if (isLACreationDisabled && isLAEmailAbsent) {
    return (
      <small className="help-content">
        {_SwitchField}
        <Popover align="top" theme="dark">
          <PopoverBody>This action is not allowed for your business type</PopoverBody>
        </Popover>
      </small>
    );
  }

  if (isLAEmailAbsent) {
    return (
      <small className="help-content">
        {_SwitchField}
        <Popover align="top" theme="dark">
          <PopoverBody>
            <div>
              {isDashboard
                ? 'Please add Email id for this linked account to grant dashboard access'
                : 'Please add Email id for this linked account to allow refunds'}
              <br />
              <button className="btn-link pull-right" onClick={onEdit}>
                Add Email
              </button>
            </div>
          </PopoverBody>
        </Popover>
      </small>
    );
  }

  return _SwitchField;
};

const AccountsListItem = ({
  account,
  showEditAccountModal,
  onEdit,
  onToggleDashboardAccess,
  onToggleAllowRefunds,
  isRouteCodeSupportEnabled,
  isCreationDisabled,
}) => {
  const status = account.activation_details ? account.activation_details.status : account.activated;
  const timeStamp = account.activation_details
    ? account.activation_details.activated_at
    : account.activated_at;

  const user = getUser();
  const isRouteDSEnabled = user.isRouteDSEnabled;
  const noLAEmail = user.merchants[user.current].email === account.email;
  const isDisabled = isCreationDisabled || isRouteDSEnabled;

  return (
    <EntityItemRow id={account.id}>
      <td>
        <Link to={`/route/accounts/${account.id}`}>
          <code>{prefixEntityValue('account', account.id)}</code>
        </Link>
      </td>
      <td>
        {showEditAccountModal && noLAEmail ? (
          <span>
            {isDisabled && (
              <Popover align="top" theme="dark">
                <PopoverBody>This action is not allowed for your business type</PopoverBody>
              </Popover>
            )}
            <button
              className="btn btn-link no-padding"
              onClick={() => {
                selfServeTrackInitiate({
                  selfServeAction: 'Email added',
                  page: 'Account',
                  screen: 'Route',
                });
                showEditAccountModal(account);
              }}
              disabled={isDisabled}
            >
              Add Email
            </button>
          </span>
        ) : (
          <span>{account.email}</span>
        )}
      </td>
      <td>{account.name}</td>
      {isRouteCodeSupportEnabled && <td>{account.code || '-'}</td>}
      <td>
        <AccountStatusLabel
          activationStatus={status}
          timeStamp={timeStamp}
          showActivationForm={onEdit}
          errorDetails={account.activation_details?.bank_details_verification_error}
          isCreationDisabled={isDisabled}
        />
      </td>
      {onToggleDashboardAccess && !user.isOrgCurlec && (
        <td style={{ textAlign: 'center' }}>
          <ToggleField
            onEdit={() => showEditAccountModal(account)}
            isLAEmailAbsent={noLAEmail}
            isDashboard
            isLACreationDisabled={isCreationDisabled}
            onChange={onToggleDashboardAccess}
            checked={!!account.dashboard_access}
            isDisabled={isRouteDSEnabled}
          />
        </td>
      )}
      {onToggleAllowRefunds && !user.isOrgCurlec && (
        <td style={{ textAlign: 'center' }}>
          {
            <ToggleField
              onEdit={() => showEditAccountModal(account)}
              isLAEmailAbsent={noLAEmail}
              isLACreationDisabled={isCreationDisabled}
              checked={!!account.allow_reversals}
              onChange={onToggleAllowRefunds}
              isDisabled={isRouteDSEnabled}
            />
          }
        </td>
      )}

      {/* TODO: Enable this once prefill account id add to direct transfers from
      {isDirectTransferEnabled && (
        <td style={{ textAlign: 'center' }}>
          <NavLink className="btn btn-default btn-xs" to="/route/transfers/direct_transfer">
            <i className="i i-plus" />
            Create Direct Transfer
          </NavLink>
        </td>
      )} */}
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
  isCreationDisabled,
}) {
  const user = getUser();
  return (
    <div className="table-responsive">
      <table className="table table-hover" id="accounts-list">
        <thead>
          <tr>
            <th>Account Id</th>
            <th>Email</th>
            <th>Name</th>
            {isRouteCodeSupportEnabled && <th>Account Code</th>}
            <th>Account Status</th>
            {onToggleDashboardAccess && !user.isOrgCurlec && (
              <th style={{ textAlign: 'center' }}>Dashboard Access</th>
            )}
            {onToggleAllowRefunds && !user.isOrgCurlec && (
              <th style={{ textAlign: 'center' }}>
                Allow Refunds
                <small className="help-content" style={{ paddingLeft: '4px' }}>
                  <i className="i i-help" />
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
            {/* TODO: Enable this once prefill account id add to direct transfers from
            {isDirectTransferEnabled && <th> </th>} */}
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
              isCreationDisabled={isCreationDisabled}
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
