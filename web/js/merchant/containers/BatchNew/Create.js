import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { showNotification } from 'rzp/modules/notifications';

import BatchCreateModal from 'merchant/components/BatchNew/CreateModal';
import PaymentLinksForm from 'merchant/components/BatchNew/PaymentLinksForm';

import { createPaymentLinkBatch as createBatch } from 'merchant/modules/batches';

import { trackUploadBatch } from './ga';
@connect(state => state.session, { createBatch, showNotification })
export default class BatchCreate extends Component {
  formInitialValues = {
    name: this.props.batchName,
  };

  state = {
    sms_notify: 0,
    email_notify: 0,
    ctaText: 'Create',
    pendingText: 'Creating...',
  };

  generateCtaText = () => {
    const { sms_notify, email_notify } = this.state;
    let ctaText = '',
      pendingText = '';

    if (sms_notify || email_notify) {
      ctaText = 'Create Batch & Send Payment Links';
      pendingText = 'Creating & Sending...';
    } else {
      ctaText = 'Create Batch';
      pendingText = 'Creating...';
    }

    this.setState({ ctaText, pendingText });
  };

  handleChange = (propName, value) => {
    this.setState(
      {
        [propName]: value | 0,
      },
      this.generateCtaText
    );
  };

  handleBatchCreate = props => {
    let data = { ...props };

    data.sms_notify = data.sms_notify | 0;
    data.email_notify = data.email_notify | 0;

    data.file_id = this.props.batch.file_id;
    data.draft = 0; //for backward compatibility
    data.config = {
      sms_notify: data.sms_notify,
      email_notify: data.email_notify,
    };
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
    return (
      <BatchCreateModal
        closeModal={this.props.closeModal}
        parsedEntries={this.props.batch.parsed_entries}
        batchType={this.props.batchType}
        onCreateBatch={this.handleBatchCreate}
        initialValues={this.formInitialValues}
        ctaText={this.state.ctaText}
        pendingText={this.state.pendingText}
      >
        <PaymentLinksForm
          batchType={this.props.batchType}
          sms_notify={this.state.sms_notify}
          email_notify={this.state.email_notify}
          onChange={this.handleChange}
        />
      </BatchCreateModal>
    );
  }
}
