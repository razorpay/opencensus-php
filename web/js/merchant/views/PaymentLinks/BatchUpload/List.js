import { Component } from 'react';
import { connect } from 'react-redux';

import { exportFileAsExcel } from 'common/utils/rzp-utils';
import BatchList from 'merchant/containers/BatchNew/ListV2';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  fetchPaymentLinkBatches as fetchAll,
  createPaymentLinkBatch as createBatch,
  validatePaymentLinkBatch as validateBatch,
} from 'merchant/reducers/batches';
import { fetchPaymentLinkCustomFields } from 'merchant/reducers/paymentlinks/details';
import { showDynamicFields, convertExcelToObj } from 'merchant/views/PaymentLinks/utils';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import PaymentLinksForm from './components/PaymentLinksForm';
import SendAllLinks from './components/SendAllLinks';
import track from './track';

const gaEvents = setGaTrack('Dashboard - Payment Links - BU');

@connect(
  (state) => {
    return {
      user: state.session.user,
      issuableIdList: state.paymentBatchIds.issuableIdList,
    };
  },
  { fetchAll, createBatch, validateBatch, openModal, showNotification },
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
      dynamicFields: [],
      isCustomFieldsLoading: false,
    };
  }

  getFileNameAndUrl = () => {
    const fileName = this.props.user.isPaymentlinksV2Enabled
      ? 'sample_batch_payment_links_v2'
      : 'sample_batch_payment_links';

    const url = `/files/${fileName}.xlsx`;

    return { url, fileName };
  };

  getPaymentsLinksCustomFields = async () => {
    const res = await fetchPaymentLinkCustomFields();
    const fields = res?.data?.configurations || [];

    let customFields = [];

    if (fields.length > 0) {
      customFields = fields.reduce((acc, { configuration }) => {
        acc[`custom_field[${configuration.label}]`] = '1234567890';

        return acc;
      }, {});

      this.setState({ dynamicFields: customFields });
    }

    return customFields;
  };

  downloadSampleFile = async () => {
    try {
      const { url, fileName } = this.getFileNameAndUrl();

      if (!showDynamicFields()) {
        return window.open(url, '_self');
      }

      this.setState({ isCustomFieldsLoading: true });

      const rows = await convertExcelToObj(url);

      let customFields = this.state.dynamicFields;

      if (!customFields.length) {
        customFields = await this.getPaymentsLinksCustomFields();
      }

      const modifiedData = rows.map((fields) => ({ ...fields, ...customFields }));

      return exportFileAsExcel({
        finalDataSend: [{ category: `custom_fields`, data: modifiedData }],
        fileFormat: 'xlsx',
        fileName,
      });
    } catch (error) {
      return this.props.showNotification({
        type: 'error',
        message: error?.errors?.join(' '),
      });
    } finally {
      this.setState({ isCustomFieldsLoading: false });
    }
  };

  downloadSampleFileInModal = () => {
    track.downloadSampleInModal();

    this.downloadSampleFile();
  };

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
        onDocumentClick={track.onDocumentClickInModal}
        clickToUploadAnalytics={track.uploadClicked}
        onError={track.fileUploadError}
        onSuccess={track.fileUploadSuccess}
        onFileNameTrack={track.onFileNameTrack}
        onPreview={track.onPreview}
        trackCloseModal={track.abandonBatchModal}
        trackCreateBatch={track.createBatch}
        trackSuccessModalClose={track.successModalClose}
        onSampleFileDownload={this.downloadSampleFileInModal}
        shouldShowSampleDownloadBtn
        isSampleFileLoading={this.state.isCustomFieldsLoading}
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
        batchType={this.props.user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link'}
        batchActions={[this.sendAllLinks]}
        renderUploadModal={this.renderUploadModal}
        onBatchIdChange={track.batchIdChange}
        onBatchSearchCountChange={track.batchSearchCount}
        onSearchAnalytics={track.onSearchAnalytics}
        onClearAnalytics={track.onClearAnalytics}
        trackPagination={track.onPagination}
        gaEvents={gaEvents}
        onSampleFileDownload={this.downloadSampleFile}
        isSampleFileLoading={this.state.isCustomFieldsLoading}
        shouldShowSampleDownloadBtn
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
