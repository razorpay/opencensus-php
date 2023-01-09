import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Button from 'common/new-ui/Button';
import {
  createRTOHistoryBatches,
  resetFileId,
} from 'merchant/reducers/magicCheckout/rtoHistoryUpload/action';
import { closeModal as closeModalAction } from 'merchant_common/reducers/modals';
import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import { NOTIFICATION_MESSAGES } from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/constants';

const ModalActions = (props) => {
  const { fileId, provider, createBatch, closeModal, resetFileId, showNotification } = props;

  const [isCtaDisabled, setIsCtaDisabled] = useState(!fileId);
  const disableClass = isCtaDisabled ? ' disabled' : '';

  const onModalConfirm = useCallback(() => {
    setIsCtaDisabled(true);
    createBatch({
      file_id: fileId,
      shipping_provider: provider,
    })
      .then(() => {
        resetFileId(null);
        showNotification({
          type: 'success',
          message: NOTIFICATION_MESSAGES.success,
        });
        closeModal();
      })
      .catch((err) => {
        setIsCtaDisabled(false);
        showNotification({
          type: 'error',
          message:
            err?.errors?.[0]?.indexOf('merchant_upload_time_period_expired_error') > -1
              ? NOTIFICATION_MESSAGES.error.time_expired
              : NOTIFICATION_MESSAGES.error.default,
        });
      });
  }, [fileId, provider, setIsCtaDisabled, resetFileId, showNotification]);

  useEffect(() => {
    setIsCtaDisabled(!fileId);
  }, [fileId]);

  return (
    <div className="modal-actions">
      <Button type="button" onClick={closeModal} className="cancel-cta">
        Cancel
      </Button>
      <Button.Primary
        type="button"
        onClick={onModalConfirm}
        className={`confirm-cta${disableClass}`}
        disabled={isCtaDisabled}
      >
        Confirm
      </Button.Primary>
    </div>
  );
};

const mapStateToProps = (state) => ({
  fileId: state.rtoHistoryUpload.fileId,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      createBatch: createRTOHistoryBatches,
      resetFileId,
      closeModal: closeModalAction,
      showNotification: displayNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ModalActions);
