import DetailRow from 'merchant/components/DetailRow';
import { titleCase } from 'common/utils/rzp-utils';
import { roles, agentRole, RBLRoles, AuthLinkRoles } from 'merchant/helpers/data';

export default ({ loggedInUser, loggedInUserRole }) => {
  let ROLES = { ...roles, ...agentRole, ...RBLRoles, ...AuthLinkRoles };

  return (
    <div class="panel panel-default">
      <div class="list-group details-row-container">
        <DetailRow label="User Name" value={titleCase(loggedInUser.name)} />

        <DetailRow label="Login Email" value={loggedInUser.email} />

        <DetailRow label="Role" value={ROLES[loggedInUserRole].label} />
      </div>
    </div>
  );
};
