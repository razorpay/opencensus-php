import {
  getIsOtherDocumentInRevampFlow,
  defaultFileTypesIERevamp,
  getFormSchema,
} from 'merchant/views/Settings/Configuration/Questionnaire/utils';

describe('International enablement form tils', () => {
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
    test.each([[true], [false]])(
      'should contain common fields when IE revamp is %s',
      (isIERevamp) => {
        const fieldsList = getFormSchema(isIERevamp).fields;
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
      },
    );

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
});
