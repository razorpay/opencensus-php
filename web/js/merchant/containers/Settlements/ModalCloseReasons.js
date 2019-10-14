import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal } from 'rzp/modules/modals';
import Button from 'component/Button';
import { CLOSEOPTIONS } from './CloseReasons';

@connect(state => ({ user: state.session.user }), {
  closeModal,
})
export default class ModalCloseReasons extends Component {
  constructor(props) {
    super(props);
    this.state = {
      closeReason: '',
    };
  }

  handleReasonChange = e => {
    this.setState({ closeReason: e.target.value });
  };

  submitCloseReason = () => {
    const analyticsPayload = {
      eventCategory: 'Dashboard - Early Settlement',
      eventAction: `Reasons - ${this.props.closeOrigin}`,
      eventLabel: `Reason - ${this.state.closeReason} - ${
        this.props.closeOrigin
      }`,
    };

    window.rzpAnalytics(analyticsPayload);
    this.props.closeModal();
  };

  render() {
    return (
      <div className="onmdemand-close-modal">
        <ModalHeader
          class="header"
          title="Reason"
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div className="modal-body">
          {CLOSEOPTIONS.map(choice => {
            return (
              <div
                key={'parent-choice-' + choice.value}
                className="es-close-choices"
              >
                <label key={'lab-' + choice.value}>
                  <input
                    type="radio"
                    name="close-reason"
                    value={choice.value}
                    key={'inp-choice' + choice.value}
                    onChange={this.handleReasonChange}
                  />
                  {choice.label}
                </label>
              </div>
            );
          })}
        </div>
        <Button.Primary
          onClick={this.submitCloseReason}
          disabled={!this.state.closeReason}
          className="pull-right confirm-close"
        >
          Confirm & Close
        </Button.Primary>
      </div>
    );
  }
}
