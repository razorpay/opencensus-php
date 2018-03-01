import { Component } from 'react';
import { connect } from 'react-redux';

import BatchCreateModal from 'merchant/components/BatchNew/CreateModal';

import { createPaymentLinkBatch as createBatch } from 'merchant/modules/batches';

@connect(state => state.session, { createBatch })
export default class BatchCreate extends Component {
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
      .catch(error => console.log('err: ', error)); //TODO: Handle error response
  };

  render() {
    return (
      <BatchCreateModal
        closeModal={this.props.closeModal}
        parsedEntries={this.props.batch.parsed_entries}
        batchType={this.props.batchType}
        onCreateBatch={this.handleBatchCreate}
      />
    );
  }
}
