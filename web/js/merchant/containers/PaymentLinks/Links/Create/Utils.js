import Input from 'common/new-ui/Input';

export function generateField(field) {
  let component = Input;
  if (field.fieldType === 'Select') {
    component = Input.Select;
  }

  return {
    name: field.name,
    label: field.label,
    placeholder: field.placeholder,
    required: field.required,
    description: field.description,
    _cmp: component,
    options: field.options,
  };
}
