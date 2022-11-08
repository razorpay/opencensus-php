import { AsyncBtn } from 'common/new-ui/Button';

const Button = ({
  buttonText = '',
  btnClassName = '',
  pendingState,
  loading = false,
  disabled = false,
  hideIcon = false,
  onClick = () => {},
}) => {
  return (
    <AsyncBtn.Primary
      id="missed-order-pl-button"
      className={`btn-primary ${btnClassName} ${loading ? 'mopl-btn-loading' : ''}`}
      type="button"
      onClick={onClick}
      disabled={disabled}
      pendingState={pendingState}
      iconAfter={!hideIcon && 'arrow-forward'}
    >
      {buttonText}
    </AsyncBtn.Primary>
  );
};

export default Button;
