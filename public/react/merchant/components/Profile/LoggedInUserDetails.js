import DetailRow from '../DetailRow';
import { titleCase } from 'rzp/utils/rzp-utils';
import { roles } from 'rzp/utils/constants';

export default ({ loggedInUser, loggedInUserRole }) => {
  return (
    <div class="panel panel-default">
      <div class="list-group">
        <DetailRow label="User Name" value={titleCase(loggedInUser.name)} />

        <DetailRow
          label="Login Email"
          value={() => (
            <a href={`mailto:${loggedInUser.email}`}>{loggedInUser.email}</a>
          )}
        />

        <DetailRow label="Role" value={roles[loggedInUserRole].label} />
      </div>
    </div>
  );
};
