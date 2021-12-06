import PropTypes from 'prop-types';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

const ConfirmModal = (props) => {
  const confirmModelStyle = {
    overlay: { ...Modal.defaultStyles.overlay, zIndex: 10000 },
    content: { ...Modal.defaultStyles.content, width: '325px' },
  };
  const { header, message, className } = props.options;

  return (
    <div>
      <Modal
        isOpen={props.show}
        style={confirmModelStyle}
        onRequestClose={props.onAbort}
        closeTimeoutMS={300}
        class={`${props.org.custom_code} Modal Modal--small Modal--confirm ${className}`}
        contentLabel="ConfirmModal"
        ariaHideApp={false}
      >
        <div class="modal-header">
          <h3 class="modal-title">{typeof header === 'function' ? header() : header || 'Alert'}</h3>
        </div>

        <div class="modal-body">
          {typeof message === 'function' ? message() : <p>{message}</p>}

          <div class="Modal__actions">
            <button type="button" class="btn btn-outline" onClick={props.onAbort}>
              {props.options.abortLabel}
            </button>
            <AsyncButton
              type="button"
              class="btn btn-primary"
              onClick={props.onAffirm}
              text={props.options.affirmativeLabel}
              pendingText={props.options.affirmativePendingLabel}
            />
          </div>
        </div>
      </Modal>
    </div>
  );
};

ConfirmModal.defaultProps = {
  show: false,
  abortLabel: 'Cancel',
  affirmativeLabel: 'OK',
};

ConfirmModal.propTypes = {
  show: PropTypes.bool,
  options: PropTypes.object,
  onAbort: PropTypes.func,
  onAffirm: PropTypes.func,
  abortLabel: PropTypes.string,
  affirmativeLabel: PropTypes.string,
};

const mapStateToProps = (state) => {
  return {
    org: state.session.org,
  };
};

export default connect(mapStateToProps, null)(ConfirmModal);
