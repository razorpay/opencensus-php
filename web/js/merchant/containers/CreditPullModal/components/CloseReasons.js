import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';
import Button from 'common/new-ui/Button';

@connect((state) => ({ user: state.session.user }), {
  closeModal,
})
export default class CloseReasons extends Component {
  constructor(props) {
    super(props);
    this.state = {
      closeReason: '',
    };
  }

  handleReasonChange = (e) => {
    this.setState({ closeReason: e.target.value });
  };

  submitCloseReason = () => {
    const { eventCategory, eventAction } = this.props;
    const analyticsPayload = {
      eventCategory,
      eventAction,
      eventLabel: `Reason - ${this.state.closeReason}`,
    };
    window.rzpAnalytics?.(analyticsPayload);
    this.props.closeModal();
  };

  render() {
    return (
      <div className="reasons-close-modal">
        <ModalHeader
          class="header"
          title="Reason"
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div className="modal-body">
          {this.props.closeReasons.map((choice) => {
            return (
              <div key={`parent-choice-${choice}`}>
                <label key={`label-${choice}`}>
                  <input
                    type="radio"
                    name="close-reason"
                    value={choice}
                    key={`inp-choice${choice}`}
                    onChange={this.handleReasonChange}
                  />
                  {choice}
                </label>
              </div>
            );
          })}
        </div>
        <div className="modal-footer">
          <Button.Primary
            onClick={this.submitCloseReason}
            disabled={!this.state.closeReason}
            className="pull-right"
          >
            Confirm & Close
          </Button.Primary>
        </div>
      </div>
    );
  }
}
