import { Component } from 'react';
import { connect } from 'react-redux';

import { showNotification } from 'rzp/modules/notifications';

import BatchCreateModal from 'merchant/components/BatchNew/CreateModal';
import { createPaymentLinkBatch as createBatch } from 'merchant/modules/batches';

@connect(state => state.session, { createBatch, showNotification })
export default class BatchCreate extends Component {
  formInitialValues = {
    sms_notify: 0,
    email_notify: 0,
    name: this.props.batchName,
  };
  handleBatchCreate = props => {
    let data = { ...props };

    data.sms_notify = data.sms_notify | 0;
    data.email_notify = data.email_notify | 0;

    data.file_id = this.props.batch.file_id;

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
    return (
      <BatchCreateModal
        closeModal={this.props.closeModal}
        parsedEntries={this.props.batch.parsed_entries}
        batchType={this.props.batchType}
        onCreateBatch={this.handleBatchCreate}
        initialValues={this.formInitialValues}
      />
    );
  }
}
