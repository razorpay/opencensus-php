import { checkIfFilterValid } from 'merchant/views/Transactions/v1/SuccessRate/helper';

describe('SR Dashboard helper methods', () => {
  test('checkIfFilterValid should return true as default', () => {
    const response = checkIfFilterValid({
      activeTab: 'Card',
      filter: 'credit',
    });
    expect(!!response).toBe(true);
  });

  test('checkIfFilterValid should return correct bool if tab is Card and filter is international based on internationalEnabled flag', () => {
    const falseResponse = checkIfFilterValid({
      activeTab: 'Card',
      filter: 'international',
    });
    expect(!!falseResponse).toBe(false);

    const trueResponse = checkIfFilterValid({
      activeTab: 'Card',
      filter: 'international',
      flags: {
        isInternationalEnabled: true,
      },
    });
    expect(!!trueResponse).toBe(true);
  });
});
