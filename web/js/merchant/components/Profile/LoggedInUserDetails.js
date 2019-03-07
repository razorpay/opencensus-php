import DetailRow from '../DetailRow';
import { titleCase } from 'rzp/utils/rzp-utils';
import { roles, agentRole, RBLRoles } from 'rzp/utils/constants';

export default ({ loggedInUser, loggedInUserRole }) => {
  let ROLES = { ...roles, ...agentRole, ...RBLRoles };

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
