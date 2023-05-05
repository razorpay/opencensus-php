// utils
import { render, screen, userEvent } from 'test-utils';

// components
import { Formik } from 'formik';

// states
import {
  formInitialValues,
  getFormSchema,
} from 'merchant/views/Settings/Configuration/Questionnaire/utils';

// testable
import MultiSelect from 'merchant/views/Settings/Configuration/Questionnaire/MultiSelect';

const onSubmit = jest.fn();

const renderComponent = ({
  error,
  required,
  disabled,
  additionalFieldMaxLength,
  additionalData,
}) => {
  return render(
    <Formik
      initialValues={{ ...formInitialValues, existing_risk_checks: additionalData || [] }}
      enableReinitialize={true}
      validationSchema={getFormSchema()}
      onSubmit={onSubmit}
    >
      <MultiSelect
        label="Risk Checks Currently in Place"
        name="existing_risk_checks"
        error={error}
        placeholder="--Select Multiple--"
        required={required}
        options={[
          'None',
          'We differentiate between domestic and international customers',
          'We have set up an upper threshold on transactions / cart value',
          'We maintain a blacklist for the suspicious /  confirmed fraud orders',
        ]}
        className=""
        disabled={disabled}
        additionalFieldMaxLength={additionalFieldMaxLength}
      />
    </Formik>,
  );
};

describe('Test <MultiSelect /> component', () => {
  test('Should render without breaking', () => {
    renderComponent({});
    expect(screen.getByText('Risk Checks Currently in Place')).toBeInTheDocument();
  });

  test('Should change the dropdown value', async () => {
    renderComponent({});
    const select = screen.getByTestId('select-items');

    // Event delegation not happening with userEvent.click, so have to trigger the click on actual element
    await userEvent.click(select.parentElement);

    const option = await screen.findByLabelText(
      'We differentiate between domestic and international customers',
    );

    await userEvent.click(option);
    expect(await screen.findByText('1 items selected')).toBeInTheDocument();

    const otherOptionButton = screen.getByText('+Others Specify');
    await userEvent.click(otherOptionButton);

    const enterDetailsInputField = screen.getByPlaceholderText('Enter details here');
    await userEvent.type(enterDetailsInputField, 'cabin');

    expect(await screen.findByText('2 items selected')).toBeInTheDocument();

    const doneButton = screen.getByText('Done');
    await userEvent.click(doneButton);

    // should close the dropdown
    expect(() =>
      screen.getByLabelText('We differentiate between domestic and international customers'),
    ).toThrow();
  });

  test('Should show edit button for additional field', async () => {
    renderComponent({ additionalData: ['Additional field'] });

    const select = screen.getByTestId('select-items');
    await userEvent.click(select.parentElement);

    const editButton = await screen.findByTestId('editAdditionalField');
    await userEvent.click(editButton);

    // expect(screen.getByPlaceholderText('Enter details here')).toBeInTheDocument();
    const enterDetailsInputField = screen.getByPlaceholderText('Enter details here');
    await userEvent.type(enterDetailsInputField, 'cabin');

    expect(await screen.findByText('1 items selected')).toBeInTheDocument();

    const doneButton = screen.getByText('Done');
    await userEvent.click(doneButton);

    // should close the dropdown
    expect(() =>
      screen.getByLabelText('We differentiate between domestic and international customers'),
    ).toThrow();
  });
});
