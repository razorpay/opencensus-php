import * as Formik from 'formik';

import { mockContextData } from './mocks';

import ModalContainer from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/components/ModalContainer';
import {
  FORM_INITIAL_VALUES,
  FORMIK_FORM_KEYS,
  TABS,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import * as services from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/services';
import * as utils from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/utils';
import { HUF } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { render, screen, userEvent, waitFor } from 'test-utils';

const useFormikContextSpy = jest.spyOn(Formik, 'useFormikContext');
const formikDefaultReturnValues = {
  errors: {},
  values: FORM_INITIAL_VALUES,
  setFieldValue: jest.fn(),
  validateForm: jest.fn(),
};

const user = {
  business_type: '1',
};

const renderComponent = (props = {}, initialState = {}) => {
  return render(<ModalContainer isOpen user={user} {...props} />, { initialState });
};

describe('Tests for ModalContainer component - MethodEnablementForm', () => {
  beforeEach(() => {
    useFormikContextSpy.mockReturnValue(formikDefaultReturnValues);
  });

  test('Should render without breaking', () => {
    mockContextData({
      selectedTab: 0,
    });
    renderComponent();
    expect(
      screen.getByText('Request to activate other international payment methods'),
    ).toBeInTheDocument();
  });

  test('Should render all the tabs in the sidebar', () => {
    mockContextData({
      selectedTab: 0,
    });
    renderComponent();
    TABS.forEach((tab) => expect(screen.getByText(tab.name)).toBeInTheDocument());

    //should show selectedTab component in modal
    expect(screen.getByText('Pre-requisite Information')).toBeInTheDocument();
  });

  test('Should show valid button text', () => {
    const selectedTab = 0;
    mockContextData({
      selectedTab,
    });
    renderComponent();
    expect(screen.getByText(TABS[selectedTab].buttonText)).toBeInTheDocument();
  });

  test('Should call onTabClick on button click', async () => {
    const selectedTab = 0;
    const onTabClick = jest.fn();
    mockContextData({
      selectedTab,
      onTabClick,
    });
    renderComponent();
    const button = screen.getByText(TABS[selectedTab].buttonText);
    await userEvent.click(button);

    await expect(onTabClick).toHaveBeenCalledWith(1);
  });

  test('Should fetch intl enablement form data on init', async () => {
    const selectedTab = 0;
    const setIsLoading = jest.fn();
    const onTabClick = jest.fn();
    const setInitialValues = jest.fn();
    const setApiData = jest.fn();
    const fetchAdditionalDocumentFormDataSpy = jest
      .spyOn(services, 'fetchAdditionalDocumentFormData')
      .mockReturnValue({ success: true });
    const submitAdditionalDocumentFormDataSpy = jest.spyOn(
      services,
      'submitAdditionalDocumentFormData',
    );
    const getFormDataSpy = jest.spyOn(utils, 'getFormData').mockReturnValue([]);
    mockContextData({
      selectedTab,
      setIsLoading,
      onTabClick,
      setInitialValues,
      setApiData,
    });

    renderComponent();

    expect(setIsLoading).toHaveBeenCalledWith(true); //loader should be shown
    expect(fetchAdditionalDocumentFormDataSpy).toHaveBeenCalled(); // fetch api call should be made
    await waitFor(() => expect(submitAdditionalDocumentFormDataSpy).not.toHaveBeenCalled()); //submit form shouldn't be called
    await waitFor(() => expect(getFormDataSpy).toHaveBeenCalledWith({ success: true })); // api data should be formatted
    expect(setInitialValues).toHaveBeenCalledWith({ ...FORM_INITIAL_VALUES, documents: [] }); // documents should be updated
    expect(setApiData).toHaveBeenCalledWith({ success: true }); //api response should be set
    expect(setIsLoading).toHaveBeenCalledWith(false);
  });

  test('Should call submit api if no documents are required', async () => {
    user.business_type = HUF;
    const selectedTab = 0;
    const fetchAdditionalDocumentFormDataSpy = jest
      .spyOn(services, 'fetchAdditionalDocumentFormData')
      .mockReturnValue({ success: true });
    const submitAdditionalDocumentFormDataSpy = jest
      .spyOn(services, 'submitAdditionalDocumentFormData')
      .mockReturnValue({ success: true });
    mockContextData({
      selectedTab,
    });

    renderComponent();

    expect(fetchAdditionalDocumentFormDataSpy).toHaveBeenCalled(); // fetch api call should be made
    await waitFor(() =>
      expect(submitAdditionalDocumentFormDataSpy).toHaveBeenCalledWith(
        { success: true },
        formikDefaultReturnValues.values,
        user,
      ),
    );
  });

  test('Should show disabled button if formik errors exist', () => {
    const selectedTab = 1;
    mockContextData({
      selectedTab,
    });
    useFormikContextSpy.mockReturnValue({
      ...formikDefaultReturnValues,
      errors: { [FORMIK_FORM_KEYS.KYC_TNC_ACCEPTED]: 'T&C not accepted' },
    });

    renderComponent();

    expect(screen.getByRole('button', { name: TABS[selectedTab].buttonText })).toBeDisabled();
  });

  test('Should show enabled button if formik errors are not related to current tab', () => {
    const selectedTab = 1;
    mockContextData({
      selectedTab,
    });
    useFormikContextSpy.mockReturnValue({
      ...formikDefaultReturnValues,
      errors: { [FORMIK_FORM_KEYS.VKYC_TNC_ACCEPTED]: 'T&C not accepted' },
    });

    renderComponent();

    expect(screen.getByRole('button', { name: TABS[selectedTab].buttonText })).not.toBeDisabled();
  });

  test('Should call submit form api and onTabClick if button is enabled and clicked', async () => {
    user.business_type = HUF;
    const selectedTab = 1;
    const onTabClick = jest.fn();
    mockContextData({
      selectedTab,
      onTabClick,
    });
    const submitAdditionalDocumentFormDataSpy = jest
      .spyOn(services, 'submitAdditionalDocumentFormData')
      .mockReturnValue({ success: true });
    renderComponent();

    await userEvent.click(screen.getByRole('button', { name: TABS[selectedTab].buttonText }));
    await waitFor(() =>
      expect(submitAdditionalDocumentFormDataSpy).toHaveBeenCalledWith(
        { success: true },
        formikDefaultReturnValues.values,
        user,
      ),
    );
    await waitFor(() => expect(onTabClick).toHaveBeenCalledWith(2));
  });

  test('Should show correct button text if video KYC tab is selected', () => {
    const selectedTab = 2;
    mockContextData({
      selectedTab,
    });
    useFormikContextSpy.mockReturnValue({
      ...formikDefaultReturnValues,
      values: { FORM_INITIAL_VALUES, [FORMIK_FORM_KEYS.SIGNATORY]: '0' },
    });

    renderComponent();

    expect(screen.getByText('Close')).toBeInTheDocument();
  });

  test('Should show correct button text if video KYC tab is selected and is authorized signatory', () => {
    const selectedTab = 2;
    mockContextData({
      selectedTab,
    });
    useFormikContextSpy.mockReturnValue({
      ...formikDefaultReturnValues,
      values: { FORM_INITIAL_VALUES, [FORMIK_FORM_KEYS.SIGNATORY]: '1' },
    });

    renderComponent();

    expect(screen.getByText(TABS[selectedTab].buttonText)).toBeInTheDocument();
  });
});
