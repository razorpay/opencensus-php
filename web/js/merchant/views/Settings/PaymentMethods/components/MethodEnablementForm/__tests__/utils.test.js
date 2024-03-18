import * as Yup from 'yup';

import { PRODUCT } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import {
  getAdditionalDocumentsBasedOnBusinessType,
  getFormSchema,
  generateApiData,
  getFormData,
  formatApiResponse,
  formatIntlFormDataTrackingObject,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/utils';

describe('Tests for getAdditionalDocumentsBasedOnBusinessType', () => {
  test('Should return array of documents if business type is passed is of type string', () => {
    const additionalDocuments = getAdditionalDocumentsBasedOnBusinessType('5');
    expect(Array.isArray(additionalDocuments)).toBe(true);
  });

  test('Should return array of documents in correct format', () => {
    const additionalDocuments = getAdditionalDocumentsBasedOnBusinessType('5');
    expect(additionalDocuments).toEqual(
      expect.arrayContaining([
        expect.objectContaining({ label: expect.any(String), name: expect.any(String) }),
      ]),
    );
  });

  test('Should return array of documents if business type passed is of type number', () => {
    const additionalDocuments = getAdditionalDocumentsBasedOnBusinessType(5);
    expect(Array.isArray(additionalDocuments)).toBe(true);
  });

  test('Should return undefined if business type is not valid', () => {
    const additionalDocuments = getAdditionalDocumentsBasedOnBusinessType(13);
    expect(additionalDocuments).toBe(undefined);
  });
});

describe('Tests for getFormSchema', () => {
  test('Returns default form schema for an unknown business type', () => {
    const businessType = 'unknownBusinessType';

    const result = getFormSchema(businessType);

    // Ensure the returned schema is a Yup schema
    expect(Yup.object().isValidSync(result)).toBe(true);
    expect(result.fields.documents).toBeUndefined();
  });
});

describe('Tests for generateApiData', () => {
  test('Generates API data with default values', () => {
    const apiData = {};
    const formData = {};
    const result = generateApiData(apiData, formData);

    expect(result).toEqual({
      products: [PRODUCT],
      documents: {},
      accepts_intl_txns: 0,
      business_use_case:
        'International is already enabled, Requesting for Other International PA CB Methods Enablement',
      version: 'v2',
      goods_type: 'both',
      existing_risk_checks: ['None'],
    });
  });

  test('Generates API data with specified values', () => {
    const apiData = {
      documents: { exampleDocument: 'Example Content' },
      accepts_intl_txns: true,
      goods_type: 'physical',
      business_use_case: 'Example Business Use Case',
      existing_risk_checks: ['RiskCheck1', 'RiskCheck2'],
    };
    const formData = {
      documents: { newDocument: 'New Content' },
    };
    const result = generateApiData(apiData, formData);

    expect(result).toEqual({
      products: [PRODUCT],
      documents: { exampleDocument: 'Example Content', newDocument: 'New Content' },
      accepts_intl_txns: 1,
      version: 'v2',
      goods_type: 'physical',
      business_use_case: 'Example Business Use Case',
      existing_risk_checks: ['RiskCheck1', 'RiskCheck2'],
    });
  });
});

describe('Tests for getFormData', () => {
  test('Should return empty object when products do not include products_pa_cb', () => {
    const apiData = {
      products: ['AnotherProduct'],
      documents: { exampleDocument: 'Example Content' },
    };
    const result = getFormData(apiData);
    expect(result).toEqual({});
  });

  test('Should return documents when products include products_pa_cb', () => {
    const apiData = {
      products: [PRODUCT],
      documents: { exampleDocument: 'Example Content' },
    };
    const result = getFormData(apiData);
    expect(result).toEqual({ exampleDocument: 'Example Content' });
  });

  test('Should return empty object when products are undefined', () => {
    const apiData = {
      documents: { exampleDocument: 'Example Content' },
    };
    const result = getFormData(apiData);
    expect(result).toEqual({});
  });

  test('Should return empty object when apiData is undefined', () => {
    const result = getFormData(undefined);
    expect(result).toEqual({});
  });
});

describe('Tests for formatApiResponse', () => {
  test('Should delete timestamp properties and returns formatted data', () => {
    const apiData = {
      submitted_at: '2024-01-29T12:34:56Z',
      updated_at: '2024-01-29T12:45:00Z',
      created_at: '2024-01-29T12:00:00Z',
      documents: { exampleDocument: 'Example Content' },
    };

    const result = formatApiResponse(apiData);

    expect(result.submitted_at).toBeUndefined();
    expect(result.updated_at).toBeUndefined();
    expect(result.created_at).toBeUndefined();

    expect(result).toEqual({
      documents: { exampleDocument: 'Example Content' },
    });
  });

  test('Should delete formatted object if only one timestamp property is present', () => {
    const apiData = {
      created_at: '2024-01-29T12:00:00Z',
      documents: { exampleDocument: 'Example Content' },
    };

    const result = formatApiResponse(apiData);

    expect(result.created_at).toBeUndefined();

    expect(result).toEqual({
      documents: { exampleDocument: 'Example Content' },
    });
  });

  test('Should return empty object when apiData is undefined', () => {
    const result = formatApiResponse(undefined);
    expect(result).toEqual(undefined);
  });
});

describe('Tests for formatIntlFormDataTrackingObject', () => {
  test('Should not throw an error if nothing is passed', () => {
    expect(formatIntlFormDataTrackingObject()).toBeDefined();
  });

  test('Should format the passed object', () => {
    const data = {
      name: 'dummy name',
      documents: {
        dummy_doc: 'true',
      },
    };
    expect(formatIntlFormDataTrackingObject(data)).toStrictEqual({
      ...data,
      dummy_doc: true,
    });
  });

  test('Should return same object if documents doesnt exist', () => {
    const data = {
      name: 'dummy name',
    };
    expect(formatIntlFormDataTrackingObject(data)).toStrictEqual(data);
  });
});
