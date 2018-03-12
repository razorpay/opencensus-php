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
class BatchStatus extends Component {
  state = {
    shouldSpin: false,
  };
  handleClick = () => {
    let { id } = this.props;
    this.setState({ shouldSpin: true });
    setTimeout(() => {
      this.props.onRefetchBatchDetails(id);
    }, 1);
  };

  render() {
    const { id, status, onRefetchBatchDetails } = this.props;
    const { shouldSpin } = this.state;

    return (
      <span>
        <BatchUploadStatusLabel status={status} />
        {status === 'created' && (
          <i
            class={`i i-refresh refetch-batch-btn${shouldSpin ? ' spin' : ''}`}
            onClick={this.handleClick}
          />
        )}
      </span>
    );
  }
}

/**
 * Render NavLink of batch
 */
const BatchNavLink = ({ batchType, batchId }) => (
  <NavLink to={`/${batchType.split('_').join('')}s/batch/${batchId}`}>
    <code>{batchId}</code>
  </NavLink>
);

// Pairs

export const batchIdLink = {
  title: 'Batch ID',
  value: batch => <BatchNavLink batchId={batch.id} batchType={batch.type} />,
};

export const batchStatus = refetchBatchDetails => {
  return {
    title: 'Status',
    value: item => (
      <BatchStatus
        id={item.id}
        status={item.status}
        onRefetchBatchDetails={refetchBatchDetails}
      />
    ),
  };
};
