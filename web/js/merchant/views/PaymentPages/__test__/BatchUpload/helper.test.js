import { getHeaderList } from 'merchant/views/PaymentPages/BatchUpload/helper';
import {
  ENTITY_DETAILS,
  HEADER_LIST,
} from 'merchant/views/PaymentPages/__test__/mocks/fixtures/BatchUpload/helper';

describe('getHeaderList', () => {
  it('should return header list with PID, SID & Amount', () => {
    expect(getHeaderList(ENTITY_DETAILS)).toStrictEqual(HEADER_LIST.WITH_PID_SID_AMOUNT);
  });

  it('should return header list with PID, SID & withut Amount', () => {
    const entityDetails = ENTITY_DETAILS;
    delete entityDetails.payment_page_items;
    expect(getHeaderList(ENTITY_DETAILS)).toStrictEqual(HEADER_LIST.WITH_PID_SID);
  });

  it('should not throw any error if details are missing', () => {
    const entityDetails = ENTITY_DETAILS;
    delete entityDetails.payment_page_items;
    expect(getHeaderList()).toStrictEqual([]);
  });
});
