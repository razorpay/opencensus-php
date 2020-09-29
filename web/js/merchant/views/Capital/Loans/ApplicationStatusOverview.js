import React, { Component } from 'react';
import MultiLevelStepper from 'merchant/views/Capital/components/MultiLevelStepper';
import { connect } from 'react-redux';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';
import { ERROR_STATES, PENDING_APPLICATION_STATES, HOTJAR_TRIGGERS } from './constants';
import { triggerHotjarRecording } from 'common/utils/hotjar';

@connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    fetchLoanApplicationMeta,
  },
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

  getStateType = (applicationStatus) => {
    const isPendingState = PENDING_APPLICATION_STATES.includes(applicationStatus);
    const isErrorState = PENDING_APPLICATION_STATES.includes(applicationStatus);
    if (isPendingState) {
      return 'pending';
    } else if (isErrorState) {
      return 'error';
    } else {
      return 'normal';
    }
  };

  viewCompletedStateGroup = (parentStep, _targetStepTitle) => {
    const { meta } = this.props.loanApplicationDetails;

    const APPLICATION_STATE_GROUPS = this.getUserFlowConfiguration().getApplicationStateGroups();

    const targetStep = APPLICATION_STATE_GROUPS[parentStep][0];
    this.props.openLoanEntity(
      meta.data.application.id,
      targetStep,
      _targetStepTitle,
      'View Completed Steps',
    );
  };

  viewCurrentState = (_targetStepTitle, _stepCtaLabel) => {
    const { meta } = this.props.loanApplicationDetails;

    this.props.openLoanEntity(meta.data.application.id, null, _targetStepTitle, _stepCtaLabel);
  };

  getStepTobeShown = (classList, step) => {
    const APPLICATION_STATE_GROUPS = this.getUserFlowConfiguration().getApplicationStateGroups();
    const APPLICATION_STATE_DESCRIPTIONS = this.getUserFlowConfiguration().getApplicationStateDescriptions();

    const { meta } = this.props.loanApplicationDetails;
    const applicationStatus = meta.data.application.status;
    if (classList.includes('active')) {
      return APPLICATION_STATE_DESCRIPTIONS[applicationStatus];
    } else if (classList.includes('completed')) {
      return APPLICATION_STATE_DESCRIPTIONS[APPLICATION_STATE_GROUPS[step].slice(-1)];
    } else if (classList.includes('not_started')) {
      return APPLICATION_STATE_DESCRIPTIONS[APPLICATION_STATE_GROUPS[step][0]];
    }
  };

  getStep = (step) => {
    const { meta } = this.props.loanApplicationDetails;
    const applicationStatus = meta.data.application.status;

    const APPLICATION_STATE_GROUPS = this.getUserFlowConfiguration().getApplicationStateGroups();
    const isCurrentStateGroup = APPLICATION_STATE_GROUPS[step].includes(applicationStatus);

    //both cannot be true
    const isPendingState =
      isCurrentStateGroup && PENDING_APPLICATION_STATES.includes(applicationStatus);
    const isErrorState = isCurrentStateGroup && ERROR_STATES.includes(applicationStatus);

    const isFinalState =
      Object.keys(APPLICATION_STATE_GROUPS).indexOf(step) ===
      Object.keys(APPLICATION_STATE_GROUPS).length - 1;

    const classList = [];
    if (isCurrentStateGroup) {
      this.stepFound = true;
      if (isFinalState) {
        classList.push('completed');
      } else {
        classList.push('active');
        classList.push('highlight');
      }
    } else if (this.stepFound) {
      classList.push('not_started');
    } else {
      classList.push('completed');
    }

    if (isPendingState) {
      classList.push('pending');
    }
    if (isErrorState) {
      classList.push('error');
    }
    const STATE_GROUP_COMPLETION_DESCRIPTION = this.getUserFlowConfiguration().getCompletedStateGroupDescriptions();

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
                onClick={() => this.viewCompletedStateGroup(step, descriptiveStep.title)}
                className="link"
              >
                View application steps
                <i className="i i-chevron-right" />
              </a>
            ) : classList.includes('active') ? (
              classList.includes('error') || classList.includes('pending') ? (
                <a
                  onClick={() =>
                    this.viewCurrentState(descriptiveStep.title, descriptiveStep.ctaText)
                  }
                  className="link"
                >
                  View application
                  <i class="i i-chevron-right" />
                </a>
              ) : (
                <button
                  className="btn btn-primary multilevel-step__step-action"
                  onClick={() => {
                    triggerHotjarRecording(HOTJAR_TRIGGERS.LOAN_APPLICATION_OPEN);
                    this.viewCurrentState(descriptiveStep.title, descriptiveStep.ctaText);
                  }}
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

  getUserFlowConfiguration = () => {
    const { meta } = this.props.loanApplicationDetails;
    return meta.configuration;
  };

  render() {
    const configuration = this.getUserFlowConfiguration();
    if (!configuration) return 'Loading...';

    return (
      <div>
        <MultiLevelStepper loading={this.props.loanApplicationDetails.meta.loading}>
          {configuration.getConsolidatedStateSequence().map((step, index) => this.getStep(step))}
        </MultiLevelStepper>
      </div>
    );
  }
}

export default ApplicationStatusOverview;
