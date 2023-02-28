import { createHubspotPayload } from 'merchant/views/MagicCheckout/LeadForm/utils';
import { reqPayload } from './mocks/constants';

const formData = {
  name: 'xyz',
  email: 'xyz@xyz.com',
  phone: '9999999991',
  url: 'https://xyz.com',
  mId: '10000000000000',
  stack: 'Shopify',
  cod: 'Yes',
  gmv: 'Less than 5 lacs',
};

jest.mock('common/utils/cookies', () => ({
  getCookie: jest.fn(() => 'hutkcookie'),
}));

describe('LeadForm utils tests', () => {
  test('should return expected payload', () => {
    expect(createHubspotPayload(formData)).toEqual(reqPayload);
  });
});
