import { Link } from 'react-router-dom';

import Alert from 'common/ui/Forms/Alert';
import AsyncButton from 'react-async-button';

export default function UploadNachFormDetails({
  downloadSignedNACHFile,
  registrationLinkId,
  preFilledNachFileURL,
  trackClickViewNACHForm,
  trackClickUploadNACHForm,
  trackClickDownloadNACHForm,
}) {
  if (downloadSignedNACHFile) {
    return (
      <AsyncButton
        className="btn btn-link nach-download-btn"
        onClick={() => {
          trackClickViewNACHForm();
          return downloadSignedNACHFile();
        }}
        target="_blank"
        rel="noreferrer noopener"
      >
        <i className="i i-file-attach" /> View Signed NACH Form
      </AsyncButton>
    );
  }

  return (
    <>
      <Alert
        type="warning"
        message="Customer’s NACH form has not been uploaded yet"
        showDismiss={false}
      />

      <Link
        className="btn btn-primary"
        to={`/registration_links/${registrationLinkId}/upload_nach`}
        onClick={trackClickUploadNACHForm}
      >
        Upload form
      </Link>

      <a
        className="btn btn-default m-l"
        onClick={() => {
          window.location = preFilledNachFileURL;

          trackClickDownloadNACHForm();
        }}
      >
        Download form
      </a>
    </>
  );
}
