import BatchUpload from 'merchant/containers/BatchNew/Upload';
import AddressList from 'merchant/views/MagicCheckout/BulkAddressUpload/containers/List';
import HeaderAction from 'common/ui/HeaderAction';
import { validateModalInfo } from 'merchant/views/MagicCheckout/BulkAddressUpload/components/Content';

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
        accept={['xlsx']}
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
        validateModalInfo={validateModalInfo('1M', SAMPLE_BATCH_UPLOAD_FILE)}
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
    <div className="content-wrapper">
      <HeaderAction responsive>
        <div className="btn-toolbar pull-right">
          <div className="pull-right MultiBatch--action">
            <button type="button" className="btn btn-primary" onClick={onUploadClick}>
              Upload Address File
            </button>
          </div>
        </div>
      </HeaderAction>

      <content>
        <AddressList />
      </content>
    </div>
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
