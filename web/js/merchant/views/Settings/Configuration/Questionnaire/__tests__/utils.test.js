// testable
import {
  modelFormData,
  modelFormDataBeforeSave,
  getAvailableFileTypes,
  getProductValue,
  defaultFileTypesIERevamp,
  getIsOtherDocumentInRevampFlow,
  getFormSchema,
  getAdditionalDocumentsBasedOnBusinessType,
  isAnyIntlProductEnabled,
  computePurposeCodeSearch,
  computePurposeCodeOptions,
} from 'merchant/views/Settings/Configuration/Questionnaire/utils';

describe('Test modelFormData util', () => {
  test('Should throw error if data is not provided', () => {
    expect(() => modelFormData()).toThrow();
  });

  test('Should return default if data is empty', () => {
    expect(modelFormData({})).toStrictEqual({
      accepts_intl_txns: 'undefined',
      documents: {},
    });
  });

  test('Should return business txn size in the data', () => {
    expect(
      modelFormData({
        accepts_intl_txns: 1,
        business_txn_size_min: 1,
        business_txn_size_max: 10,
        created_at: '<date>',
        submitted_at: '<date>',
        updated_at: '<date>',
        documents: {
          a: 'a',
          b: 'b',
        },
      }),
    ).toStrictEqual({
      accepts_intl_txns: '1',
      business_txn_size: '1=10',
      documents: {
        a: 'a',
        b: 'b',
      },
    });
  });
});

describe('Test modelFormDataBeforeSave util', () => {
  test('Should throw error if formData is not provided', () => {
    expect(() => modelFormDataBeforeSave()).toThrow();
  });

  test('Should return default formData', () => {
    expect(modelFormDataBeforeSave({})).toStrictEqual({
      accepts_intl_txns: 0,
      allowed_currencies: null,
      contact_us_link: null,
      customer_info_collected: null,
      business_txn_size_max: null,
      business_txn_size_min: null,
      logistic_partners: null,
      monthly_sales_intl_cards_max: null,
      monthly_sales_intl_cards_min: null,
      partner_details_plugins: null,
      privacy_policy_link: null,
      refund_and_cancellation_policy_link: null,
      shipping_policy_link: null,
      social_media_page_link: null,
      terms_and_conditions_link: null,
      products: [],
    });
  });

  test('Should return business_txn_size in formData', () => {
    expect(
      modelFormDataBeforeSave({
        business_txn_size: '1=10',
        accepts_intl_txns: 'true',
        submit: true,
        products: 'product1,product2',
      }),
    ).toStrictEqual({
      accepts_intl_txns: 1,
      business_txn_size_min: '1',
      business_txn_size_max: '10',
      allowed_currencies: null,
      contact_us_link: null,
      customer_info_collected: null,
      logistic_partners: null,
      monthly_sales_intl_cards_max: null,
      monthly_sales_intl_cards_min: null,
      partner_details_plugins: null,
      privacy_policy_link: null,
      refund_and_cancellation_policy_link: null,
      shipping_policy_link: null,
      social_media_page_link: null,
      terms_and_conditions_link: null,
      products: ['product1', 'product2'],
    });
  });
});

describe('Test getAvailableFileTypes util', () => {
  test('Should throw error if formikProps is not provided', () => {
    expect(() => getAvailableFileTypes()).toThrow();
  });

  test('Should return filetypes', () => {
    expect(
      getAvailableFileTypes({
        values: {
          accepts_intl_txns: 'true',
          documents: {
            bank_statement_inward_remittance: 1,
            others: {
              1: 'other1',
            },
          },
        },
      }),
    ).toStrictEqual([
      [
        { label: 'FIRC', name: 'firc' },
        { label: 'I/E Code', name: 'ie_code' },
        { label: 'Invoices', name: 'invoices' },
      ],
      [
        { label: 'Bank Statement for Inward Remittance', name: 'bank_statement_inward_remittance' },
        { label: '1', name: '1' },
      ],
    ]);
  });
});

describe('Test getProductValue util', () => {
  test('Should return the product value', () => {
    expect(getProductValue()).toStrictEqual('payment_links,payment_pages,invoices');
    expect(getProductValue('pg')).toStrictEqual('payment_gateway');
    expect(getProductValue(1)).toStrictEqual('payment_links,payment_pages,invoices');
  });
});

describe('getIsOtherDocumentInRevampFlow', () => {
  const defaultFileTypesList = defaultFileTypesIERevamp.map((fileInfo) => [false, fileInfo.name]);

  test.each([
    ...defaultFileTypesList,
    [false, 'current_payment_partner_settlement_record'],
    [true, 'iata'],
  ])('should return %s on passing %s', (output, docType) => {
    expect(getIsOtherDocumentInRevampFlow(docType)).toBe(output);
  });
});

