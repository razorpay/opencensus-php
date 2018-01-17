import { PropTypes } from 'react';
import Modal from 'react-modal';
import AsyncButton from 'react-async-button';

const ConfirmModal = (props, context) => {
  let confirmModelStyle = {
    overlay: Object.assign({}, Modal.defaultStyles.overlay, {
      zIndex: 10000,
    }),
    content: Object.assign({}, Modal.defaultStyles.content, {
      width: '325px',
    }),
  };
  let { header, message } = props.options;

  return (
    <div>
      <Modal
        isOpen={props.show}
        style={confirmModelStyle}
        onRequestClose={props.onAbort}
        closeTimeoutMS={300}
        class={`Modal Modal--small Modal--confirm`}
        contentLabel="ConfirmModal"
      >
        <div class="modal-header">
          <h3 class="modal-title">
            {typeof header === 'function' ? header() : header || 'Alert'}
          </h3>
        </div>

        <div class="modal-body">
          {typeof message === 'function' ? message() : <p>{message}</p>}

          <div class="Modal__actions">
            <button
              type="button"
              class="btn btn-default"
              onClick={props.onAbort}
            >
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
  show: PropTypes.bool.isRequired,
  options: PropTypes.object,
  onAbort: PropTypes.func,
  onAffirm: PropTypes.func,
};

export default ConfirmModal;
