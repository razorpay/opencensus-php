import React, { Component } from 'react';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import CreditPullModal from '../index';

class CreditPullClose extends Component {
  constructor(props) {
    super(props);
    this.state = {};
  }

  titleGenerator = () => {
    return (
      <span>
        <i className="i i-error" /> {this.props.closeTitle ? this.props.closeTitle : 'Oops!'}
      </span>
    );
  };

  render() {
    return (
      <div className="credit-pull-failure-container">
        <ModalHeader
          title={this.titleGenerator()}
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div className="modal-body">{this.props.message}</div>
        <div className="try-button">
          <AsyncButton
            type="submit"
            className="btn btn-primary"
            text="Try Again"
            onClick={() => {
              this.props.openModal({
                component: <CreditPullModal fromWhere="Retry attempt" />,
                size: 'regular',
              });
            }}
          />
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({ user: state.session.user }), {
    closeModal,
    openModal,
  }),
)(CreditPullClose);
