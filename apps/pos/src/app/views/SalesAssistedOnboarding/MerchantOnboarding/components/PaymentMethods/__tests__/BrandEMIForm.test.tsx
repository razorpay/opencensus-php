import React from 'react';
import BrandEMIForm, {
  BrandEMIFormProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/BrandEMIForm';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import {
  getMockBrandEmiFields,
  MOCK_BRAND_RELATED_FIELDS,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/__tests__/mocks/fixtures';

const renderApp = (props) => {
  render(<BrandEMIForm {...props} />);
};

const commonProps = {
  storeTypes: [
    { label: 'Multi Brand Outlet', value: 'multi_brand_outlet' },
    { label: 'Exclusive Brand Outlet', value: 'Exclusive Brand Outlet' },
  ],
  brandNames: [
    {
      label: 'Vivo',
      value: 'vivo',
    },
    {
      label: 'Voltas',
      value: 'voltas',
    },
  ],
  submitBrandHandler: jest.fn(),
  isUpdateModularLoading: false,
  gstErrorMsg: '',
  onBrandEmiFieldInputChange: jest.fn(),
  handleBrandNameChange: jest.fn(),
  resetBrandRelatedFields: jest.fn(),
  isFormDisabled: false,
  optionalBrandFields: [],
};
describe('<BrandEMIForm/>', () => {
  test('should show brand emi form heading with store type and brand name fields', () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'HDIWJ33232',
      hasAddedBrandEMIData: false,
      brandEmiFields: getMockBrandEmiFields({}),
    };
    renderApp(props);
    expect(screen.getAllByLabelText(/Type of store/i)).toHaveLength(2);
    expect(screen.getAllByLabelText(/Brand name/i)).toHaveLength(2);
    expect(screen.getAllByText(/\*/i)).toHaveLength(4); // 2 for type-of-store + brand-name dropdown and 2 for dealer-code and gst-field
  });
  test('should show brand emi form heading with store type and brand name fields but dealer code is optional', () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'HDIWJ33232',
      hasAddedBrandEMIData: false,
      brandEmiFields: getMockBrandEmiFields({}),
      optionalBrandFields: [
        {
          name: 'dealer_code_field',
          isDisabled: false,
          isRequired: false,
          isHidden: false,
          meta: {
            title: 'Dealer Code',
            description: '',
            defaultValue: '',
            size: '',
            accessibilityLabel: '',
            hideOnReviewScreen: false,
            dataType: 'string',
            selectionType: '',
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: '',
        },
      ],
    };
    renderApp(props);
    expect(screen.getAllByLabelText(/Type of store/i)).toHaveLength(2);
    expect(screen.getAllByLabelText(/Brand name/i)).toHaveLength(2);
    expect(screen.getAllByText(/\*/i)).toHaveLength(3); // 2 for type-of-store + brand-name dropdown and 1 for gst-field since dealer-code is optional
  });
  test('should show brand emi form with selectable type of store', () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'HDIWJ33232',
      hasAddedBrandEMIData: false,
      brandEmiFields: getMockBrandEmiFields({ storeType: 'multi_brand_outlet' }),
    };
    renderApp(props);
    const storeTypeSelect = screen.getByRole('combobox', {
      name: /type of store required \*/i,
    });
    const brandNameSelect = screen.getByRole('combobox', {
      name: /brand name required \*/i,
    });
    expect(storeTypeSelect).toBeInTheDocument();
    expect(brandNameSelect).toBeInTheDocument();
  });
  test('should show brand emi form with a plain prefilled type of store field when a brand is already added', () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'HDIWJ33232',
      hasAddedBrandEMIData: true,
      brandEmiFields: getMockBrandEmiFields({
        storeType: 'multi_brand_outlet',
      }),
    };
    renderApp(props);
    expect(screen.getByText(/multi brand outlet/i)).toBeInTheDocument();
    const storeTypeSelect = screen.queryByRole('combobox', {
      name: /type of store required \*/i,
    });
    expect(storeTypeSelect).not.toBeInTheDocument();
  });
  test('should show related brand fields when a brand is selected', async () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'HDIWJ33232',
      hasAddedBrandEMIData: true,
      brandEmiFields: getMockBrandEmiFields({
        storeType: 'multi_brand_outlet',
        brandName: 'vivo',
      }),
      brandNames: [
        { label: 'Samsung', value: 'samsung' },
        { label: 'Vivo', value: 'vivo' },
      ],
    };
    renderApp(props);
    const brandNameSelect = screen.getByTestId('brand-name-select');
    await userEvent.click(brandNameSelect);
    await screen.getByText(/select option/i).click();
    await userEvent.click(screen.getByRole('option', { name: 'Vivo' }));
    const dealerCodeField = screen.getByRole('textbox', {
      name: /dealer code required \*/i,
    });
    expect(dealerCodeField).toBeInTheDocument();
  });
  test('All brand fields should be disabled if brand data fields has merchantgstfield but merchant has not provided gst in kyc step', async () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: '',
      hasAddedBrandEMIData: true,
      brandEmiFields: getMockBrandEmiFields({
        storeType: 'multi_brand_outlet',
        brandName: 'vivo',
      }),
      brandNames: [
        { label: 'Samsung', value: 'samsung' },
        { label: 'Vivo', value: 'vivo' },
      ],
      gstErrorMsg: 'you cannot apply for brand emi with brand because you don’t have gstin',
    };
    renderApp(props);
    const brandNameSelect = screen.getByTestId('brand-name-select');
    await userEvent.click(brandNameSelect);
    await screen.getByText(/select option/i).click();
    await userEvent.click(screen.getByRole('option', { name: 'Vivo' }));
    const merchantGstField = screen.getByRole('textbox', {
      name: /merchant gst required \*/i,
    });
    expect(merchantGstField).toBeDisabled();
  });
  test('merchant gst field should be prefilled and disabled if brand data fields has merchantgstfield and value is present', async () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'ABC123',
      hasAddedBrandEMIData: true,
      brandEmiFields: getMockBrandEmiFields({
        storeType: 'multi_brand_outlet',
        brandName: 'vivo',
      }),
      gstErrorMsg: '',
    };
    renderApp(props);
    const merchantGstField = screen.getByRole('textbox', {
      name: /merchant gst required \*/i,
    });
    expect(merchantGstField).toBeDisabled();
    expect(merchantGstField).toHaveDisplayValue('ABC123');
  });
  test('should disable save btn when kyc qualified', async () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'ABC123',
      hasAddedBrandEMIData: true,
      brandEmiFields: getMockBrandEmiFields({
        storeType: 'multi_brand_outlet',
        brandName: 'vivo',
      }),
      gstErrorMsg: '',
      isFormDisabled: true,
    };
    renderApp(props);
    expect(screen.getByRole('button', { name: /save/i })).not.toBeEnabled();
  });
  test('should not disable save btn when not kyc qualified', async () => {
    const props: BrandEMIFormProps = {
      ...commonProps,
      brandRelatedFields: MOCK_BRAND_RELATED_FIELDS,
      merchantGstNumber: 'ABC123',
      hasAddedBrandEMIData: true,
      brandEmiFields: getMockBrandEmiFields({
        storeType: 'multi_brand_outlet',
        brandName: 'vivo',
      }),
      gstErrorMsg: '',
    };
    renderApp(props);
    expect(screen.getByRole('button', { name: /save/i })).toBeEnabled();
  });
});
