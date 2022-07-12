import { Component } from 'react';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { withRouter } from 'react-router-dom';
import { isEmpty, isPlainObject } from 'lodash';
import qs from 'query-string';

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
class ModalDialog extends Component {
  _prevQueryParams = null;

  addQueryParams = (queryParams) => {
    const params = qs.parse(this.props.location.search);
    Object.entries(queryParams).forEach(([queryParamKey, queryParamValue]) => {
      params[queryParamKey] = queryParamValue;
    });
    this.props.history.replace({ search: qs.stringify(params) });
  };

  removeQueryParams = (queryParams) => {
    const params = qs.parse(this.props.location.search);
    Object.keys(queryParams).forEach((queryParamKey) => {
      delete params[queryParamKey];
    });
    this.props.history.replace({ search: isEmpty(params) ? '' : qs.stringify(params) });
  };

  onModalOpen = () => {
    const { queryParams } = this.props;
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
  };

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
          onAfterClose={this.onModalClose}
          onAfterOpen={this.onModalOpen}
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

export default withRouter(ModalDialog);
