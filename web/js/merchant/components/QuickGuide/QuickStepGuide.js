import Button from 'component/Button';

import StepGuide from 'merchant/components/StepGuide';

export default function QuickStepGuide(props) {
  return <StepGuide {...props} class={`${props.className} QuickGuide`} />;
}

export const QuickGuideTitle = ({ title = 'QUICK GUIDE' }) => (
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
