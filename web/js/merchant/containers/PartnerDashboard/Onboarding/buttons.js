import Button, { AsyncBtn } from 'component/Button';

export default function Onboarding(props) {
  return (
    <div class="partner--btns">
      <a
        class="patner--back"
        onClick={() => {
          props.prev();
        }}
      >
        {props.active != 0 ? 'Back' : ''}
      </a>
      <Button
        iconAfter="arrow-forward"
        onClick={(...args) => {
          // window.rzpAnalytics({
          //   eventCategory: `Onboarding Card (${feature})`,
          //   eventAction: `Page ${active} - Next CTA`,
          // });

          // next(args);
          props.next();
        }}
      >
        Next
      </Button>
    </div>
  );
}
