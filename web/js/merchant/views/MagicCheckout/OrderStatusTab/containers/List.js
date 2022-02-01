import { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import DataTable from 'common/ui/Table/DataTable';
import { EmptyComponent } from 'merchant/views/MagicCheckout/OrderStatusTab/components/Content';
import {
  totalCount,
  status,
  processedCount,
  uploadedOn,
  actions,
  fileName,
} from 'merchant/views/MagicCheckout/OrderStatusTab/components/cellItem';
import {
  fetchAllOrderStatusBatches,
  createOrderStatusBatch,
} from 'merchant/reducers/magicCheckout/bulk_order_statuses';
import { batchDownload } from 'merchant/reducers/batches';
import { batchId } from 'common/ui/item/pair';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const DEFAULT_ERROR_MESSAGE = 'Something went wrong. Please try again later.';

const BatchListContainer = (props) => {
  const { sampleUrl, fetchAll, loading, items } = props;

  useEffect(() => {
    if (fetchAll) {
      fetchAll();
    }
  }, [fetchAll]);

  const onBatchDownloadClick = (id) => () => {
    props
      .batchDownload(id)
      .then((response) => {
        if (response?.data?.url) {
          window.location = response.data.url;
        }
      })
      .catch(({ errors }) => {
        props.showNotification({
          type: 'error',
          message: errors || DEFAULT_ERROR_MESSAGE,
        });
      });
  };

  return (
    <>
      {!loading && !items.length ? (
        <EmptyComponent sampleUrl={sampleUrl} />
      ) : (
        <DataTable
          title="Batch Uploads"
          columns={[
            batchId,
            fileName,
            totalCount,
            processedCount,
            uploadedOn,
            status,
            actions({ onClick: onBatchDownloadClick }),
          ]}
          {...props}
        />
      )}
    </>
  );
};

const mapStateToProps = (state) => {
  return { mode: state.session.mode, user: state.session.user, ...state.orderStatusBatches };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchAll: fetchAllOrderStatusBatches,
      createOrderStatusBatch,
      batchDownload,
      ...NotificationsActions,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(BatchListContainer);
