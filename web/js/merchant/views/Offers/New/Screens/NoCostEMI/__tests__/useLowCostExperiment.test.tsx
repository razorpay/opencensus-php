import { renderHook } from '@testing-library/react-hooks';

import { useSplitzService } from 'common/splitz';
import { isLowCostExperimentEnabled } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';

import { useLowCostOfferExperiment } from '../useLowCostOfferExperiment';

jest.mock('common/splitz');
jest.mock('merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper');

describe('useLowCostOfferExperiment', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('returns true when Low_cost_offer experiment is enabled', () => {
    (useSplitzService as jest.Mock).mockReturnValue({
      abExperiments: { Low_cost_offer: true },
    });
    (isLowCostExperimentEnabled as jest.Mock).mockReturnValue(true);

    const { result } = renderHook(() => useLowCostOfferExperiment());

    expect(result.current.isLowCostEnabled).toBe(true);
    expect(isLowCostExperimentEnabled).toHaveBeenCalledWith(true);
  });

  test('returns false when Low_cost_offer experiment is not enabled', () => {
    (useSplitzService as jest.Mock).mockReturnValue({
      abExperiments: { Low_cost_offer: false },
    });
    (isLowCostExperimentEnabled as jest.Mock).mockReturnValue(false);

    const { result } = renderHook(() => useLowCostOfferExperiment());

    expect(result.current.isLowCostEnabled).toBe(false);
    expect(isLowCostExperimentEnabled).toHaveBeenCalledWith(false);
  });

  test('handles undefined Low_cost_offer experiment correctly', () => {
    (useSplitzService as jest.Mock).mockReturnValue({
      abExperiments: { Low_cost_offer: undefined },
    });
    (isLowCostExperimentEnabled as jest.Mock).mockReturnValue(false);

    const { result } = renderHook(() => useLowCostOfferExperiment());

    expect(result.current.isLowCostEnabled).toBe(false);
    expect(isLowCostExperimentEnabled).toHaveBeenCalledWith(undefined);
  });
});
