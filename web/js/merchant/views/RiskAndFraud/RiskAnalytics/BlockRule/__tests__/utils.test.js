import { validateForm } from 'merchant/views/RiskAndFraud/RiskAnalytics/BlockRule/utils';

describe('validateForm function', () => {
  test('should return errors for required fields if values are not provided', () => {
    const values = {
      parameters: '',
      email: '',
      file: '',
    };

    const errors = validateForm(values);

    expect(errors.parameters).toBe('This field is required');
    expect(errors.email).toBe('This field is required');
    expect(errors.file).toBe('This field is required');
  });

  test('should return error for invalid email format', () => {
    const values = {
      parameters: 'some value',
      email: 'invalidemail', // invalid email format
      file: 'some file',
    };

    const errors = validateForm(values);
    expect(errors.email).toBe('Please enter a valid email Id');
  });

  test('should return no errors if all fields are provided correctly', () => {
    const values = {
      parameters: 'some value',
      email: 'valid@example.com',
      file: 'some file',
    };

    const errors = validateForm(values);
    expect(Object.keys(errors).length).toBe(0); // Ensure no errors are returned
  });
});
