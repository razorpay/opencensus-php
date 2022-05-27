import { Component } from 'react';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

Object.assign(Modal.defaultStyles.overlay, {
  backgroundColor: 'rgba(58, 63, 81, 0.8)',
  zIndex: 9999,
  overflowY: 'auto',
  display: 'flex',
  justifyContent: 'center',
  alignItems: 'center',
});

Modal.defaultStyles.content = {
  width: '625px',
  margin: '65px auto',
  padding: 0,
  border: 0,
  boxShadow: '0 5px 15px rgba(0,0,0,0.5)',
  backgroundColor: 'rgb(255, 255, 255)',
  borderRadius: '5px',
};

@connect((state) => ({ ...state.modal, org: state.session.org }), ModalActions)
export default class ModalDialog extends Component {
  render() {
    const props = this.props;

    // to apply the styles passed as props
    Object.assign(Modal.defaultStyles.overlay, props.overlayStyles);

    return (
      <div>
        <Modal
          isOpen={!!props.component}
          onRequestClose={props.disableClose ? null : props.closeModal}
          closeTimeoutMS={300}
          shouldCloseOnOverlayClick={false}
          class={`${props.org?.custom_code} Modal ${props.size ? `Modal--${props.size}` : ''}${
            props.className ? ` ${props.className}` : ''
          }`}
          contentLabel="Modal"
          ariaHideApp={false}
        >
          <ErrorBoundary resetOnProps>{props.component}</ErrorBoundary>
        </Modal>
      </div>
    );
  }
}

ModalDialog.defaultProps = {
  size: 'regular',
  disableClose: false,
  overlayStyles: {},
};
