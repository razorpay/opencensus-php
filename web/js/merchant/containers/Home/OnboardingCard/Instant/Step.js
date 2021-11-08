import React, { Component } from 'react';
import PropTypes from 'prop-types';

import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { isChildSameType, checkChildrenType } from 'common/utils/react-utils';

const loading = 'loading';
const progress = 'progress';
const locked = 'locked';
const done = 'done';
const active = 'active';
const blocked = 'blocked';
const warning = 'warning';

const possibleStatuses = { loading, progress, locked, done, active, blocked, warning };

class StepTitle extends Component {
  render() {
    return <div className="step-title">{this.props.children}</div>;
  }
}

class StepContent extends Component {
  render() {
    return (
      <div className={`step-content ${this.props.isInstantActivationEnabled ? 'align-left' : ''}`}>
        {this.props.children}
      </div>
    );
  }
}

class Step extends Component {
  render() {
    let stepTitle = null;
    let stepContent = null;

    React.Children.forEach(this.props.children, (child) => {
      if (!stepTitle && isChildSameType(child, StepTitle)) {
        stepTitle = child;
      }

      if (!stepContent && isChildSameType(child, StepContent)) {
        stepContent = child;
      }
    });

    const { status } = this.props;
    const isLoading = status === loading;

    return (
      <div
        className={`onboarding-step status-${status} ${
          this.props.isInstantActivationEnabled ? 'align-left' : ''
        }`}
      >
        <div
          className={`step-connector ${this.props.isInstantActivationEnabled ? 'align-left' : ''}`}
        >
          <div className="step-connector-content" />
        </div>
        <div
          className={`step-indicator ${this.props.isInstantActivationEnabled ? 'align-left' : ''}`}
        >
          {isLoading ? (
            <PlaceholderLoader />
          ) : (
            <img
              src={`/dist/css/assets/onboarding/${
                status === 'warning' ? 'warning.svg' : `${status}.png`
              }`}
            />
          )}
        </div>
        <div
          className={`step-content ${this.props.isInstantActivationEnabled ? 'align-left' : ''}`}
        >
          <div className="step-content-title">
            {stepTitle && (
              <stepTitle.type>
                {(isLoading && <PlaceholderLoader />) || stepTitle.props.children}
              </stepTitle.type>
            )}
          </div>
          <div className="step-content-body">
            {stepContent && (
              <stepContent.type>
                {isLoading ? (
                  <div>
                    <PlaceholderLoader />
                    <PlaceholderLoader />
                  </div>
                ) : (
                  stepContent.props.children
                )}
              </stepContent.type>
            )}
          </div>
        </div>
      </div>
    );
  }
}

Step.defaultProps = {
  status: loading,
};

Step.propTypes = {
  children: ({ children }) => checkChildrenType(children, [StepTitle, StepContent]),
  status: PropTypes.oneOf(Object.keys(possibleStatuses)),
};

export { Step, StepTitle, StepContent, possibleStatuses };

export default Step;
