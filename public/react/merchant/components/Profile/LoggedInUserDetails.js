import DetailRow from '../DetailRow';
import { titleCase } from 'rzp/utils/rzp-utils';

export default ({ loggedInUser }) => {
  return (
    <div class="panel-detail-container">
      <div class="panel panel-default">
        <div class="list-group">
          <DetailRow label="User Name" value={titleCase(loggedInUser.name)} />

          <DetailRow
            label="Login Email"
            value={() => (
              <a href={`mailto:${loggedInUser.email}`}>{loggedInUser.email}</a>
            )}
          />

          <DetailRow label="Role" value={'admin'} />
          {' '}
          {/*TODO: angular, roletoname*/}
        </div>
      </div>
    </div>
  );
};
