import React, { Component } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';

export default class CancelWithdrawalReasons extends Component {
  constructor(props) {
    super(props);
    this.state = {
      closeReason: '',
      reasonDescription: '',
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
      eventLabel: `
      Reason - ${this.state.closeReason}
      ${this.state.reasonDescription ? `Description - ${this.state.reasonDescription}` : ''}
      `,
    };
    window.rzpAnalytics?.(analyticsPayload);
    this.props.onClose();
  };

  render() {
    return (
      <div className="reasons-close-modal">
        <ModalHeader class="header" title="Reason" onCloseClick={() => {}} />
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
          <div class="m-t">
            <strong>Description</strong>
            <textarea
              value={this.state.reasonDescription}
              onChange={(e) => {
                this.setState({
                  reasonDescription: e.target.value,
                });
              }}
              placeholder="Write a brief description "
              className="form-control"
            />
          </div>
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
