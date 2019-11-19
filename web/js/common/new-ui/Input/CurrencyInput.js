import Input from 'common/new-ui/Input';

export default function(props) {
  return (
    <Input.Group label={props.label} className="InputGroup--inline">
      <div className="Input-content">
        <Input.CurrencySelect defaultValue={props.currency || 'INR'} />
        <Input
          name={props.name}
          onChange={props.onChange}
          placeholder={props.placeholder}
          type="Number"
          validator={props.validator}
          className={props.className}
        />
      </div>
    </Input.Group>
  );
}
