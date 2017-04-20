import TableBody from '../TableBody';

const BatchUploadsListItem = ({ batchupload }) => {
  return (
    <tr>
      <td><code>{batchupload.id}</code></td>
      <td>{batchupload.total_count}</td>
      <td>{batchupload.status}</td>
      <td>
        <a class="btn btn-default btn-xs" href={`#/app/batches/${batchupload.id}/download`} target="_blank">
          Download
        </a>
      </td>
    </tr>
  );
};

export default ({ batchuploads, isLoading }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover table-striped">
        <thead>
          <tr>
            <th>Batch Id</th>
            <th>Count</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={4}
          rows={batchuploads}
          emptyTableMsg="No Batch Uploads found!"
        >
          {batchuploads.map(batchupload => (
            <BatchUploadsListItem key={batchupload.id} batchupload={batchupload} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
