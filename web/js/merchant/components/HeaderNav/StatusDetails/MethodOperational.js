import Button from '../../../../common/new-ui/Button';

const MethodOperational = (props) => {
  return (
    <div
      class={
        props.methodName === 'Net Banking' ? 'payment-method' : 'payment-method with-bottom-border'
      }
    >
      <img src={`${window.cdnBaseUrl}/static/assets/downtimes/green-tick-small.svg`} />
      <span className="payment-method-title"> {props.methodName}</span>
      <Button.Transparent
        class="status-view-button"
        onClick={() => {
          props.switchToInfoView(props.methodName);
        }}
      >
        View Details
      </Button.Transparent>
      <img
        src={`${window.cdnBaseUrl}/static/assets/downtimes/arrow-right-blue.svg`}
        class="status-view-arrow"
        onClick={() => {
          props.switchToInfoView(props.methodName);
        }}
      />
    </div>
  );
};

export default MethodOperational;
