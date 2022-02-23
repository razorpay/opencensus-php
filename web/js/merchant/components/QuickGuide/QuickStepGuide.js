import React from 'react';
import Button from 'common/new-ui/Button';

import StepGuide from 'merchant/components/StepGuide';
import Step from 'merchant/components/StepGuide/Step';

export default function QuickStepGuide(props) {
  return <StepGuide {...props} class={`${props.className} QuickGuide`} />;
}

export const QuickGuideTitle = ({ title = 'GET STARTED' }) => (
  <React.Fragment>
    {title}

    <div class="divider" />
  </React.Fragment>
);

export const QuickGuideCloseBtn = ({ isCompleted, onClick }) => (
  <Button.Transparent onClick={onClick}>
    {isCompleted ? (
      <span class="done">
        {' '}
        <i class="i i-thumbs-up" /> Got It{' '}
      </span>
    ) : (
      <i class="i i-close" />
    )}
  </Button.Transparent>
);

class QuickGuideStep extends React.Component {
  onClick = () => {
    window.rzpAnalytics?.({
      eventCategory: `Product QuickGuide (${this.props.feature})`,
      eventAction: `${this.props.step} Click`,
    });
  };

  render() {
    return <Step {...this.props} onStepClick={this.onClick} />;
  }
}

export { QuickGuideStep };
