import DetailRow from '../DetailRow';
import { titleCase } from 'rzp/utils/rzp-utils';
import { roles, agentRole } from 'rzp/utils/constants';

export default ({ loggedInUser, loggedInUserRole }) => {
  let ROLES = { ...roles, ...agentRole };

  return (
    <div class="panel panel-default">
      <div class="list-group details-row-container">
        <DetailRow label="User Name" value={titleCase(loggedInUser.name)} />

        <DetailRow
          label="Login Email"
          value={() => (
            <a href={`mailto:${loggedInUser.email}`}>{loggedInUser.email}</a>
          )}
        />

        <DetailRow label="Role" value={ROLES[loggedInUserRole].label} />
      </div>
    </div>
  );
};
