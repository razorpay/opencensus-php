import { Link } from 'react-router-dom';

import Alert from 'rzp/ui/Forms/Alert';

export default ({ registrationLinkId, fileURL, status }) => (
  <React.Fragment>
    <Alert type={status.type} message={status.message} />

    <Link
      class="btn btn-primary"
      to={`/registration_links/${registrationLinkId}/upload_nach`}
    >
      Upload form
    </Link>
    <a href={fileURL} class="btn btn-default m-l">
      Download form
    </a>
  </React.Fragment>
);
