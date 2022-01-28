import BatchUpload from 'merchant/containers/BatchNew/Upload';
import AddressList from 'merchant/views/MagicCheckout/BulkAddressUpload/containers/List';
import HeaderAction from 'common/ui/HeaderAction';

import { useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  createAddressBatch,
  validateAddressBatch,
} from 'merchant/reducers/magicCheckout/bulk_address_upload';
import * as ModalActions from 'merchant_common/reducers/modals';

import setGaTrack from 'merchant/containers/BatchNew/ga';

const gaEvents = setGaTrack('Dashboard - Magic Checkout - BU');
const SAMPLE_BATCH_UPLOAD_FILE = `https://dashboard.razorpay.com/files/sample_batch_address_upload.xlsx`;

const validateModalInfo = (maxRows, sampleUrl) => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>
        File should follow the template format. Download{' '}
        <a className="btn-link" href={sampleUrl}>
          <strong>sample file</strong>
        </a>{' '}
        for the template.
      </li>
      <li>
        Contact, Address Line1, City, State, Country and Zipcode are mandatory fields, can’t be left
        blank.
      </li>
      <li>
        For “address_type”, recommended values are home, office and other, if labels are available.
      </li>
      <li>The number of rows should not exceed {maxRows}.</li>
    </ol>
  </div>
);

const successModalContent = () => (
  <div className="text-center">
    <p>
      Upload was successful. You can download the output file to check for processed addresses. For
      the addresses that could not be processed due to some issues, please upload a new batch file.
    </p>
  </div>
);

const openBatchUploadModal = (createBatch, validateBatch, openModal) => {
  openModal({
    size: 'large',
    component: (
      <BatchUpload
        sampleUrl={SAMPLE_BATCH_UPLOAD_FILE}
        closeUrl="/magic"
        ctaText="Create Batch"
        title="Upload Addresses"
        pendingText="Creating & Sending..."
        batchType="raw_address"
        maxRows={1000000} // 1M (Excel Limit)
        createBatch={createBatch}
        validateBatch={validateBatch}
        gaEvents={gaEvents}
        validateModalInfo={validateModalInfo('1M')}
        successModalContent={successModalContent()}
        maxFileSize={52428800} // 50MB
      />
    ),
  });
};

const BulkAddressUpload = ({ openModal, createBatch, validateBatch }) => {
  const onUploadClick = useCallback(() => {
    openBatchUploadModal(createBatch, validateBatch, openModal);
  }, [createBatch, validateBatch, openModal]);

  return (
    <>
      <HeaderAction responsive>
        <div class="btn-toolbar pull-right">
          <div className="pull-right MultiBatch--action">
            <a className="btn btn-link" href={SAMPLE_BATCH_UPLOAD_FILE}>
              Download Sample File
            </a>
            <button className="btn btn-primary" onClick={onUploadClick}>
              Upload Address File
            </button>
          </div>
        </div>
      </HeaderAction>

      <content>
        <AddressList />
      </content>
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      createBatch: createAddressBatch,
      validateBatch: validateAddressBatch,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(BulkAddressUpload);
