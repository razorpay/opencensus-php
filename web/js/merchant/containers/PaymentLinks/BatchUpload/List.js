import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import BatchList from 'merchant/containers/BatchNew/List';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import { openModal } from 'rzp/modules/modals';
import {
  fetchPaymentLinkBatches as fetchAll,
  createPaymentLinkBatch as createBatch,
  validatePaymentLinkBatch as validateBatch,
} from 'merchant/modules/batches';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import PaymentLinksForm from '../PaymentLinksForm';
import SendAllLinks from '../SendAllLinks';

const gaEvents = setGaTrack('Dashboard - Payment Links - BU');

@connect(
  state => {
    return {
      issuableIdList: state.paymentBatchIds.issuableIdList,
    };
  },
  { fetchAll, createBatch, validateBatch, openModal }
)
export default class BatchListContainer extends Component {
  constructor(props) {
    super(props);
    //hotjar integration
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'batch_payment_links');
      window.hj('tagRecording', ['batch_payment_links']);
    }
  }

  state = {
    sms_notify: 0,
    email_notify: 0,
  };

  handlePaymentLinksFormChange = (propName, value) => {
    this.setState({
      [propName]: value,
    });
  };

  sendAll = item => {
    this.props.openModal({
      size: 'small',
      component: (
        <SendAllLinks
          trackSendAllLinks={gaEvents.trackSendAllLinks}
          batchId={item.id}
          fetchAll={this.props.fetchAll}
        />
      ),
    });
  };

  sendAllLinks = item => {
    if (['created', 'failure'].indexOf(item.status) < 0) {
      const allowSendAll = allowSendAllLinks(item);
      return (
        <button
          key={item.id}
          class="btn btn-xs btn-default"
          onClick={() => this.sendAll(item)}
          disabled={!allowSendAll}
        >
          {allowSendAll ? 'Send all links' : 'All links sent'}
        </button>
      );
    }
    return null;
  };

  renderUploadModal = () => {
    const { sms_notify, email_notify } = this.state;
    const notify = sms_notify || email_notify;
    const batchFormInitialValues = {
      draft: 0, //for backward compatibility
      config: {
        sms_notify: false,
        email_notify: false,
      },
    };
    return (
      <BatchUpload
        ctaText={`Create Batch${notify ? ' & Send Payment Links' : ''}`}
        pendingText={`Creating${notify ? ' & Sending' : ''}...`}
        batchFormInitialValues={batchFormInitialValues}
        batchType="payment_link"
        maxRows={50000}
        maxFileSize={5242880}
        gaEvents={gaEvents}
        createBatch={this.props.createBatch}
        validateBatch={this.props.validateBatch}
        docUrl="https://razorpay.com/docs/payment-links/batch-upload/"
        sampleUrl="/files/sample_batch_payment_links_v2.xlsx"
        renderBatchCreationForm={() => (
          <PaymentLinksForm
            batchType={this.props.batchType}
            sms_notify={this.state.sms_notify}
            email_notify={this.state.email_notify}
            onChange={this.handlePaymentLinksFormChange}
          />
        )}
      />
    );
  };

  render() {
    return (
      <BatchList
        form="batchListFilter"
        docUrl="https://razorpay.com/docs/payment-links/batch-upload/"
        sampleUrl="/files/sample_batch_payment_links_v2.xlsx"
        batchType="payment_link"
        batchActions={[this.sendAllLinks]}
        renderUploadModal={this.renderUploadModal}
        gaEvents={gaEvents}
        {...this.props}
      />
    );
  }
}

function allowSendAllLinks(batch) {
  // config object will not be available for older batches
  // duplicate batches will have no success count
  if (batch.config && Object.keys(batch.config).length) {
    if (
      parseInt(batch.config.sms_notify) > 0 ||
      parseInt(batch.config.email_notify) > 0
    ) {
      //if more than 0 payment link(s) has been sent, disable the btn
      return false;
    } else {
      return true;
    }
  }
  //disable for older batches
  return false;
}
