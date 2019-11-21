import { Link } from 'react-router-dom';

import Alert from 'rzp/ui/Forms/Alert';
import AsyncButton from 'react-async-button';

export default ({
  downloadSignedNACHFile,
  registrationLinkId,
  preFilledNachFileURL,
  trackClickViewNACHForm,
  trackClickUploadNACHForm,
  trackClickDownloadNACHForm,
}) => {
  if (downloadSignedNACHFile) {
    return (
      <React.Fragment>
        <AsyncButton
          class="btn btn-link nach-download-btn"
          onClick={() => {
            trackClickViewNACHForm();
            return downloadSignedNACHFile();
          }}
          target="_blank"
        >
          <i class="i i-file-attach" />
          View Signed NACH Form
        </AsyncButton>
      </React.Fragment>
    );
  }

  return (
    <React.Fragment>
      <Alert
        type="warning"
        message="Customer’s NACH form has not been uploaded yet"
        showDismiss={false}
      />

      <Link
        class="btn btn-primary"
        to={`/registration_links/${registrationLinkId}/upload_nach`}
        onClick={trackClickUploadNACHForm}
      >
        Upload form
      </Link>

      <a
        href={preFilledNachFileURL}
        class="btn btn-default m-l"
        target="_blank"
        onClick={trackClickDownloadNACHForm}
      >
        Download form
      </a>
    </React.Fragment>
  );
};
