import Input from 'common/new-ui/Input';
import track from '../../track';

const PartialPayments = (props) => (
  <Input.Check
    autoRender
    name="accept_partial"
    fieldLabel="Enable Partial Payment"
    label="Partial Payment"
    class="Input--vTop"
    {...props}
    onBlur={track.lj.fields.partialPayment}
  />
);

export default PartialPayments;
