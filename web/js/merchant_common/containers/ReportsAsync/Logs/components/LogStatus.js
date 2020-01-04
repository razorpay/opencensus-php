export default function LogStatus(props) {
  return (
    <div class="LogStatus">
      <strong>{renderActionBasedOnStatus(props)}</strong>
    </div>
  );
}

function renderActionBasedOnStatus({ status, ...props }) {
  switch (status) {
    case 'created':
      return <p>Generating...</p>;

    case 'processed':
      return !!props.fileId ? renderDownloadButton(props) : renderNoDataError();

    default:
      return null;
  }
}

function renderDownloadButton(props) {
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

function renderNoDataError() {
  return <p>No data available</p>;
}
