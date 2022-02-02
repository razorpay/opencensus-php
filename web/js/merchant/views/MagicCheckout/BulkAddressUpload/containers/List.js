import { connect } from 'react-redux';
import { batchDownload } from 'merchant/reducers/batches';
import {
  createAddressBatch,
  fetchAllAddressBatches,
} from 'merchant/reducers/magicCheckout/bulk_address_upload';
import { bindActionCreators } from 'redux';
import { batchId } from 'common/ui/item/pair';
import {
  totalCount,
  status,
  processedCount,
  uploadedOn,
  actions,
  fileName,
} from 'merchant/views/MagicCheckout/BulkAddressUpload/components/cellItem';
import DataTable from 'common/ui/Table/DataTable';
import { useEffect } from 'react';
import { EmptyComponent as emptyComponent } from 'merchant/components/BatchNew/ListAddons';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const DEFAULT_ERROR_MESSAGE = 'Something went wrong. Please try again later.';

const BatchListContainer = (props) => {
  const { fetchAll } = props;

  useEffect(() => {
    if (fetchAll) {
      fetchAll();
    }
  }, [fetchAll]);

  const downloadFile = (id) => () => {
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
    <DataTable
      title="Batch Uploads"
      columns={[
        batchId,
        fileName,
        totalCount,
        processedCount,
        uploadedOn,
        status,
        actions({ onClick: downloadFile }),
      ]}
      EmptyComponent={emptyComponent(null, null, 'No address files uploaded yet.')}
      {...props}
    />
  );
};

const mapStateToProps = (state) => {
  return { mode: state.session.mode, user: state.session.user, ...state.addressbatches };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchAll: fetchAllAddressBatches,
      createAddressBatch,
      batchDownload,
      ...NotificationsActions,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(BatchListContainer);
