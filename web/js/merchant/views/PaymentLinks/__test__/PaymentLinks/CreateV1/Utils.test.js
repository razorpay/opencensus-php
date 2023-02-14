import Input from 'common/new-ui/Input';
import { generateField } from 'merchant/views/PaymentLinks/PaymentLinks/Create/Utils';

describe('generateField Util fn Unit Test Case', () => {
  test('should return an object with the correct properties', () => {
    const field = {
      name: 'fieldName',
      label: 'Field Label',
      placeholder: 'Enter a value',
      required: true,
      description: 'This is a field',
      fieldType: 'Select',
      options: [
        { label: 'Option 1', value: 'option1' },
        { label: 'Option 2', value: 'option2' },
      ],
    };
    const result = generateField(field);
    expect(result).toEqual({
      name: 'fieldName',
      label: 'Field Label',
      placeholder: 'Enter a value',
      required: true,
      description: 'This is a field',
      _cmp: Input.Select,
      options: [
        { label: 'Option 1', value: 'option1' },
        { label: 'Option 2', value: 'option2' },
      ],
    });
  });

  test('should return an object with a component property set to Input if fieldType is not "Select"', () => {
    const field = {
      name: 'fieldName',
      label: 'Field Label',
      placeholder: 'Enter a value',
      required: true,
      description: 'This is a field',
      fieldType: 'Text',
      options: [],
    };
    const result = generateField(field);
    expect(result).toEqual({
      name: 'fieldName',
      label: 'Field Label',
      placeholder: 'Enter a value',
      required: true,
      description: 'This is a field',
      _cmp: Input,
      options: [],
    });
  });
});
