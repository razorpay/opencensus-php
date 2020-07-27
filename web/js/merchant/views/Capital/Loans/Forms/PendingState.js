import React, { Component } from 'react';
import Note from '../../components/Note';
import { changeActiveState } from 'merchant/reducers/capital';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import { isPreceedingState } from '../../utils';
import getApplicationProgressPercentage from '../../utils/ProgressPercentageCalculator';
import { APPLICATION_STATE_DESCRIPTIONS } from '../constants';

@connect(
  state => ({
    currentState: state.loanApplicationDetails.meta.data.application.status,
    activeState: state.loanApplicationDetails.context.activeState,
    applicationId: state.loanApplicationDetails.meta.data.application.id,
  }),
  {
    changeActiveState,
  }
)
class PendingState extends Component {
  gaEventDispatcher = eventObject => {
    eventObject['eventCategory'] = 'Dashboard - WCL LOS';
    window.rzpAnalytics(eventObject);
  };

  _trackSupportClick = () => {
    const { activeState } = this.props;

    this.gaEventDispatcher({
      eventAction: 'Reach Support Cta Clicked',
      eventLabel: `${
        APPLICATION_STATE_DESCRIPTIONS[activeState].short_description
      } | ${getApplicationProgressPercentage(activeState)}%`,
    });
  };

  render() {
    const {
      backState,
      message,
      showNavigation,
      nextState,
      currentState,
      applicationId,
    } = this.props;
    return (
      <div>
        <Note
          message={message}
          applicationId={applicationId}
          _trackSupportClick={this._trackSupportClick}
        />
        {showNavigation && (
          <div className="actions p-r pull-right m-r">
            {backState && (
              <Button.Transparent
                onClick={() => {
                  this.gaEventDispatcher({
                    eventAction: 'Application | Back',
                    eventLabel: `${
                      APPLICATION_STATE_DESCRIPTIONS[this.props.activeState]
                        .short_description
                    } | ${getApplicationProgressPercentage(currentState)}%`,
                  });
                  this.props.changeActiveState(backState);
                }}
              >
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
            )}
            {!isPreceedingState(currentState, nextState) && (
              <Button.Primary
                onClick={() => {
                  this.gaEventDispatcher({
                    eventAction: 'Application | Next',
                    eventLabel: `${
                      APPLICATION_STATE_DESCRIPTIONS[this.props.activeState]
                        .short_description
                    } | ${getApplicationProgressPercentage(currentState)}%`,
                  });
                  this.props.changeActiveState(nextState);
                }}
              >
                Next
                <i className="i i-chevron-right" />
              </Button.Primary>
            )}
            {/*<Button.Transparent>*/}
            {/*  Close*/}
            {/*  <i className="i i-chevron-right"/>*/}
            {/*</Button.Transparent>*/}
          </div>
        )}
      </div>
    );
  }
}

export default PendingState;
