import React from 'react';
import Button from 'common/new-ui/Button';

import StepGuide from 'merchant/components/StepGuide';
import Step from 'merchant/components/StepGuide/Step';
import { connect } from 'react-redux';

function QuickStepGuide(props) {
  const { user, isMobileResolution, className } = props;
  const i18NOrgClassName = user.isOrgCurlec && !isMobileResolution ? ' i18N-Org' : '';
  return <StepGuide {...props} class={`${className} QuickGuide${i18NOrgClassName}`} />;
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

export default connect(
  (state) => ({ user: state.session.user, isMobileResolution: state.app.isMobileResolution }),
  null,
)(QuickStepGuide);
