import Input from 'common/new-ui/Input';

const PaymentFor = (props) => (
  <Input
    name="description"
    label="Payment For"
    class="Input--vTop"
    placeholder="Payment description"
    {...props}
  />
);

export default PaymentFor;
