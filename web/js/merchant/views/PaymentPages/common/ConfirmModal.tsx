import React from 'react';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { DASHBOARD_ZINDEX_MAP } from '@libs/shared-utils';

type FunctionElement = () => any;

interface IProps {
  onAbort: () => void;
  onAffirm: () => void | Promise<void>;
  header: FunctionElement | string;
  message: FunctionElement | string;
  affirmativeLabel?: string;
  affirmativePendingLabel?: string;
  abortLabel?: string;
  org?: {
    custom_code?: string;
  };
  className?: string;
  hideActions?: boolean;
}

const ConfirmModal = (props: IProps) => {
  const confirmModelStyle = {
    overlay: { ...Modal.defaultStyles.overlay, zIndex: DASHBOARD_ZINDEX_MAP.dropdownOverlay },
    content: { ...Modal.defaultStyles.content, width: '325px' },
  };
  const {
    header,
    message,
    className,
    abortLabel = 'Cancel',
    affirmativeLabel = 'OK',
    affirmativePendingLabel = 'Saving',
    // eslint-disable-next-line @typescript-eslint/naming-convention
    hideActions,
    org,
    onAbort,
    onAffirm,
  } = props;

  return (
    <Modal
      isOpen={true}
      style={confirmModelStyle}
      onRequestClose={onAbort}
      closeTimeoutMS={300}
      className={`${org?.custom_code || ''} Modal Modal--small Modal--confirm ${className}`}
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

        {!hideActions && (
          <div className="Modal__actions">
            <button type="button" className="btn btn-outline" onClick={onAbort}>
              {abortLabel}
            </button>
            <AsyncButton
              type="button"
              className="btn btn-primary"
              onClick={onAffirm}
              text={affirmativeLabel}
              pendingText={affirmativePendingLabel}
            />
          </div>
        )}
      </div>
    </Modal>
  );
};

const mapStateToProps = (state) => {
  return {
    org: state.session.org,
  };
};

export default connect(mapStateToProps, null)(ConfirmModal);
