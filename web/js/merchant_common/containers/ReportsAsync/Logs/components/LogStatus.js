export default function LogStatus(props) {
  return (
    <div class="LogStatus">
      <strong>{renderActionBasedOnStatus(props)}</strong>
    </div>
  );
}

function renderActionBasedOnStatus({ actualStatus, ...props }) {
  switch (actualStatus) {
    case 'created':
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
    <button
      class="btn btn-link"
      data-file-id={props.fileId}
      data-consumer-id={props.consumerId}
      onClick={props.onDownloadClick}
    >
      Download
    </button>
  );
}

function NoDataError() {
  return <p>No data available</p>;
}
