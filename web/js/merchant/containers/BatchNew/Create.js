import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { showNotification } from 'rzp/modules/notifications';

import BatchCreateModal from 'merchant/components/BatchNew/CreateModal';

import { createPaymentLinkBatch as createBatch } from 'merchant/modules/batches';

import { trackUploadBatch } from './ga';
@connect(state => state.session, { createBatch, showNotification })
export default class BatchCreate extends Component {
  formInitialValues = {
    name: this.props.batchName,
  };

  handleBatchCreate = props => {
    let data = { ...props };

    data.file_id = this.props.batch.file_id;
    trackUploadBatch('Create');
    return this.props
      .createBatch(data)
      .then(response => {
        this.props.onCreation(response);
      })
      .catch(error => {
        this.props.showNotification({
          type: 'error',
          message: 'Failed to create batch.',
        });
      });
  };

  render() {
    const {
      handleBatchCreate,
      props: {
        closeModal,
        batch,
        ctaText,
        pendingText,
        batchName,
        batchType,
        renderBatchCreationForm,
        batchFormInitialValues = {},
      },
    } = this;

    return (
      <BatchCreateModal
        closeModal={closeModal}
        parsedEntries={batch.parsed_entries}
        batchType={batchType}
        onCreateBatch={handleBatchCreate}
        initialValues={{ name: batchName, ...batchFormInitialValues }}
        ctaText={ctaText}
        pendingText={pendingText}
      >
        {renderBatchCreationForm && renderBatchCreationForm()}
      </BatchCreateModal>
    );
  }
}
