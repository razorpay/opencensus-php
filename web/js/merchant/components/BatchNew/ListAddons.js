/**
 * Render this component when there are not batch row.
 */
export const EmptyComponent = (uploadUrl, openModalFunc, emptyResultsDescription) => () => (
  <div class="empty-table-message">
    <h3>No Batch Files Found</h3>
    <h5 class="helper">
      {emptyResultsDescription || 'A Batch file consists of a group of payment links can be generated in bulk. Simply upload a file containing all the information and accept payments instantly.'}
    </h5>
    {openModalFunc && (
      <button class="btn btn-default" onClick={openModalFunc}>
        Start Uploading
      </button>
    )}
  </div>
);
