import { Component } from 'react';
import { isEmpty, isPlainObject } from 'lodash';
import qs from 'query-string';
import Modal from 'react-modal';
import { compose } from 'redux';
import { withZustand } from 'shell/commonStore';

import { withRouter } from 'common/deprecated/withRouter';
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

class ModalDialog extends Component {
  _prevQueryParams = null;
  defaultOverlayStyle = { ...Modal.defaultStyles.overlay };

  replacePathName = ({ search }) => {
    const newPath = this.props.history.location.pathname + (search ? `?${search}` : '');
    this.props.history.replace(newPath);
  };

  addQueryParams = (queryParams) => {
    const params = qs.parse(this.props.location.search);
    Object.entries(queryParams).forEach(([queryParamKey, queryParamValue]) => {
      params[queryParamKey] = queryParamValue;
    });
    this.replacePathName({ search: qs.stringify(params) });
  };

  removeQueryParams = (queryParams) => {
    const params = qs.parse(this.props.location.search);
    Object.keys(queryParams).forEach((queryParamKey) => {
      delete params[queryParamKey];
    });
    this.replacePathName({ search: isEmpty(params) ? '' : qs.stringify(params) });
  };

  onModalOpen = () => {
    const {
      modal: { queryParams },
    } = this.props.store;
    if (isPlainObject(queryParams) && !isEmpty(queryParams)) {
      this._prevQueryParams = queryParams;
      this.addQueryParams(queryParams);
    }
  };

  onModalClose = () => {
    // Modal is closed by clearing all the props (props.component, props.queryParams etc).
    // Hence queryParams is stored in _prevQueryParams onModalOpen
    if (this._prevQueryParams) {
      this.removeQueryParams(this._prevQueryParams);
      this._prevQueryParams = null;
    }
    // reset the default overlay style when modal closes as it is getting modified by reference
    Modal.defaultStyles.overlay = { ...this.defaultOverlayStyle };
  };

  render() {
    const props = this.props;
    const {
      session: { org },
      closeModal,
      modal: {
        component,
        overlayStyles,
        size,
        closeOnOverLay = false,
        isNew,
        disableClose,
        className,
      } = {},
    } = props.store || {};

    // to apply the styles passed as props
    Object.assign(Modal.defaultStyles.overlay, overlayStyles);

    /* 
      use your own modal component & the modal reducer for opening closing modal
      added as we're moving to styled components and the below modal adds multiple styles of its own
      openModal -> Opens the modal
      closeModal -> Closes the modal
    */
    if (isNew) {
      return !!component && component;
    }

    return (
      <div>
        <Modal
          isOpen={!!component}
          onRequestClose={disableClose ? null : closeModal}
          closeTimeoutMS={300}
          shouldCloseOnOverlayClick={closeOnOverLay}
          class={`${org?.custom_code} Modal ${size ? `Modal--${size}` : ''}${
            className ? ` ${className}` : ''
          }`}
          contentLabel="Modal"
          ariaHideApp={false}
          onAfterClose={this.onModalClose}
          onAfterOpen={this.onModalOpen}
        >
          <ErrorBoundary resetOnProps>{component}</ErrorBoundary>
        </Modal>
      </div>
    );
  }
}

ModalDialog.defaultProps = {
  store: {
    modal: {
      size: 'regular',
      disableClose: false,
      overlayStyles: {},
    },
  },
};

export default compose(withRouter, (Component) =>
  withZustand(Component, ['modal', 'closeModal', 'session']),
)(ModalDialog);