describe('getFormSchema', () => {
  test.each([
    [true, false],
    [false, true],
    [true, true],
  ])('should contain common fields when IE revamp is %s', (isIERevamp, businessType) => {
    const fieldsList = getFormSchema(isIERevamp, businessType ? '1' : null).fields;
    [
      'products',
      'goods_type',
      'business_use_case',
      'business_txn_size',
      'existing_risk_checks',
      'accepts_intl_txns',
      'import_export_code',
      'submit',
    ].forEach((field) => {
      expect(fieldsList[field]).toBeDefined();
    });
  });

  test('should contain about us link and documents when IE revamp is true', () => {
    const fieldsList = getFormSchema(true).fields;
    expect(fieldsList.about_us_link).toBeDefined();
    expect(fieldsList.documents).toBeDefined();
    [
      'bank_statement_inward_remittance',
      'invoices',
      'current_payment_partner_settlement_record',
      'firc',
    ].forEach((document) => {
      expect(fieldsList.documents.fields[document]).toBeDefined();
    });
  });
});

describe('Test getAdditionalDocumentsBasedOnBusinessType util', () => {
  test('Should return default documents if business type is not provided', () => {
    expect(getAdditionalDocumentsBasedOnBusinessType({})).toStrictEqual([
      {
        options: [
          { label: 'Select', name: '' },
          { label: 'Forward inward remittance statement', name: 'firc' },
        ],
        type: 'select',
      },
    ]);
  });

  test('Should return default documents if valid business type is not provided', () => {
    expect(getAdditionalDocumentsBasedOnBusinessType({ businessType: 'gaming' })).toStrictEqual([
      {
        options: [
          { label: 'Select', name: '' },
          { label: 'Forward inward remittance statement', name: 'firc' },
        ],
        type: 'select',
      },
    ]);
  });

  test('Should return additional documents if business type is provided', () => {
    expect(getAdditionalDocumentsBasedOnBusinessType({ businessType: '4' })).toStrictEqual([
      {
        label: 'Articles of Association',
        name: 'aoa',
      },
      { label: 'Memorandom of Association', name: 'moa' },
    ]);
  });
});

describe('Tests for isAnyIntlProductEnabled', () => {
  test.each([
    [undefined, false], // No product status provided
    [
      {
        // None of the products are approved
        invoices: 'pending',
        payment_gateway: 'rejected',
        payment_pages: 'pending',
      },
      false,
    ],
    [
      {
        // At least one product is approved
        invoices: 'pending',
        payment_gateway: 'approved',
        payment_pages: 'rejected',
      },
      true,
    ],
    [
      {
        // All products are approved
        invoices: 'approved',
        payment_gateway: 'approved',
        payment_pages: 'approved',
      },
      true,
    ],
  ])('should return %s when product status is %p', (productStatus, expectedResult) => {
    expect(isAnyIntlProductEnabled(productStatus)).toBe(expectedResult);
  });
});

describe('computeSearch', () => {
  const source = [
    {
      codes: [
        { purposeCode: 'P001', description: 'Description 1' },
        { purposeCode: 'P002', description: 'Description 2' },
      ],
    },
    {
      codes: [
        { purposeCode: 'P003', description: 'Description 3' },
        { purposeCode: 'P004', description: 'Description 4' },
      ],
    },
  ];

  test('returns empty array if search string is empty', () => {
    const result = computePurposeCodeSearch(source, '');
    expect(result).toEqual([]);
  });

  test('returns matching codes based on purposeCode or description', () => {
    const result1 = computePurposeCodeSearch(source, 'P001');
    expect(result1).toEqual([{ purposeCode: 'P001', description: 'Description 1' }]);

    const result2 = computePurposeCodeSearch(source, 'Description 4');
    expect(result2).toEqual([{ purposeCode: 'P004', description: 'Description 4' }]);

    const result3 = computePurposeCodeSearch(source, 'p002');
    expect(result3).toEqual([{ purposeCode: 'P002', description: 'Description 2' }]);
  });

  test('returns empty array if no matches are found', () => {
    const result = computePurposeCodeSearch(source, 'Non-existent');
    expect(result).toEqual([]);
  });

  test('ignores case sensitivity in search', () => {
    const result1 = computePurposeCodeSearch(source, 'p001');
    expect(result1).toEqual([{ purposeCode: 'P001', description: 'Description 1' }]);

    const result2 = computePurposeCodeSearch(source, 'DESCRIPTION 4');
    expect(result2).toEqual([{ purposeCode: 'P004', description: 'Description 4' }]);
  });
});

describe('computeRadioOptions', () => {
  const purposeCodes = [
    { purposeCode: 'P001', description: 'Description 1' },
    { purposeCode: 'P002', description: 'Description 2' },
    { purposeCode: 'P003', description: 'Description 3' },
  ];

  test('returns radio options with selected code', () => {
    const selectedPurposeCode = 'P002';
    const result = computePurposeCodeOptions(purposeCodes, selectedPurposeCode);
    expect(result).toEqual([{ value: 'P002', label: 'P002 - Description 2' }]);
  });

  test('returns radio options without selected code', () => {
    const selectedPurposeCode = 'P004';
    const result = computePurposeCodeOptions(purposeCodes, selectedPurposeCode);
    expect(result).toEqual([
      { value: 'P001', label: 'P001 - Description 1' },
      { value: 'P002', label: 'P002 - Description 2' },
      { value: 'P003', label: 'P003 - Description 3' },
    ]);
  });

  test('returns empty array if purposeCodes is empty', () => {
    const selectedPurposeCode = 'P001';
    const result = computePurposeCodeOptions([], selectedPurposeCode);
    expect(result).toEqual([]);
  });
});
