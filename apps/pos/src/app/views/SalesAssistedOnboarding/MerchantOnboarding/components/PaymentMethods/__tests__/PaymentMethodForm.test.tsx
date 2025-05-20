import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import PaymentMethodContextProvider from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/PaymentMethodContextProvider/index';
import PaymentMethodFormComponent from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/PaymentMethodForm/PaymentMethodForm';
import NACHForm from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/NACHForm/index';
import { NachFormKeyNames } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/NACHForm/NACHForm';
import {
  getModularConfig,
  updateModularConfig,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/__tests__/mocks/handlers';
import { mockProps } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/__tests__/mocks/fixtures';

jest.setTimeout(30000);
const renderApp = () => {
  render(
    <PaymentMethodContextProvider
      brandEmi
      addedBrands
      nach
      component={PaymentMethodFormComponent}
    />,
  );
};

const mockSubmit = jest.fn();

const props = {
  ...mockProps,
  onFileUploadChange: jest.fn(),
  onFieldCheckboxChange: jest.fn(),
  onFieldInputChange: jest.fn(),
  onFormSubmitClick: mockSubmit,
};

describe('<PaymentMethods/>', () => {
  afterAll(() => jest.clearAllMocks());
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should open bottom sheet on mount', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          addNachComponent: false,
        },
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Select Onboarding Model/i)).toBeInTheDocument();
      expect(screen.getByRole('radio', { name: /Aggregator Model/i })).toBeInTheDocument();
      expect(screen.getByRole('radio', { name: /Direct Model/i })).toBeInTheDocument();
    });
  });

  test('should show error toast if no custom proof uploaded when rates are edited', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
          agreementDocuments: [],
          vas_cc_emi_rate_field: '3.2',
          vas_dc_emi_rate_field: '4.2',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
          agreementDocuments: [],
          vas_cc_emi_rate_field: '3.2',
          vas_dc_emi_rate_field: '4.2',
        },
      }),
    );
    renderApp();
    const mdrEditBtn = screen.getByTestId('vas-edit-save');
    await userEvent.click(mdrEditBtn);
    const submitFormBtn = screen.getByRole('button', { name: /save & continue/i });
    expect(submitFormBtn).toBeDisabled();
    const vas_cc_emi_field = screen.getByTestId('vas_cc_emi_rate_field');
    const vas_dc_emi_field = screen.getByTestId('vas_dc_emi_rate_field');
    await userEvent.type(vas_cc_emi_field, '3.2');
    await userEvent.type(vas_dc_emi_field, '4.2');
    await userEvent.click(mdrEditBtn);
    expect(submitFormBtn).toBeEnabled();
    await userEvent.click(submitFormBtn);
    await waitFor(() => {
      expect(screen.getByText(/Please upload custom pricing proof/i)).toBeInTheDocument();
    });
  });

  test('should show error if incorrect form values are entered', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
          agreementDocuments: [],
          vas_cc_emi_rate_field: '333.2',
        },
      }),
    );
    renderApp();
    const mdrEditBtn = screen.getByTestId('vas-edit-save');
    await userEvent.click(mdrEditBtn);
    const submitFormBtn = screen.getByRole('button', { name: /save & continue/i });
    expect(submitFormBtn).toBeDisabled();
    const vas_cc_emi_field = screen.getByTestId('vas_cc_emi_rate_field');
    await userEvent.type(vas_cc_emi_field, '333.2');
    await userEvent.click(mdrEditBtn);
    expect(submitFormBtn).toBeEnabled();
    await userEvent.click(submitFormBtn);
    await waitFor(() => {
      expect(screen.getByText(/Please enter valid cc emi value/i)).toBeInTheDocument();
    });
  });
});

