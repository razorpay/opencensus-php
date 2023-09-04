import { useState, useEffect } from 'react';
import { connect } from 'react-redux';

import { getErrorMessageFromResponse, exportFileAsExcel } from 'common/utils/rzp-utils';
import BatchList from 'merchant/containers/BatchNew/ListV2';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  fetchPaymentPageBatches as fetchAll,
  createPaymentPageBatch as createBatch,
  validatePaymentPageBatch as validateBatch,
} from 'merchant/reducers/batches';
import PaymentLinksForm from 'merchant/views/PaymentLinks/BatchUpload/components/PaymentLinksForm';
import SendAllLinks from 'merchant/views/PaymentLinks/BatchUpload/components/SendAllLinks';
import {
  getHeaderList,
  getFormattedExcelData,
  allowSendAllLinks,
} from 'merchant/views/PaymentPages/BatchUpload/helper';
import track from 'merchant/views/PaymentPages/BatchUpload/track';
import {
  MAX_ROWS,
  MAX_FILE_SIZE,
  BATCH_UPLOAD_DOC_URL,
  BATCH_TYPE,
} from 'merchant/views/PaymentPages/PaymentPages/constants';
import { fetchPaymentPageEntity } from 'merchant/views/PaymentPages/PaymentPages/model';
import { openModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const gaEvents = setGaTrack('Dashboard - Payment Page - BU');

const BatchListContainer = ({
  showNotification,
  id,
  fetchAll,
  validateBatch,
  createBatch,
  location,
  openModal,
}) => {
  const [isPaymentPageDetailsLoading, setPaymentPageDetailsLoading] = useState(true);
  const [finalDataSend, setFinalDataSend] = useState([]);
  const [notificationConfig, setNotificationConfig] = useState({
    sms_notify: 0,
    email_notify: 0,
    reminder_enable: 1,
  });

  const fetchEntity = async () => {
    try {
      const resp = await fetchPaymentPageEntity(id);
      setPaymentPageDetailsLoading(false);
      const headerList = getHeaderList(resp?.data);
      if (!headerList) {
        showNotification({
          type: 'error',
          message: 'Issue while parsing the udf_schema, please try again later',
        });
        return;
      }
      const formattedExcelData = getFormattedExcelData(headerList);
      const data = [
        {
          category: `sample_${id}`,
          data: formattedExcelData,
        },
      ];
      setFinalDataSend(data);
    } catch ({ errors }) {
      setPaymentPageDetailsLoading(false);
      const err = getErrorMessageFromResponse(errors);
      showNotification({
        type: 'error',
        message: err?.[0],
      });
    }
  };

  useEffect(() => {
    fetchEntity();
  }, []);

  const handlePaymentLinksFormChange = (propName, value) => {
    setNotificationConfig({
      [propName]: value,
    });
  };

  const onSampleFileDownload = () => {
    const fileName = `sample_${id}`;
    if (finalDataSend.length > 0) {
      exportFileAsExcel({ finalDataSend, fileName, fileFormat: 'xlsx' });
    } else {
      showNotification({
        type: 'error',
        message: 'Error while generating sample file.',
      });
    }
  };

  const fetchAllBatches = (params) => {
    params && fetchAll({ id, params });
  };

  const sendAll = (item) => {
    openModal({
      size: 'small',
      component: (
        <SendAllLinks
          trackSendAllLinks={gaEvents.trackSendAllLinks}
          batchId={item.id}
          fetchAll={fetchAllBatches}
          type={item.type}
          paymentLinkId={id}
        />
      ),
    });
  };

  const sendAllLinks = (item) => {
    const { status, id } = item;
    if (!['created', 'failure'].includes(status)) {
      const allowSendAll = allowSendAllLinks(item);
      return (
        <button
          type="button"
          key={id}
          className="btn btn-xs btn-default"
          onClick={() => sendAll(item)}
          disabled={!allowSendAll}
        >
          {allowSendAll ? 'Send all links' : 'All links sent'}
        </button>
      );
    }
    return null;
  };

  const onValidateBatch = (file, progressTracker) => {
    return validateBatch(file, progressTracker, id);
  };

  const onCreateBatch = (data) => {
    data.config.payment_page_id = id;
    return createBatch(data);
  };

  const renderPaymentLinksForm = () => {
    const { sms_notify, email_notify } = notificationConfig;
    return (
      <PaymentLinksForm
        batchType={BATCH_TYPE}
        sms_notify={sms_notify}
        email_notify={email_notify}
        onChange={handlePaymentLinksFormChange}
      />
    );
  };

  const renderUploadModal = () => {
    const { sms_notify, email_notify, reminder_enable } = notificationConfig;
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
        batchType={BATCH_TYPE}
        maxRows={MAX_ROWS}
        maxFileSize={MAX_FILE_SIZE}
        gaEvents={gaEvents}
        createBatch={onCreateBatch}
        validateBatch={onValidateBatch}
        docUrl={BATCH_UPLOAD_DOC_URL}
        onDocumentClick={track.onDocumentClickInModal}
        onSampleFileDownload={onSampleFileDownload}
        shouldShowSampleDownloadBtn
        clickToUploadAnalytics={track.uploadClicked}
        onError={track.fileUploadError}
        onSuccess={track.fileUploadSuccess}
        onFileNameTrack={track.onFileNameTrack}
        onPreview={track.onPreview}
        trackCloseModal={track.abandonBatchModal}
        trackCreateBatch={track.createBatch}
        trackSuccessModalClose={track.successModalClose}
        renderBatchCreationForm={renderPaymentLinksForm}
      />
    );
  };

  return (
    <BatchList
      form="batchListFilter"
      docUrl={BATCH_UPLOAD_DOC_URL}
      batchType={BATCH_TYPE}
      renderUploadModal={renderUploadModal}
      onBatchIdChange={track.batchIdChange}
      onSearchAnalytics={track.onSearchAnalytics}
      onClearAnalytics={track.onClearAnalytics}
      trackPagination={track.onPagination}
      gaEvents={gaEvents}
      onSampleFileDownload={onSampleFileDownload}
      shouldShowSampleDownloadBtn
      location={location}
      isSampleFileLoading={isPaymentPageDetailsLoading}
      fetchAll={fetchAllBatches}
      batchActions={[sendAllLinks]}
    />
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = {
  fetchAll,
  createBatch,
  validateBatch,
  openModal,
  ...NotificationsActions,
};
export default connect(mapStateToProps, mapDispatchToProps)(BatchListContainer);
