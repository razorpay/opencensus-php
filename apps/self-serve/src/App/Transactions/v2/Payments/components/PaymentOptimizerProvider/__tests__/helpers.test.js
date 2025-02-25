import { findProviderDetails } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentOptimizerProvider/helper';
import { toTitleCase } from '@libs/shared-utils';

describe('findProviderDetails', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  it('should return Razorpay provider when terminal_id is Razorpay', () => {
    const result = findProviderDetails([], 'Razorpay', null);
    expect(result).toEqual({
      Provider_name: 'Razorpay',
      Gateway: 'razorpay',
    });
  });

  it('should return provider details from terminalProviders based on terminal_id', () => {
    const terminalProviders = [
      { Terminal_id: 'Provider1', Provider_name: 'Provider 1', Gateway: 'gateway1' },
      { Terminal_id: 'Provider2', Provider_name: 'Provider 2', Gateway: 'gateway2' },
    ];
    const result = findProviderDetails(terminalProviders, 'Provider1', null);
    expect(result).toEqual({
      Terminal_id: 'Provider1',
      Provider_name: 'Provider 1',
      Gateway: 'gateway1',
    });
  });

  it('should return null if terminal_id is not found in terminalProviders and settled_by is null', () => {
    const terminalProviders = [
      { Terminal_id: 'Provider1', Provider_name: 'Provider 1', Gateway: 'gateway1' },
    ];
    const result = findProviderDetails(terminalProviders, 'NonExistentProvider', null);
    expect(result).toBeUndefined();
  });

  it('should return provider details using settled_by when terminal_id is not found', () => {
    toTitleCase.mockReturnValue('Settled By Name');
    const result = findProviderDetails([], null, 'settled_by_value');
    expect(result).toEqual({
      Provider_name: 'Settled By Name',
      Gateway: 'settled_by_value',
    });
    expect(toTitleCase).toHaveBeenCalledWith('settled_by_value');
  });

  it('should return provider with Gateway as "razorpay" when settled_by is Razorpay', () => {
    toTitleCase.mockReturnValue('Razorpay');
    const result = findProviderDetails([], null, 'Razorpay');
    expect(result).toEqual({
      Provider_name: 'Razorpay',
      Gateway: 'razorpay',
    });
    expect(toTitleCase).toHaveBeenCalledWith('Razorpay');
  });

  it('should return null when terminal_id and settled_by are both null', () => {
    const result = findProviderDetails([], null, null);
    expect(result).toBeNull();
  });

  it('should return the first matched provider from terminalProviders if multiple matches are found', () => {
    const terminalProviders = [
      { Terminal_id: 'Provider1', Provider_name: 'Provider 1', Gateway: 'gateway1' },
      { Terminal_id: 'Provider1', Provider_name: 'Provider 1 Duplicate', Gateway: 'gateway1' },
    ];
    const result = findProviderDetails(terminalProviders, 'Provider1', null);
    expect(result).toEqual({
      Terminal_id: 'Provider1',
      Provider_name: 'Provider 1',
      Gateway: 'gateway1',
    });
  });
});
