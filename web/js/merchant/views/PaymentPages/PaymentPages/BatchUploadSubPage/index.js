import React, { useState, useEffect } from 'react';
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { Button, Spinner } from '@razorpay/blade/components';
import Header from 'merchant/views/PaymentPages/PaymentPages/BatchUploadSubPage/Header';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchPaymentPage } from 'merchant/reducers/wysiwyg';
import { getErrorMessageFromResponse, exportFileAsExcel } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink';
import {
  getHeaderList,
  getFormattedExcelData,
} from 'merchant/views/PaymentPages/BatchUpload/helper';
import {
  createPaymentPageBatch as createBatch,
  validatePaymentPageBatch as validateBatch,
} from 'merchant/reducers/batches';
import PaymentLinksForm from 'merchant/views/PaymentLinks/BatchUpload/components/PaymentLinksForm';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import { openModal } from 'merchant_common/reducers/modals';
import track from 'merchant/views/PaymentPages/BatchUpload/track';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  MAX_ROWS,
  MAX_FILE_SIZE,
  BATCH_UPLOAD_DOC_URL,
  BATCH_TYPE,
} from 'merchant/views/PaymentPages/PaymentPages/constants';

const gaEvents = setGaTrack('Dashboard - Batch Payment Page - BU');

function BatchUploadSubPage({
  id,
  fetchPaymentPage,
  validateBatch,
  createBatch,
  openModal,
  isBatchPaymentPages,
  paymentPageEntity,
}) {
  const [pageDetails, setPageDetails] = useState({
    isLoaded: false,
    pageLoadError: '',
    finalDataSend: [],
    sms_notify: 0,
    email_notify: 0,
    reminder_enable: 1,
  });

  const fetchEntity = async () => {
    try {
      const resp = await fetchPaymentPage(id, false); // Auto reinitialise store if id doesn't exist.
      const headerList = getHeaderList(resp?.data);
      if (!headerList) {
        setPageDetails({
          pageLoadError: 'Issue while parsing the udf_schema, please try again later',
          isLoaded: true,
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
      setPageDetails({
        isLoaded: true,
        finalDataSend: data,
      });
    } catch ({ errors }) {
      const err = getErrorMessageFromResponse(errors);
      setPageDetails({
        pageLoadError: err?.[0] || 'Something went wrong, please try again later',
        isLoaded: true,
      });
    }
  };

  useEffect(() => {
    fetchEntity();
  }, []);

  const onSampleFileDownload = () => {
    const { finalDataSend } = pageDetails;
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

  const onValidateBatch = (file, progressTracker) => {
    return validateBatch(file, progressTracker, id);
  };

  const onCreateBatch = (data) => {
    data.config.payment_page_id = id;
    return createBatch(data);
  };

  const handlePaymentLinksFormChange = (propName, value) => {
    setPageDetails({
      [propName]: value,
    });
  };

  const openUploadModal = (renderUploadModal) => {
    openModal({
      size: 'large',
      component: renderUploadModal(),
    });
  };

  const renderPaymentLinksForm = () => {
    const { sms_notify, email_notify } = pageDetails;
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
    const { sms_notify, email_notify, reminder_enable } = pageDetails;
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

  const { isLoaded, pageLoadError } = pageDetails;

  let content;

  if (!isLoaded) {
    content = <Spinner size="xlarge" accessibilityLabel="Loading..." />;
  } else if (pageLoadError) {
    content = <div className="error-message">{pageLoadError}</div>;
  } else {
    content = (
      <div className="content">
        <Link className="btn edit-page-btn" to={`/paymentpages/${paymentPageEntity.id}/edit`}>
          <i className="i i-chevron-left" />
          <span>EDIT PAGE</span>
        </Link>

        <div id="next-steps">
          <div id="next-steps--title">
            Upload Batch
            <div className="divider" />
          </div>
          <div className="box">
            <div className="box--left">
              <div className="box--line">
                For the smooth functioning of this payment page data needs to be uploaded in the
                defined manner as a batch.
                <br />
                <br />
                <b>Getting Started with batch Upload?</b>{' '}
                <DocLink
                  className="btn btn-link m-l doc-url"
                  href={BATCH_UPLOAD_DOC_URL}
                  target="_blank"
                >
                  View Documentation <i className="i i-external-link" />
                </DocLink>
                <br />
                Upload a batch file to continue.
                <br />
                <br />
                Please note the following things before proceeding further:
                <br />
                <ol>
                  <li>The amount mentioned should be in paise.</li>
                  <li>The Primary Reference ID should be unique for each entry.</li>
                  <li>The number of rows should not exceed 50000.</li>
                </ol>
                <a className="btn-link" onClick={onSampleFileDownload}>
                  <strong>Download Sample File</strong>
                </a>
              </div>
            </div>
            <div className="box--right">
              <Button
                className="Button--secondary"
                variant="secondary"
                size="medium"
                onClick={() => openUploadModal(renderUploadModal)}
              >
                Upload Batch
              </Button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="pp-success-container">
      <Header id={id} isBatchPaymentPages={isBatchPaymentPages} />
      {content}
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    ...state.wysiwyg,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      fetchPaymentPage,
      createBatch,
      validateBatch,
      openModal,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(BatchUploadSubPage);
