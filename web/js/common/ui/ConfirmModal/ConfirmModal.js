import PropTypes from 'prop-types';
import AsyncButton from 'react-async-button';
import Modal from 'react-modal';
import { connect } from 'react-redux';

import { delay } from 'common/utils/timeout';

const MODAL_CLOSE_TIMEOUT_MS = 300;
const ConfirmModal = (props) => {
  const confirmModelStyle = {
    overlay: { ...Modal.defaultStyles.overlay, zIndex: 10000 },
    content: { ...Modal.defaultStyles.content, width: '325px' },
  };
  const { header, message, className } = props.options;

  const onAffirmClick = async () => {
    await props.onAffirm();
    // Keep the button disabled until the animation closes
    await delay(MODAL_CLOSE_TIMEOUT_MS);
  };

  return (
    <div>
      <Modal
        isOpen={props.show}
        style={confirmModelStyle}
        onRequestClose={props.onAbort}
        closeTimeoutMS={MODAL_CLOSE_TIMEOUT_MS}
        className={`${
          props?.org?.custom_code || ''
        } Modal Modal--small Modal--confirm ${className}`}
        contentLabel="ConfirmModal"
        ariaHideApp={false}
      >
        <div className="modal-header">
          <h3 className="modal-title">
            {typeof header === 'function' ? header() : header || 'Alert'}
          </h3>
        </div>

        <div className="modal-body">
          {typeof message === 'function' ? message() : <p>{message}</p>}

          {!props.options.hideActions && (
            <div className="Modal__actions">
              <button type="button" className="btn btn-outline" onClick={props.onAbort}>
                {props.options.abortLabel}
              </button>
              <AsyncButton
                type="button"
                className="btn btn-primary"
                onClick={onAffirmClick}
                text={props.options.affirmativeLabel}
                pendingText={props.options.affirmativePendingLabel}
              />
            </div>
          )}
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
