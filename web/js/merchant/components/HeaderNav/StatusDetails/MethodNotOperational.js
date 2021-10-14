import Button from '../../../../common/new-ui/Button';

const MethodNotOperational = (props) => {
  return (
    <div class="payment-method">
      <span className="payment-method-title-not-operational">{props.methodName}</span>
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

export default MethodNotOperational;
