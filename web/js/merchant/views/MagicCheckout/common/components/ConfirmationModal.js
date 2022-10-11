import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';

export const DisplayNotificationTxt = ({ notificationTxt }) => (
  <>
    <i className="i i-info-outline magicNotification font-normal" />
    <p className="toastTxt">{notificationTxt}</p>
  </>
);

const ConfirmationModal = (props) => {
  const { header, subText, desc, affirmativeLabel, abortLabel, onAffirm, closeModal } = props;

  return (
    <div className="confirmation-modal">
      <ModalHeader title={header} extraClass="no-padding" onCloseClick={closeModal} />
      <div className="font-bold confirmation-modal-subtext">{subText}</div>
      <div className="confirmation-modal-desc">{desc}</div>
      <div className="confirmation-modal-ctas-container">
        <div
          className="pointer confirmation-modal-secondary-cta display-inline"
          onClick={closeModal}
        >
          {abortLabel}
        </div>
        <div
          className="pointer color-white confirmation-modal-primary-cta display-inline"
          onClick={onAffirm}
        >
          {affirmativeLabel}
        </div>
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
