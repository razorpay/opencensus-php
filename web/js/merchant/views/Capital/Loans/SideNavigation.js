import React, { Component } from 'react';
import MultiLevelStepper from 'merchant/views/Capital/components/MultiLevelStepper';
import { ERROR_STATES, PENDING_APPLICATION_STATES } from './constants';
import { connect } from 'react-redux';
import { changeActiveState } from 'merchant/reducers/capital';
import getApplicationProgressPercentage from '../utils/ProgressPercentageCalculator';

@connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    changeActiveState,
  },
)
class SideNavigation extends Component {
  constructor(props) {
    super(props);
    this.stepFound = false;
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    this.stepFound = false;
  }

  _getParentStepLabel = (step) => {
    const SIDE_NAVIGATION_STATE_GROUPS = this.getUserFlowConfiguration().getSideNavigationStateGroups();

    return Object.values(SIDE_NAVIGATION_STATE_GROUPS).filter((meta) =>
      Object.values(meta.steps)
        .reduce((acc, curr) => [...acc, ...curr], [])
        .includes(step),
    )[0].description;
  };

  _getProgressPercentage = () => {
    const { meta } = this.props.loanApplicationDetails;
    if (!meta.data.application.status) return 0;
    return getApplicationProgressPercentage(
      meta.data.application.status,
      meta.configuration.getApplicationStateGroups(),
    );
  };

  _trackNavigationEvent = (_to, _from) => {
    const APPLICATION_STATE_DESCRIPTIONS = this.getUserFlowConfiguration().getApplicationStateDescriptions();

    const _toStepLabel = APPLICATION_STATE_DESCRIPTIONS[_to].short_description;
    const _fromStepLabel = APPLICATION_STATE_DESCRIPTIONS[_from].short_description;
    this.gaEventDispatcher({
      eventAction: 'Left Navigation | Steps',
      eventLabel: `${this._getParentStepLabel(
        _from,
      )}:${_fromStepLabel} to ${this._getParentStepLabel(
        _to,
      )}:${_toStepLabel} | ${this._getProgressPercentage()}%`,
    });
  };

  getParentStep = (parentStep, parentStepMeta) => {
    const { meta, context } = this.props.loanApplicationDetails;
    const userFlowConfiguration = this.getUserFlowConfiguration();
    const APPLICATION_STATE_GROUPS = userFlowConfiguration.getApplicationStateGroups();
    const SIDE_NAVIGATION_STATE_GROUPS = userFlowConfiguration.getSideNavigationStateGroups();

    const currentState = meta.data.application.status;

    const { activeState } = context;

    const isCurrentStateGroup = APPLICATION_STATE_GROUPS[parentStep].includes(currentState);

    const isActiveStateGroup = APPLICATION_STATE_GROUPS[parentStep].includes(activeState);

    const isFinalState =
      Object.keys(APPLICATION_STATE_GROUPS).indexOf(parentStep) ===
      Object.keys(APPLICATION_STATE_GROUPS).length - 1;

    //both cannot be true
    const isPendingState = isCurrentStateGroup && PENDING_APPLICATION_STATES.includes(currentState);
    const isErrorState = isCurrentStateGroup && ERROR_STATES.includes(currentState);

    const classList = [
      // ...(isCurrentStateGroup ? [''] : []),
      ...(isActiveStateGroup || isCurrentStateGroup ? ['expanded'] : []),
      ...(this.stepFound
        ? ['not_started']
        : isCurrentStateGroup && !isFinalState
        ? ['partial-complete', 'active']
        : ['completed']),
      ...(isPendingState ? ['pending'] : []),
      ...(isErrorState ? ['error'] : []),
    ];

    if (isCurrentStateGroup) {
      this.stepFound = true;
    }

    return (
      <React.Fragment>
        <MultiLevelStepper.ParentStep
          status={classList.join(' ')}
          title={parentStepMeta.description}
          highlight={false}
          disabled={classList.includes('not_started')}
          action={
            classList.includes('completed') &&
            !classList.includes('expanded') && (
              <a
                className="link"
                onClick={() => {
                  const targetStep = Object.values(
                    SIDE_NAVIGATION_STATE_GROUPS[parentStep].steps,
                  )[0][0];
                  this._trackNavigationEvent(targetStep, activeState);
                  this.props.changeActiveState(targetStep);
                }}
              >
                View Details
                <i className="i i-chevron-down" />
              </a>
            )
          }
        />
        {classList.includes('expanded') && this.getSteps(parentStep, parentStepMeta, classList)}
      </React.Fragment>
    );
  };

  handleNavigation = (step, parentStepMeta) => {
    const { context, meta } = this.props.loanApplicationDetails;
    const currentState = meta.data.application.status;

    this._trackNavigationEvent(step, context.activeState);

    if (parentStepMeta.steps[step].includes(currentState)) {
      this.props.changeActiveState(currentState);
    } else {
      this.props.changeActiveState(step);
    }
  };

  getSteps = (parentStep, parentStepMeta, statuses) => {
    const { context, meta } = this.props.loanApplicationDetails;

    const currentState = meta.data.application.status;

    const { activeState } = context;

    let stepFound = false;
    const getStatus = (step, steps) => {
      const doHighlight = parentStepMeta.steps[step].includes(activeState);

      if (statuses.includes('completed'))
        return ['completed', 'parent-complete', ...(doHighlight ? ['highlight'] : [])];

      const isCurrentStateGroup = steps.includes(currentState);

      //both cannot be true
      const isPendingState =
        isCurrentStateGroup && PENDING_APPLICATION_STATES.includes(currentState);
      const isErrorState = isCurrentStateGroup && ERROR_STATES.includes(currentState);

      const classList = [
        ...(doHighlight ? ['highlight'] : []),
        ...(isCurrentStateGroup
          ? ['active']
          : stepFound
          ? ['not_started']
          : ['completed', 'parent-partial-complete']),
        ...(isPendingState ? ['pending'] : []),
        ...(isErrorState ? ['error'] : []),
      ];

      if (isCurrentStateGroup) {
        stepFound = true;
      }

      return classList;
    };

    const APPLICATION_STATE_DESCRIPTIONS = this.getUserFlowConfiguration().getApplicationStateDescriptions();

    return Object.entries(parentStepMeta.steps).map(([step, steps]) => {
      const statuses = getStatus(step, steps);

      //TODO:Comment this for now. In future, we want to hide the async
      // states if they are completed
      // if(PENDING_APPLICATION_STATES.includes(step) && statuses.includes('completed')) return null;

      return (
        <MultiLevelStepper.Step
          key={step}
          status={statuses.join(' ')}
          title={APPLICATION_STATE_DESCRIPTIONS[step].short_description}
          onClick={() => this.handleNavigation(step, parentStepMeta)}
          disabled={statuses.includes('not_started')}
        />
      );
    });
  };

  gaEventDispatcher = (eventObject) => {
    eventObject['eventCategory'] = 'Dashboard - WCL LOS';
    window.rzpAnalytics(eventObject);
  };

  getUserFlowConfiguration = () => {
    const { meta } = this.props.loanApplicationDetails;
    return meta.configuration;
  };

  render() {
    const configuration = this.getUserFlowConfiguration();

    return (
      <div class="progress-overview-container">
        <MultiLevelStepper>
          {Object.entries(
            configuration.getSideNavigationStateGroups(),
          ).map(([parentStep, parentStepMeta]) => this.getParentStep(parentStep, parentStepMeta))}
        </MultiLevelStepper>
      </div>
    );
  }
}

export default SideNavigation;
