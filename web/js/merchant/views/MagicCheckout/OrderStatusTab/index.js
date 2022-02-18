import BatchUpload from 'merchant/containers/BatchNew/Upload';
import HeaderAction from 'common/ui/HeaderAction';
import OrderStatusList from 'merchant/views/MagicCheckout/OrderStatusTab/containers/List';
import { validateModalInfo } from 'merchant/views/MagicCheckout/OrderStatusTab/components/Content';

import { useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import {
  createOrderStatusBatch,
  validateOrderStatusBatch,
} from 'merchant/reducers/magicCheckout/bulk_order_statuses';

import setGaTrack from 'merchant/containers/BatchNew/ga';

const gaEvents = setGaTrack('Dashboard - Magic Checkout - BU - Order Status');
const SAMPLE_BATCH_UPLOAD_FILE =
  'https://cdn.razorpay.com/static/assets/magic-checkout/sample_batch_order_status_upload.xlsx';

const openBatchUploadModal = (createBatch, validateBatch, openModal) => {
  openModal({
    size: 'large',
    component: (
      <BatchUpload
        sampleUrl={SAMPLE_BATCH_UPLOAD_FILE}
        closeUrl="/magic/delivery-status"
        ctaText="Create Batch"
        title="Upload Delivery Statuses"
        pendingText="Creating & Sending..."
        batchType="fulfillment_order_update"
        maxRows={1000000} // 1M (Excel Limit)
        createBatch={createBatch}
        validateBatch={validateBatch}
        gaEvents={gaEvents}
        validateModalInfo={validateModalInfo('1M', SAMPLE_BATCH_UPLOAD_FILE)}
        maxFileSize={52428800} // 50MB
      />
    ),
  });
};

const OrderStatuses = ({ openModal, createBatch, validateBatch }) => {
  const onUploadClick = useCallback(() => {
    openBatchUploadModal(createBatch, validateBatch, openModal);
  }, [createBatch, validateBatch, openModal]);
  return (
    <div className="content-wrapper">
      <HeaderAction responsive>
        <div className="pull-right MultiBatch--action">
          <button className="btn btn-primary" onClick={onUploadClick}>
            Upload Delivery Status File
          </button>
        </div>
      </HeaderAction>
      <OrderStatusList sampleUrl={SAMPLE_BATCH_UPLOAD_FILE} />
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      createBatch: createOrderStatusBatch,
      validateBatch: validateOrderStatusBatch,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(OrderStatuses);
