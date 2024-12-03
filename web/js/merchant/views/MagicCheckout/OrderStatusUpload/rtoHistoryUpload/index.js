import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  EmptyComponent,
  ValidateModalInfo,
} from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/components/Content';
import RTOHistoryTable from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/containers/Table';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import InputSelector from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/containers/InputSelector';
import ModalCTA from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/components/ModalActions';
import Spinner from 'common/ui/Spinner';
import * as ModalActions from 'merchant_common/reducers/modals';
import {
  validateRTOHistoryBatches,
  fetchAllRTOHistoryBatches,
} from 'merchant/reducers/magicCheckout/rtoHistoryUpload/action';
import {
  DISPLAY_MESSAGES,
  SHIPPING_PROVIDERS,
  SAMPLE_FILE_URL,
} from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/constants';
import { ContentWrapper, TabHeader } from 'merchant/views/MagicCheckout/styled';

const CLOSE_URL = '/magic/delivery-status';

const openFileUpload = (validateBatch, openModal, provider, setProvider) => {
  openModal({
    size: 'large',
    className: 'rto-history-upload-modal',
    component: (
      <BatchUpload
        accept={['csv']}
        closeUrl={CLOSE_URL}
        title="Upload Pre-Magic delivery data"
        batchType="fulfillment_order_update"
        validateBatch={validateBatch}
        processFile
        displayMsgs={DISPLAY_MESSAGES}
        validateModalInfo={<ValidateModalInfo sampleUrl={SAMPLE_FILE_URL} />}
        maxFileSize={52428800} // 50MB
        batchListClass="rto-history-upload"
        disabled={!provider}
        component={
          <InputSelector provider={provider} list={SHIPPING_PROVIDERS} setInput={setProvider} />
        }
        modalActions={<ModalCTA provider={provider} />}
        isDragDropDisabled={!provider}
        hideCloseBtn
      />
    ),
  });
};
const RTOHistoryUpload = ({ openModal, validateBatch, rtoHistoryData, fetchAll, isRCOD }) => {
  const { error, loading, items, isUploadAllowed } = rtoHistoryData;

  const [provider, setProvider] = useState(null);

  useEffect(() => {
    if (fetchAll) fetchAll();
  }, [fetchAll]);

  const onUploadClick = useCallback(() => {
    openFileUpload(validateBatch, openModal, provider, setProvider);
  }, [validateBatch, openModal, provider, setProvider]);

  useEffect(() => {
    if (provider || provider === '') {
      onUploadClick();
    }
    return () => setProvider(null);
  }, [provider]);

  return loading ? (
    <div className="content-loader">
      <Spinner />
    </div>
  ) : (
    <ContentWrapper className="rto-history-container content-wrapper">
      <TabHeader className="tab-header">
        <div>
          <span className="heading">Pre-Magic delivery data</span>
          <p className="sub-text">
            Sharing order details for {`${isRCOD ? 'pre-MagicX' : 'pre-Magic Checkout'}`} orders
            will enable you to get better COD intelligence and RTO protection from Day 1.
          </p>
        </div>
        {isUploadAllowed && (
          <span className="pull-right upload-cta" data-testid="upload-order-history-cta">
            <button
              className="btn btn-primary"
              onClick={onUploadClick}
              name="upload-order-history-cta"
            >
              <i className="i i-plus" />
              Upload Order History
            </button>
          </span>
        )}
      </TabHeader>
      {!items.length ? (
        <EmptyComponent sampleUrl={SAMPLE_FILE_URL} />
      ) : (
        <RTOHistoryTable items={items} error={error} />
      )}
    </ContentWrapper>
  );
};

const mapStateToProps = (state) => ({
  rtoHistoryData: state.rtoHistoryUpload,
  isRCOD: state.magicCheckout.rcod,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      validateBatch: validateRTOHistoryBatches,
      fetchAll: fetchAllRTOHistoryBatches,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(RTOHistoryUpload);
