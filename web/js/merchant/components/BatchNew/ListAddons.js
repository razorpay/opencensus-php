import { Component } from 'react';
import { NavLink } from 'react-router-dom';

import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';

/**
 * Render this component when there are not batch row.
 */
export const EmptyComponent = (uploadUrl, openModalFunc) => {
  return (
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
};

/**
 *  Render customized status pill for batch
 */

export class BatchStatus extends Component {
  handleClick = () => {
    let { id } = this.props;

    this.props.onRefetchBatchDetails(id);
  };

  render() {
    let { id, status, onRefetchBatchDetails } = this.props;

    return (
      <span>
        <BatchUploadStatusLabel status={status} />
        {status === 'created' && (
          <i
            class="i i-refresh m-l fetch-batch-btn"
            onClick={this.handleClick}
          />
        )}
      </span>
    );
  }
}

export const BatchNavLink = ({ batchType, batchId }) => (
  <NavLink to={`/${batchType.split('_').join('')}s/batch/${batchId}`}>
    <code>{batchId}</code>
  </NavLink>
);
