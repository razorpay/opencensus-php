import BatchUpload from 'merchant/containers/BatchNew/Upload';
import OrderStatusList from 'merchant/views/MagicCheckout/OrderStatusTab/containers/List';
import { validateModalInfo } from 'merchant/views/MagicCheckout/OrderStatusTab/components/Content';

import { useCallback, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Spinner from 'common/ui/Spinner';
import * as ModalActions from 'merchant_common/reducers/modals';
import {
  fetchAllOrderStatusBatches,
  createOrderStatusBatch,
  validateOrderStatusBatch,
} from 'merchant/reducers/magicCheckout/bulk_order_statuses';
import { LIMIT } from 'merchant/views/MagicCheckout/OrderStatusTab/constants';
import setGaTrack from 'merchant/containers/BatchNew/ga';

const gaEvents = setGaTrack('Dashboard - Magic Checkout - BU - Order Status');
const SAMPLE_BATCH_UPLOAD_FILE =
  'https://cdn.razorpay.com/static/assets/magic-checkout/sample_batch_order_status_upload.xlsx';

const openBatchUploadModal = (createBatch, validateBatch, openModal) => {
  openModal({
    size: 'large',
    className: 'delivery-status-upload',
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
        validateModalInfo={validateModalInfo(LIMIT, SAMPLE_BATCH_UPLOAD_FILE)}
        maxFileSize={52428800} // 50MB
      />
    ),
  });
};

const OrderStatuses = ({ openModal, createBatch, validateBatch, fetchAll, orderStatusData }) => {
  const { loading, items, error } = orderStatusData;

  useEffect(() => {
    if (fetchAll) fetchAll();
  }, [fetchAll]);

  const onUploadClick = useCallback(() => {
    openBatchUploadModal(createBatch, validateBatch, openModal);
  }, [createBatch, validateBatch, openModal]);
  return loading ? (
    <div className="content-loader">
      <Spinner />
    </div>
  ) : (
    <div className="delivery-status-container content-wrapper">
      <div className="tab-header">
        <span className="heading">Monthly delivery data</span>
        <span className="pull-right upload-cta">
          <button className="btn btn-primary" onClick={onUploadClick}>
            <i className="i i-plus" />
            Upload Delivery Statuses
          </button>
        </span>
        <p className="sub-text">
          Upload delivery status periodically for orders placed via Magic Checkout to get better
          reduction of RTOs via COD Intelligence and claim RTO Protection.
        </p>
      </div>
      <OrderStatusList
        sampleUrl={SAMPLE_BATCH_UPLOAD_FILE}
        maxRows={LIMIT}
        items={items}
        error={error}
      />
    </div>
  );
};

const mapStateToProps = (state) => ({
  orderStatusData: state.orderStatusBatches,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      createBatch: createOrderStatusBatch,
      validateBatch: validateOrderStatusBatch,
      fetchAll: fetchAllOrderStatusBatches,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(OrderStatuses);
