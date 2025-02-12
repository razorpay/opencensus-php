import { AsyncBtn } from 'common/new-ui/Button';

export default function LogStatus(props) {
  return (
    <div className="LogStatus">
      <strong data-testid="log-status-text">{renderActionBasedOnStatus(props)}</strong>
    </div>
  );
}

function renderActionBasedOnStatus({ actualStatus, ...props }) {
  switch (actualStatus) {
    case 'in-process':
      return <p>Generating...</p>;
    case 'no-data':
      return <NoDataError />;
    case 'ready-for-download':
      return <DownloadButton {...props} />;
    case 'error':
      return <p>Failed</p>;
    default:
      return null;
  }
}

function DownloadButton(props) {
  return (
    <AsyncBtn.Transparent
      onClick={props.onDownloadClick}
      pendingState="Downloading"
      type="button"
      className="Btn--link"
      data-file-id={props.fileId}
      data-consumer-id={props.consumerId}
    >
      Download
    </AsyncBtn.Transparent>
  );
}

function NoDataError() {
  return <p>No data available</p>;
}