describe('<PaymentMethodForm/>', () => {
  test('should show MDR rates and Affordibility category section if aggregator model selected', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
    );
    render(
      <PaymentMethodFormComponent
        methodForm={props.aggregatorMethodForm}
        onFieldCheckboxChange={props.onFieldCheckboxChange}
        onFieldInputChange={props.onFieldInputChange}
        onFormSubmitClick={props.onFormSubmitClick}
        onFileUploadChange={props.onFileUploadChange}
        isFormDisabled={false}
        handleViewBrandEMIForm={jest.fn()}
        hasAddedBrandEMIData={false}
        isPricingNcRaised={false}
        pricingNcCommentField={null}
      />,
    );
    expect(screen.getByRole('heading', { name: /^mdr rates$/i, exact: true })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /affordability category/i })).toBeInTheDocument();
  });

  test('should not show MDR rates if direct model selected', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'direct',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'direct',
        },
      }),
    );
    render(
      <PaymentMethodFormComponent
        methodForm={props.directMethodForm}
        onFieldCheckboxChange={props.onFieldCheckboxChange}
        onFieldInputChange={props.onFieldInputChange}
        onFormSubmitClick={props.onFormSubmitClick}
        onFileUploadChange={props.onFileUploadChange}
        isFormDisabled={false}
        hasAddedBrandEMIData={false}
        handleViewBrandEMIForm={jest.fn()}
        isPricingNcRaised={false}
        pricingNcCommentField={null}
      />,
    );
    expect(screen.queryByText(/mdr rates/i)).not.toBeInTheDocument();
  });

  test('should disable save and continue when editing rates', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
          agreementDocuments: [],
          vas_cc_emi_rate_field: '333.2',
        },
      }),
    );
    renderApp();
    const mdrEditBtn = screen.getByTestId('mdr-edit-save');
    expect(mdrEditBtn).toHaveTextContent(/edit/i);
    await userEvent.click(mdrEditBtn);
    expect(mdrEditBtn).toHaveTextContent(/save changes/i);
    const submitFormBtn = screen.getByRole('button', { name: /save & continue/i });
    expect(submitFormBtn).toBeDisabled();
  });

  test('should show pricing nc alert when pricing nc is raised and pricing nc comment field is available', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
    );
    render(
      <PaymentMethodFormComponent
        methodForm={props.aggregatorMethodForm}
        onFieldCheckboxChange={props.onFieldCheckboxChange}
        onFieldInputChange={props.onFieldInputChange}
        onFormSubmitClick={props.onFormSubmitClick}
        onFileUploadChange={props.onFileUploadChange}
        isFormDisabled={false}
        handleViewBrandEMIForm={jest.fn()}
        hasAddedBrandEMIData={false}
        isPricingNcRaised={true}
        pricingNcCommentField={{
          title: 'Pricing Needs Clarification',
          value: 'Please add correct custom proof',
        }}
      />,
    );
    expect(screen.getByRole('alert')).toHaveTextContent('Pricing Needs Clarification');
    expect(screen.getByRole('alert')).toHaveTextContent('Please add correct custom proof');
  });

  test('should not show pricing nc alert when pricing nc is not raised', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
    );
    render(
      <PaymentMethodFormComponent
        methodForm={props.aggregatorMethodForm}
        onFieldCheckboxChange={props.onFieldCheckboxChange}
        onFieldInputChange={props.onFieldInputChange}
        onFormSubmitClick={props.onFormSubmitClick}
        onFileUploadChange={props.onFileUploadChange}
        isFormDisabled={false}
        handleViewBrandEMIForm={jest.fn()}
        hasAddedBrandEMIData={false}
        isPricingNcRaised={false}
        pricingNcCommentField={{
          title: 'Pricing Needs Clarification',
          value: 'Please add correct custom proof',
        }}
      />,
    );
    expect(screen.queryByTestId('pricing-nc-alert')).not.toBeInTheDocument();
  });
});

describe('<NachForm/>', () => {
  test('should render nach form component', () => {
    const props = {
      nachForm: {
        [NachFormKeyNames.NACH_FORM_DOCUMENT_FIELD]: [
          {
            fileStoreId: 'frefer',
            name: 'ferrfer',
            size: 2112,
          },
        ],
        [NachFormKeyNames.NACH_FORM_COMMENTS_FIELD]: 'hello',
      },
      onNachTextAreaChange: jest.fn(),
      onNachFileUploadChange: jest.fn(),
      onNachSubmitClick: jest.fn(),
      onNachSkipClick: jest.fn(),
      isFormDisabled: false,
      removeExistingPricingDocs: jest.fn(),
      nachFields: [],
    };
    render(<NACHForm {...props} />);
    expect(screen.getByRole('heading', { name: /Upload NACH Form/i })).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toHaveTextContent('hello');
    expect(screen.getByRole('button', { name: /save & continue/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /skip & add later/i })).toBeInTheDocument();
  });
});
