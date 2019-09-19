import { Link } from 'react-router-dom';

import Alert from 'rzp/ui/Forms/Alert';

export default ({ nachForm, registrationLinkId, fileURL }) => {
  if (nachForm) {
    return (
      <React.Fragment>
        <a href={nachForm}>
          <i class="i i-file-attach" />
          View Scan
        </a>
      </React.Fragment>
    );
  }

  return (
    <React.Fragment>
      <Alert
        type="info"
        message="Customer’s NACH form has not been uploaded yet"
        showDismiss={false}
      />

      <Link
        class="btn btn-primary"
        to={`/registration_links/${registrationLinkId}/upload_nach`}
      >
        Upload form
      </Link>

      <a href={fileURL} class="btn btn-default m-l" target="_blank">
        Download form
      </a>
    </React.Fragment>
  );
};
