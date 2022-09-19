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
          message: 'RTO history file uploaded successfully.',
        });
        closeModal();
      })
      .catch(() => {
        setIsCtaDisabled(false);
        showNotification({
          type: 'error',
          message: 'RTO history file upload unsuccessful. Please Try again.',
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
