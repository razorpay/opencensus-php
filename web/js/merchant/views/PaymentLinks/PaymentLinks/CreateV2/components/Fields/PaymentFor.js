import Input from 'common/new-ui/Input';
import track from '../../track';

const PaymentFor = (props) => (
  <Input
    name="description"
    label="Payment For"
    className="Input--vTop"
    placeholder="Payment description"
    labelClass="Input-label pb-8"
    onBlur={() => {
      track.lj.fields.paymentFor();
      track.segment.fields.paymentFor();
    }}
    {...props}
  />
);

export default PaymentFor;
