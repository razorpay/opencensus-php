import {
  UPLOAD_AIRWAY_BILL,
  UPLOAD_INVOICE_BILL,
} from 'merchant/views/Transactions/v1/UploadInvoice/BulkUpload/constants';
import { getPurpose, getTabs } from 'merchant/views/Transactions/v1/UploadInvoice/BulkUpload/utils';
import { UPLOAD_INVOICES_TYPE } from 'merchant/views/Transactions/v1/UploadInvoice/components/constants';

describe('Tests for getPurpose function', () => {
  test.each([
    { tags: ['someTag', 'enable_jpmc_import_flow'], tab: 1, expected: UPLOAD_INVOICES_TYPE.JPMC },
    { tags: ['someTag', 'anotherTag'], tab: 0, expected: UPLOAD_INVOICES_TYPE.OPGSP_INVOICE },
    { tags: ['someTag', 'anotherTag'], tab: 1, expected: UPLOAD_INVOICES_TYPE.OPGSP_AWB },
    { tags: null, tab: 1, expected: UPLOAD_INVOICES_TYPE.OPGSP_AWB },
    { tags: undefined, tab: 1, expected: UPLOAD_INVOICES_TYPE.OPGSP_AWB },
    { tags: null, tab: 0, expected: UPLOAD_INVOICES_TYPE.OPGSP_INVOICE },
    { tags: undefined, tab: 0, expected: UPLOAD_INVOICES_TYPE.OPGSP_INVOICE },
  ])('returns $expected when tags are $tags and tab is $tab', ({ tags, tab, expected }) => {
    const result = getPurpose(tags, tab);
    expect(result).toBe(expected);
  });
});

describe('getTabs', () => {
  test.each([
    { purposeCode: 'S0101', expected: [UPLOAD_INVOICE_BILL, UPLOAD_AIRWAY_BILL] },
    { purposeCode: 'S0102', expected: [UPLOAD_INVOICE_BILL, UPLOAD_AIRWAY_BILL] },
    { purposeCode: 'NON_AWB_CODE', expected: [UPLOAD_INVOICE_BILL] },
    { purposeCode: '', expected: [UPLOAD_INVOICE_BILL] },
    { purposeCode: null, expected: [UPLOAD_INVOICE_BILL] },
    { purposeCode: undefined, expected: [UPLOAD_INVOICE_BILL] },
  ])('returns $expected when purposeCode is $purposeCode', ({ purposeCode, expected }) => {
    const result = getTabs(purposeCode);
    expect(result).toEqual(expected);
  });
});
