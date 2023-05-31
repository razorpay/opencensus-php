import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import Button from 'common/new-ui/Button';
import { CLOSE_OPTIONS } from 'merchant/views/Settlements/Settlements/data';
import Input from 'common/new-ui/Input';
import {
  trackEsChurnReason,
  trackEsModalCloseAction,
} from 'merchant/views/Settlements/Settlements/ga';
import {
  trackSettleNowCloseReason,
  trackSettleNowConfirmClose,
} from 'merchant/views/Settlements/trackEvents';
import { bindActionCreators } from 'redux';
import Nudge from './ScheduledModal/components/Nudge';
import { ISPlusPlusReasons } from './IsPlusPlusModal';

class ModalCloseReasons extends Component {
  constructor(props) {
    super(props);
    this.state = {
      closeReason: '',
      brief: '',
    };
  }

  handleReasonChange = (e) => {
    this.setState({ closeReason: e.target.value });
  };

  handleBriefChange = (e) => {
    this.setState({ brief: e.target.value });
  };

  submitCloseReason = () => {
    const { brief, closeReason } = this.state;
    const { user, fromWhere, closeOrigin } = this.props;
    const analyticsPayload = {
      eventCategory: this.props.eventCategory,
      eventAction: `Reasons - ${this.props.closeOrigin}`,
      eventLabel: `${closeReason} | ${brief}`,
      eventValue: brief,
    };
    window.rzpAnalytics?.(analyticsPayload);
    trackEsChurnReason(user.current, `${closeReason}${brief ? ` | Description - ${brief}` : ''}`);
    trackEsModalCloseAction(user.current, true);
    if (closeOrigin === 'OnDemand') {
      const label =
        CLOSE_OPTIONS[CLOSE_OPTIONS.findIndex((each) => each.value === closeReason)].label;
      const desc = brief ? `| ${brief}` : '';
      trackSettleNowCloseReason(fromWhere, `${label} ${desc}`);
      trackSettleNowConfirmClose(fromWhere);
    }
    this.props.closeModal();
  };

  handleGoBackClick = (e) => {
    const { goBackToInitialModalView, closeModal } = this.props;
    trackEsModalCloseAction(this.props.user.current);
    closeModal();
    setTimeout(() => goBackToInitialModalView && goBackToInitialModalView(e), 0);
  };

  render() {
    const { brief } = this.state;
    const { user, openModal, closeOrigin, showISPlusPlus, fromWhere, onFinish } = this.props;

    if (showISPlusPlus) {
      return <ISPlusPlusReasons onFinish={onFinish} fromWhere={fromWhere} />;
    }
    return (
      <div class="reasons-close-modal">
        <ModalHeader class="header" title="Reason" />
        <div class="modal-body">
          {CLOSE_OPTIONS.map((choice) => {
            return (
              <div key={`parent-choice-${choice.value}`} class="es-close-choices">
                <label key={`lab-${choice.value}`}>
                  <input
                    type="radio"
                    name="close-reason"
                    value={choice.value}
                    key={`inp-choice ${choice.value}`}
                    onChange={this.handleReasonChange}
                  />
                  {choice.label}
                </label>
              </div>
            );
          })}
        </div>
        <div class="flex Input-textarea-container">
          <Input.Textarea
            label="Write a brief"
            size="small"
            class="Input-description Input--vTop m-b p-b"
            placeholder="Write a brief description"
            value={brief}
            onChange={this.handleBriefChange}
          />
        </div>

        <Nudge user={user} openModal={openModal} closeOrigin={closeOrigin} />

        <div class="flex action-container">
          <Button.Transparent onClick={this.handleGoBackClick} class="go-back">
            Go Back
          </Button.Transparent>
          <Button.Primary
            onClick={this.submitCloseReason}
            disabled={!this.state.closeReason}
            class="confirm-close"
          >
            Confirm & Close
          </Button.Primary>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal: fnCloseModal }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(ModalCloseReasons);
