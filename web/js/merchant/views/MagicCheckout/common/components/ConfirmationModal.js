import { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import { closeModal } from 'merchant_common/reducers/modals';

export const DisplayNotificationTxt = ({ notificationTxt }) => (
  <>
    <i className="i i-info-outline magicNotification font-normal" />
    <p className="toastTxt">{notificationTxt}</p>
  </>
);

const ConfirmationModal = (props) => {
  const { header, subText, desc, affirmativeLabel, abortLabel, onAffirm, closeModal, onAbort } =
    props;
  const [disableCta, setDisableCta] = useState(false);

  const onConfirm = () => {
    setDisableCta(true);
    onAffirm();
  };

  const handleClick = () => (typeof onAbort === 'function' ? onAbort() : closeModal());

  return (
    <div className="confirmation-modal">
      <ModalHeader title={header} extraClass="no-padding" onCloseClick={closeModal} />
      <div className="font-bold confirmation-modal-subtext">{subText}</div>
      <div className="confirmation-modal-desc">{desc}</div>
      <div className="confirmation-modal-ctas-container">
        {abortLabel ? (
          <Button type="button" className="confirmation-modal-secondary-cta" onClick={handleClick}>
            {abortLabel}
          </Button>
        ) : null}
        <Button
          type="button"
          className="confirmation-modal-primary-cta"
          onClick={onConfirm}
          disabled={disableCta}
        >
          {affirmativeLabel}
        </Button>
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ConfirmationModal);
