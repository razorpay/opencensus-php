import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import { activationDuration } from 'common/data';
import Step, { StepTitle, StepContent, possibleStatuses } from './Step';
import RTracking from 'react-tracking';

const initialState = {
  status: null,
  content: null,
  title: 'Account Activation',
};
@RTracking(() => {
  return window.rzpQ.component('ActivationCard');
})
export default class ActivationCard extends Component {
  constructor(props) {
    super(props);
    this.state = initialState;
  }

  componentWillReceiveProps(nextProps) {
    const { tracking } = this.props;
    const {
        instantActivation,
        isSubmitted,
        needsClarification,
        isActivated,
        isRejected,
        onActive,
        track,
        international,
      } = nextProps,
      {
        isL1Submitted,
        isWhitelistFlow,
        isBlacklistFlow,
        isGraylistFlow,
      } = instantActivation;

    let { status, content, title } = initialState;

    if (!isL1Submitted) {
      status = possibleStatuses.active;
      content = (
        <div>
          Give a few details to start transacting immediately
          <div>
            <Link
              to="/activation"
              className="btn btn-primary"
              onClick={e => {
                track.activateAccount();
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('act.form_fill', {
                    clickSource: 'Dashboard CTA',
                  })
                );
              }}
            >
              Activate Account
            </Link>
          </div>
        </div>
      );
    } else if (isActivated) {
      title = 'Account Activated';
      status = possibleStatuses.done;
      content =
        'You can now start accepting domestic ' +
        (international ? 'and international' : '') +
        ' payments';
    } else if (isGraylistFlow) {
      if (!isSubmitted) {
        status = possibleStatuses.active;
        content = (
          <div>
            <div>
              For your business model, we need a few more details for activation
            </div>
            <Link
              to="/activation"
              className="btn btn-primary"
              onClick={() => track.fillKyc()}
            >
              Fill KYC Form
            </Link>
          </div>
        );
      } else {
        if (needsClarification) {
          status = possibleStatuses.blocked;
          content = 'Check your email ID to complete clarification of KYC';
        } else if (isRejected) {
          status = possibleStatuses.blocked;
          content = 'Your KYC form has been rejected.';
        } else {
          status = possibleStatuses.progress;
          content = `We are reviewing your form. Expect confirmation in ${activationDuration}.`;
        }
      }
    } else if (isBlacklistFlow) {
      status = possibleStatuses.blocked;
      content = (
        <span>
          We do not support your selected business model. In case you entered it
          wrong, change it{' '}
          <Link
            to="/activation"
            className="btn-link"
            onClick={() => {
              track.refillActivationForm();
              tracking.trackEvent(
                window.rzpQ.onbr().initiated('act.form_fill', {
                  clickSource: 'Modify Business Category',
                })
              );
            }}
          >
            here
          </Link>
        </span>
      );
    }

    if (
      onActive &&
      status !== this.state.status &&
      (status === possibleStatuses.active ||
        status === possibleStatuses.progress ||
        status === possibleStatuses.blocked)
    ) {
      onActive();
    }

    this.setState({
      content,
      title,
      status,
    });
  }

  render() {
    const { status, content, title } = this.state;

    return (
      <Step status={status}>
        <StepTitle>{title}</StepTitle>
        <StepContent>{content}</StepContent>
      </Step>
    );
  }
}
