import { Link } from 'react-router-dom';

import Alert from 'rzp/ui/Forms/Alert';

export default ({
  completedNachFileURL,
  registrationLinkId,
  preFilledNachFileURL,
}) => {
  if (completedNachFileURL) {
    return (
      <React.Fragment>
        <button
          class="btn btn--primary"
          href={completedNachFileURL}
          target="_blank"
        >
          View Scan
          <i class="i i-file-attach" />
        </button>
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

      <a
        href={preFilledNachFileURL}
        class="btn btn-default m-l"
        target="_blank"
      >
        Download form
      </a>
    </React.Fragment>
  );
};
