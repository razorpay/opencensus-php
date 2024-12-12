import {
  filterPopularGateways,
  handleTPVFeatures,
} from 'merchant/views/Optimizer/OnBoarding/utils';

describe('filterPopularGateways', () => {
  it('should return supported gateways list without popular gateways', () => {
    const supportedGateways = {
      payu: {
        'Gateway Name': {
          data_value: 'PayU',
        },
      },
      atom: {
        'Gateway Name': {
          data_value: 'Atom',
        },
      },
    };
    expect(filterPopularGateways(supportedGateways)).toEqual([
      {
        label: 'Atom',
        value: 'atom',
      },
    ]);
  });
});

describe('handleTPVFeatures', () => {
  it('should return correct payload for natbanking tpv', () => {
    const payload = {
      Gateway_details: {
        'Payment Methods': ['netbanking'],
      },
    };
    const gateway = 'billdesk_optimizer';
    expect(handleTPVFeatures(payload, gateway)).toEqual({
      Gateway_details: {
        'Payment Methods': ['netbanking'],
        'Netbanking Features': { tpv: 0 },
      },
    });
  });

  it('should return correct payload for upi tpv', () => {
    const payload = {
      Gateway_details: {
        'Payment Methods': ['upi'],
      },
    };
    const gateway = 'billdesk_optimizer';
    expect(handleTPVFeatures(payload, gateway)).toEqual({
      Gateway_details: {
        'Payment Methods': ['upi'],
        'UPI Features': { tpv: 0 },
      },
    });
  });
});
