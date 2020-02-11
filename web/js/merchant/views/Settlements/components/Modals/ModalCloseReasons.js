import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';
import Button from 'common/new-ui/Button';
import { CLOSE_OPTIONS } from 'merchant/views/Settlements/data';

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
      <div class="reasons-close-modal">
        <ModalHeader
          class="header"
          title="Reason"
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div class="modal-body">
          {CLOSE_OPTIONS.map(choice => {
            return (
              <div
                key={'parent-choice-' + choice.value}
                class="es-close-choices"
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
          class="pull-right confirm-close"
        >
          Confirm & Close
        </Button.Primary>
      </div>
    );
  }
}
