import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';

const LinkExpiry = (props) => {
  const extraProps = {
    isInline: true,
    class: 'Input--vTop',
  };

  if (props.user.isExpireByRequired) {
    extraProps.isInline = false;
    extraProps.required = true;
    delete extraProps.class;
    extraProps.dateTimeInputClass = 'InputGroup--vTop';
  }

  return (
    <Input.DateTime
      autoRender
      label="Link Expiry"
      checkboxFieldLabel="Expire link after"
      onChange={props.onChange}
      disabled={props.disabled}
      defaultValue={props.defaultValue}
      {...extraProps}
    />
  );
};

const mapStateTopProps = (state) => ({
  user: state.session.user,
});
export default connect(mapStateTopProps)(LinkExpiry);
