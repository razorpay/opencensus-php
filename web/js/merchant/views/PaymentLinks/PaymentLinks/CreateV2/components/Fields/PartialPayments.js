import Input from 'common/new-ui/Input';

const PartialPayments = (props) => (
  <Input.Check
    autoRender
    name="accept_partial"
    fieldLabel="Enable Partial Payment"
    label="Partial Payment"
    class="Input--vTop"
    {...props}
  />
);

export default PartialPayments;
