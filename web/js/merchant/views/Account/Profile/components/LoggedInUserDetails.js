import DetailRow from 'merchant/components/DetailRow';
import { titleCase } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';
import { roles, agentRole, RBLRoles, RegistrationLinkRoles } from 'merchant/helpers/data';
import AsyncButton from 'react-async-button';

export default ({
  isOrgRZP,
  loggedInUser,
  loggedInUserRole,
  handleUpdateClick,
  isEmailSelfServeEnabled,
}) => {
  let ROLES = { ...roles, ...agentRole, ...RBLRoles, ...RegistrationLinkRoles };

  return (
    <div class="panel panel-default">
      <div class="list-group details-row-container">
        <DetailRow label="User Name" value={titleCase(loggedInUser.name)} />

        <DetailRow
          label="Login Email"
          value={() => (
            <span>
              {loggedInUser.email}
              {isOrgRZP && isEmailSelfServeEnabled && loggedInUserRole === 'owner' ? (
                <Button.Transparent onClick={handleUpdateClick}>
                  <i class="i i-edit p-l" />
                </Button.Transparent>
              ) : null}
            </span>
          )}
        />
        {/* Added check to verify if loggedInUserRole is there or if its a valid role (part of role-list) */}
        <DetailRow label="Role" value={ROLES[loggedInUserRole]?.label} />
      </div>
    </div>
  );
};
