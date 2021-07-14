import DetailRow from 'merchant/components/DetailRow';
import { titleCase } from 'common/utils/rzp-utils';
import { roles, agentRole, RBLRoles, RegistrationLinkRoles } from 'merchant/helpers/data';
import AsyncButton from 'react-async-button';

export default ({ loggedInUser, loggedInUserRole, handleUpdateClick, isEmailSelfServeEnabled }) => {
  let ROLES = { ...roles, ...agentRole, ...RBLRoles, ...RegistrationLinkRoles };

  return (
    <div class="panel panel-default">
      <div class="list-group details-row-container">
        <DetailRow label="User Name" value={titleCase(loggedInUser.name)} />

        <DetailRow
          label="Login Email"
          value={() => (
            <span>
              {isEmailSelfServeEnabled && loggedInUserRole === 'owner' ? (
                <AsyncButton
                  type="button"
                  class="Button--secondary Button scheduled-btn-act btn-border"
                  onClick={handleUpdateClick}
                  text="update"
                />
              ) : null}
              {loggedInUser.email}
            </span>
          )}
        />

        <DetailRow label="Role" value={ROLES[loggedInUserRole].label} />
      </div>
    </div>
  );
};
