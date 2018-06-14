import { Component } from 'react';
import { Link } from 'react-router-dom';

import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';

/**
 * Render this component when there are not batch row.
 */
export const EmptyComponent = (uploadUrl, openModalFunc) => () => (
  <div class="empty-table-message">
    <h3>No Batch Files Found</h3>
    <h5 class="helper">
      A Batch file consists of a group of payment links can be generated in
      bulk. Simply upload a file containing all the information and accept
      payments instantly.
    </h5>
    <button class="btn btn-default" onClick={openModalFunc}>
      Start Uploading
    </button>
  </div>
);
