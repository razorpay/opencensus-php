// utils
import { render, screen } from 'test-utils';

// components
import { Formik } from 'formik';

// states
import {
  formInitialValues,
  getFormSchema,
} from 'merchant/views/Settings/Configuration/Questionnaire/utils';

// constants
import { CURRENCY_LIST } from './mocks/fixtures';

// testable
import SupportingDetails from 'merchant/views/Settings/Configuration/Questionnaire/SupportingDetails';

const onSubmit = jest.fn();

const renderComponent = ({ disabled }) => {
  return render(
    <Formik
      initialValues={formInitialValues}
      enableReinitialize={true}
      validationSchema={getFormSchema()}
      onSubmit={onSubmit}
    >
      <SupportingDetails disabled={disabled} />
    </Formik>,
  );
};

describe('Test <SupportingDetails /> component', () => {
  beforeAll(() => {
    window.currencyList = CURRENCY_LIST;
  });

  test('Should render without breaking', () => {
    renderComponent({});
    expect(screen.getByText('SUPPORTING DETAILS AND BEST PRACTICES')).toBeInTheDocument();
  });
});
