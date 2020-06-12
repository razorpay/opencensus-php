import React, { Component } from 'react';
import MultiLevelStepper from 'merchant/views/Capital/components/MultiLevelStepper';
import { connect } from 'react-redux';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';
import {
  APPLICATION_STATE_DESCRIPTIONS,
  APPLICATION_STATE_GROUPS,
  CONSOLIDATED_STATE_SEQUENCE,
  ERROR_STATES,
  PENDING_APPLICATION_STATES,
  STATE_GROUP_COMPLETION_DESCRIPTION,
} from './constants';

@connect(
  state => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    fetchLoanApplicationMeta,
  }
)
class ApplicationStatusOverview extends Component {
  constructor() {
    super();
    this.stepFound = false;
  }
  getStepStatus = () => {};

  componentDidUpdate(prevProps, prevState, snapshot) {
    this.stepFound = false;
  }

  getStateType = applicationStatus => {
    const isPendingState = PENDING_APPLICATION_STATES.includes(
      applicationStatus
    );
    const isErrorState = PENDING_APPLICATION_STATES.includes(applicationStatus);
    if (isPendingState) {
      return 'pending';
    } else if (isErrorState) {
      return 'error';
    } else {
      return 'normal';
    }
  };

  viewCompletedStateGroup = parentStep => {
    const { meta } = this.props.loanApplicationDetails;

    const targetStep = APPLICATION_STATE_GROUPS[parentStep][0];
    this.props.openLoanEntity(meta.data.application.id, targetStep);
  };

  viewCurrentState = () => {
    const { meta } = this.props.loanApplicationDetails;

    this.props.openLoanEntity(meta.data.application.id);
  };

  getStepTobeShown = (classList, step) => {
    const { meta } = this.props.loanApplicationDetails;
    const applicationStatus = meta.data.application.status;
    if (classList.includes('active')) {
      return APPLICATION_STATE_DESCRIPTIONS[applicationStatus];
    } else if (classList.includes('completed')) {
      return APPLICATION_STATE_DESCRIPTIONS[
        APPLICATION_STATE_GROUPS[step].slice(-1)
      ];
    } else if (classList.includes('not_started')) {
      return APPLICATION_STATE_DESCRIPTIONS[APPLICATION_STATE_GROUPS[step][0]];
    }
  };

  getStep = step => {
    const { meta } = this.props.loanApplicationDetails;
    const applicationStatus = meta.data.application.status;

    const isCurrentStateGroup = APPLICATION_STATE_GROUPS[step].includes(
      applicationStatus
    );

    //both cannot be true
    const isPendingState =
      isCurrentStateGroup &&
      PENDING_APPLICATION_STATES.includes(applicationStatus);
    const isErrorState =
      isCurrentStateGroup && ERROR_STATES.includes(applicationStatus);

    const classList = [
      ...(isCurrentStateGroup
        ? ['active', 'highlight']
        : this.stepFound
        ? ['not_started']
        : ['completed']),
      ...(isPendingState ? ['pending'] : []),
      ...(isErrorState ? ['error'] : []),
    ];

    if (isCurrentStateGroup) {
      this.stepFound = true;
    }

    const descriptiveStep = classList.includes('completed')
      ? STATE_GROUP_COMPLETION_DESCRIPTION[step]
      : this.getStepTobeShown(classList, step);

    return (
      <MultiLevelStepper.ParentStep
        status={classList.join(' ')}
        title={descriptiveStep.title}
        description={descriptiveStep.description}
        action={
          <div>
            {classList.includes('completed') ? (
              <a
                onClick={() => this.viewCompletedStateGroup(step)}
                className="link"
              >
                View application steps
                <i className="i i-chevron-right" />
              </a>
            ) : classList.includes('active') ? (
              classList.includes('error') || classList.includes('pending') ? (
                <a onClick={this.viewCurrentState} className="link">
                  View application
                  <i class="i i-chevron-right" />
                </a>
              ) : (
                <button
                  className="btn btn-primary multilevel-step__step-action"
                  //get this id from api, if null, the id will
                  // be new.
                  //'new'
                  onClick={this.viewCurrentState}
                >
                  {descriptiveStep.ctaText}
                </button>
              )
            ) : null}
          </div>
        }
      />
    );
  };

  render() {
    const { meta } = this.props.loanApplicationDetails;
    if (!meta.data.application) return 'Loading skeleton...';
    //TODO:Handle meta.errors

    return (
      <div>
        <MultiLevelStepper loading={meta.loading}>
          {CONSOLIDATED_STATE_SEQUENCE.map((step, index) => this.getStep(step))}
        </MultiLevelStepper>
      </div>
    );
  }
}

export default ApplicationStatusOverview;
