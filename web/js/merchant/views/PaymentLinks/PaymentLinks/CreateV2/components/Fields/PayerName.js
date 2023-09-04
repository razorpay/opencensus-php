import Input from 'common/new-ui/Input';

const PayerName = ({ defaultValue, required, disabled }) => (
  <Input
    name="name"
    label="Payer Name"
    className="Input--vTop"
    placeholder="Payer Name"
    labelClass="Input-label pb-8"
    defaultValue={defaultValue}
    required={required}
    disabled={disabled}
  />
);

export default PayerName;
