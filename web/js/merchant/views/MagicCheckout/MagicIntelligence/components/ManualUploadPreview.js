import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { AsyncBtn } from 'common/new-ui/Button';
import Table from 'common/ui/Table/Index';
import {
  NOTIFICATION_MESSAGES,
  ATTRIBUTE_TYPE,
} from 'merchant/views/MagicCheckout/MagicIntelligence/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

const Type = { title: 'Type', value: (data) => ATTRIBUTE_TYPE[data.attribute_type] };
const Value = { title: 'Value', value: (data) => data.attribute_value };
const Count = { title: 'Count', value: (data) => data.count };

const ManualUploadPreview = (props) => {
  const {
    fetchAll,
    data,
    onModalClose,
    uploadList,
    modalSource,
    showNotification,
    blocklist,
    allowlist,
    resetHandler,
    dataPreview,
  } = props;

  const uploadItems = () => {
    uploadList({ cod_eligibility_attributes: data })
      .then(() => {
        resetHandler();
        fetchAll({
          count: 25,
        })
          .then(() => {
            showNotification({
              type: 'success',
              message: NOTIFICATION_MESSAGES.UPLOAD_SUCCESSFUL,
            });
            onModalClose();
          })
          .catch(() => {
            showNotification({
              type: 'error',
              message: NOTIFICATION_MESSAGES.FETCH_ERROR,
            });
            onModalClose();
          });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_MESSAGES.UPLOAD_ERROR,
        });
        onModalClose();
      });
  };

  return (
    <>
      <div className="modal-header">
        <h3 className="modal-title">{`Add items to ${modalSource}`} </h3>
      </div>
      <div className="modal-body">
        <Table rows={dataPreview} columns={[Count, Type, Value]} className="table-striped" />
      </div>
      <div className="modal-footer">
        <AsyncBtn type="button" onClick={onModalClose}>
          Cancel
        </AsyncBtn>
        <AsyncBtn.Primary
          type="button"
          isPending={modalSource === 'Blocklist' ? blocklist.loading : allowlist.loading}
          onClick={uploadItems}
        >
          {`Add To ${modalSource}`}
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

const mapStateToProps = (state) => {
  return { blocklist: { ...state.magicBlocklist }, allowlist: { ...state.magicAllowlist } };
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ showNotification }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ManualUploadPreview);
