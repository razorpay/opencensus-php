import React, { Component } from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';

import Note from '../../components/Note';
import { isPreceedingState } from '../../utils';
import getApplicationProgressPercentage from '../../utils/ProgressPercentageCalculator';
import { GA_CATEGORY_BY_PRODUCT } from '../constants';

class PendingState extends Component {
  gaEventDispatcher = (eventObject) => {
    eventObject.eventCategory = GA_CATEGORY_BY_PRODUCT[this.props.product];
    window.rzpAnalytics?.(eventObject);
  };

  _trackSupportClick = () => {
    const { activeState, configuration } = this.props;

    this.gaEventDispatcher({
      eventAction: 'Reach Support Cta Clicked',
      eventLabel: `${
        configuration.getApplicationStateDescriptions()[activeState].short_description
      } | ${getApplicationProgressPercentage(
        activeState,
        configuration.getApplicationStateGroups(),
      )}%`,
    });
  };

  render() {
    const { message, showNavigation, currentState, applicationId, navigation, configuration } =
      this.props;
    return (
      <div className="pending-note-wrapper">
        <Note
          message={message}
          applicationId={applicationId}
          product={this.props.product}
          _trackSupportClick={this._trackSupportClick}
        />
        {showNavigation && (
          <div className="actions pull-right">
            <Button.Transparent
              onClick={() => {
                this.gaEventDispatcher({
                  eventAction: 'Application | Back',
                  eventLabel: `${
                    configuration.getApplicationStateDescriptions()[this.props.activeState]
                      .short_description
                  } | ${getApplicationProgressPercentage(
                    currentState,
                    configuration.getApplicationStateGroups(),
                  )}%`,
                });
                navigation.back();
              }}
            >
              <i className="i i-chevron-left" />
              Back
            </Button.Transparent>
            {!isPreceedingState(currentState, this.props.activeState) && (
              <Button.Primary
                onClick={() => {
                  this.gaEventDispatcher({
                    eventAction: 'Application | Next',
                    eventLabel: `${
                      configuration.getApplicationStateDescriptions()[this.props.activeState]
                        .short_description
                    } | ${getApplicationProgressPercentage(
                      currentState,
                      configuration.getApplicationStateGroups(),
                    )}%`,
                  });
                  navigation.next();
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

export default connect((state) => ({
  currentState: state.loanApplicationDetails.meta.data.application.status,
  configuration: state.loanApplicationDetails.meta.configuration,
  activeState: state.loanApplicationDetails.context.activeState,
  applicationId: state.loanApplicationDetails.meta.data.application.id,
  product: state.loanApplicationDetails.meta.product,
}))(PendingState);
