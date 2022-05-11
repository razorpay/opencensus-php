import { Component } from 'react';
import { connect } from 'react-redux';

import BatchList from 'merchant/containers/BatchNew/List';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import { openModal } from 'merchant_common/reducers/modals';
import {
  fetchPaymentLinkBatches as fetchAll,
  createPaymentLinkBatch as createBatch,
  validatePaymentLinkBatch as validateBatch,
} from 'merchant/reducers/batches';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import track from './track';
import PaymentLinksForm from './components/PaymentLinksForm';
import SendAllLinks from './components/SendAllLinks';

const gaEvents = setGaTrack('Dashboard - Payment Links - BU');

@connect(
  (state) => {
    return {
      user: state.session.user,
      issuableIdList: state.paymentBatchIds.issuableIdList,
    };
  },
  { fetchAll, createBatch, validateBatch, openModal },
)
export default class BatchListContainer extends Component {
  constructor(props) {
    super(props);
    //hotjar integration
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'batch_payment_links');
      window.hj('tagRecording', ['batch_payment_links']);
    }

    this.state = {
      sms_notify: 0,
      email_notify: 0,
      reminder_enable: 1,
    };
  }

  get sampleUrl() {
    let url = '/files/sample_batch_payment_links.xlsx';

    if (this.props.user.isPaymentlinksV2Enabled) {
      url = '/files/sample_batch_payment_links_v2.xlsx';
    }

    return url;
  }

  handlePaymentLinksFormChange = (propName, value) => {
    this.setState({
      [propName]: value,
    });
  };

  sendAll = (item) => {
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

  sendAllLinks = (item) => {
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
    const { sms_notify, email_notify, reminder_enable } = this.state;
    const notify = sms_notify || email_notify;
    const batchFormInitialValues = {
      config: {
        draft: 0, //for backward compatibility
        sms_notify: false,
        email_notify: false,
        reminder_enable,
      },
    };
    return (
      <BatchUpload
        ctaText={`Create Batch${notify ? ' & Send Payment Links' : ''}`}
        pendingText={`Creating${notify ? ' & Sending' : ''}...`}
        batchFormInitialValues={batchFormInitialValues}
        batchType={this.props.user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link'}
        maxRows={50000}
        maxFileSize={60457280} // 60MB
        gaEvents={gaEvents}
        createBatch={this.props.createBatch}
        validateBatch={this.props.validateBatch}
        docUrl="https://razorpay.com/docs/payment-links/batch-upload/"
        sampleUrl={this.sampleUrl}
        onDocumentClick={track.onDocumentClickInModal}
        onSampleFileDownload={track.donwloadSampleInModal}
        clickToUploadAnalytics={track.uploadClicked}
        onError={track.fileUploadError}
        onSuccess={track.fileUploadSuccess}
        onFileNameTrack={track.onFileNameTrack}
        onPreview={track.onPreview}
        trackCloseModal={track.abandonBatchModal}
        trackCreateBatch={track.createBatch}
        trackSuccessModalClose={track.successModalClose}
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
        sampleUrl={this.sampleUrl}
        batchType={this.props.user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link'}
        batchActions={[this.sendAllLinks]}
        renderUploadModal={this.renderUploadModal}
        onBatchIdChange={track.batchIdChange}
        onBatchSearchCountChange={track.batchSearchCount}
        onSearchAnalytics={track.onSearchAnalytics}
        onClearAnalytics={track.onClearAnalytics}
        trackPagination={track.onPagination}
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
    if (Number(batch.config.sms_notify) > 0 || Number(batch.config.email_notify) > 0) {
      //if more than 0 payment link(s) has been sent, disable the btn
      return false;
    } else {
      return true;
    }
  }
  //disable for older batches
  return false;
}
