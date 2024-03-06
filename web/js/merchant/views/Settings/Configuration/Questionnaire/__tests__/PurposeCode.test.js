import * as Formik from 'formik';

import PurposeCode from 'merchant/views/Settings/Configuration/Questionnaire/PurposeCode';
import { initialStateForRevamp } from 'merchant/views/Settings/Configuration/Questionnaire/stateHelpers';
import { render, screen, userEvent } from 'test-utils';

import { purposeCodeList, defaultPurposeCode } from './mocks/fixtures';

jest.mock('merchant/views/Settings/Configuration/Questionnaire/utils', () => ({
  ...jest.requireActual('merchant/views/Settings/Configuration/Questionnaire/utils'),
  revampTabs: [],
}));

const useFormikContextSpy = jest.spyOn(Formik, 'useFormikContext');

const renderComponent = (props) => {
  return render(
    <PurposeCode
      isRevampFlow
      isAnyIntlProductEnabled={false}
      purposeCodeList={[]}
      fetchPurposeCodes={() => {}}
      {...props}
    />,
  );
};

const defaultMockedValues = {
  errors: {},
  touched: {},
  status: {},
  values: initialStateForRevamp.initialValues,
  setFieldValue: jest.fn(),
};

describe('Tests for PurposeCode component - Questionaire module', () => {
  beforeEach(() => {
    useFormikContextSpy.mockReturnValue(defaultMockedValues);
  });

  test('Should show dropdown and search input', () => {
    renderComponent();

    const searchInput = screen.getByText('Search code');
    expect(searchInput).toBeInTheDocument();
    expect(searchInput).toHaveClass('Input-label');

    const dropdown = screen.getByText('Purpose group');
    expect(dropdown).toBeInTheDocument();
    expect(dropdown).toHaveClass('Input-label');
  });

  test('Should show purpose code list and purpose group in dropdown on initial load', () => {
    renderComponent({ purposeCodeList });

    purposeCodeList.forEach((purposeGroup) => {
      expect(screen.getByRole('option', { name: purposeGroup.purposeGroup })).toBeInTheDocument();
      purposeGroup.codes.forEach((code) => {
        expect(screen.getByText(`${code.purposeCode} - ${code.description}`)).toBeInTheDocument();
      });
    });
  });

  test('Should show selected purpose code only if already exists', () => {
    useFormikContextSpy.mockReturnValue({
      ...defaultMockedValues,
      values: { purpose_code: defaultPurposeCode.purposeCode },
    });
    renderComponent({ purposeCodeList });

    expect(
      screen.getByText(`${defaultPurposeCode.purposeCode} - ${defaultPurposeCode.description}`),
    ).toBeInTheDocument();

    purposeCodeList[0].codes
      .filter((code) => code.purposeCode !== defaultPurposeCode.purposeCode)
      .forEach(({ purposeCode, description }) => {
        expect(screen.queryByText(`${purposeCode} - ${description}`)).not.toBeInTheDocument();
      });
  });

  test('Should show banner if purpose code already exists and isAnyIntlProductEnabled is true', () => {
    useFormikContextSpy.mockReturnValue({
      ...defaultMockedValues,
      values: { purpose_code: defaultPurposeCode.purposeCode },
    });
    renderComponent({ isAnyIntlProductEnabled: true });

    expect(
      screen.getByText('The purpose code below is associated with your account', {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  test('Should filter list on search', async () => {
    useFormikContextSpy.mockReturnValue({
      ...defaultMockedValues,
      values: { purpose_code: '' },
    });
    renderComponent({ purposeCodeList });

    const searchInput = screen.getByTestId('purpose-code-input');
    const selectedPurposeCode = purposeCodeList[0].codes[0];
    await userEvent.type(searchInput, selectedPurposeCode.purposeCode);

    expect(
      screen.getByText(`${selectedPurposeCode.purposeCode} - ${selectedPurposeCode.description}`),
    ).toBeInTheDocument();

    purposeCodeList[0].codes
      .filter((code) => code.purposeCode !== selectedPurposeCode.purposeCode)
      .forEach(({ purposeCode, description }) => {
        expect(screen.queryByText(`${purposeCode} - ${description}`)).not.toBeInTheDocument();
      });
  });
});
