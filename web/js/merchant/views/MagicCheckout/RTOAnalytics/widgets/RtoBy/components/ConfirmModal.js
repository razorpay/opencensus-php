import { connect } from 'react-redux';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import ModalHeader from 'common/ui/ModalHeader';

const ConfirmModal = ({
  value,
  isBlocked,
  closeModal,
  modalSource,
  onConfirmClick,
  ipLoader,
  zipcodeLoader,
}) => {
  const actionType = isBlocked ? 'unblock' : 'block';
  return (
    <>
      <ModalHeader
        title={
          <>
            <span>{actionType}</span> selected {modalSource}?
          </>
        }
        onCloseClick={closeModal}
      />
      <div className="modal-body">
        <span>
          COD option for all orders from{' '}
          <strong>
            {modalSource}: {value}
          </strong>{' '}
          will
          {isBlocked ? ' not' : ''} be automatically blocked.
        </span>
        <div className="Modal__actions">
          <Button.Transparent type="button" onClick={closeModal}>
            Don't {actionType}
          </Button.Transparent>
          <AsyncBtn.Primary
            className="btn-primary"
            type="button"
            isPending={modalSource === 'pincode' ? zipcodeLoader : ipLoader}
            onClick={() => onConfirmClick(value, modalSource)}
          >
            Yes, {actionType}
          </AsyncBtn.Primary>
        </div>
      </div>
    </>
  );
};

const mapStateToProps = (state) => ({
  ipLoader: state.magicRTOAnalytics.rto_by_ip.modalLoading,
  zipcodeLoader: state.magicRTOAnalytics.rto_by_zipcode.modalLoading,
});

export default connect(mapStateToProps, null)(ConfirmModal);
