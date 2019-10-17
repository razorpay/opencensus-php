import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import { openModal, closeModal } from 'rzp/modules/modals';
import AsyncButton from 'react-async-button';
import CreditPullModal from './CreditPullModal';

@connect(state => ({ user: state.session.user }), {
  closeModal,
  openModal,
})
export default class CreditPullClose extends Component {
  constructor(props) {
    super(props);
    this.state = {};
  }

  titleGenerator = () => {
    return (
      <span>
        <i className="i i-error" />{' '}
        {this.props.closeTitle ? this.props.closeTitle : 'Oops!'}
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
            class="btn btn-primary"
            text={'Try Again'}
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
