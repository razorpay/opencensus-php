import { toBase64 } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils';

describe('/utils', () => {
  test('toBase64', async () => {
    const result = await toBase64(
      new File(['foo'], 'foo.txt', {
        type: 'text/plain',
      }),
    );
    expect(result).toBe('data:text/plain;base64,Zm9v');
  });
});
