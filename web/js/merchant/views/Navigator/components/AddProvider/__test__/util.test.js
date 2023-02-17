import { TPV_OPTIONS } from 'merchant/views/Navigator/constants';
import { getTPVOptions } from 'merchant/views/Navigator/components/AddProvider/util';

describe('merchant/views/Navigator/components/AddProvider/util', () => {
  const TPVOptions = getTPVOptions([0, 1]);

  test('should return tpv options having array of object with keys label and value', () => {
    expect(TPVOptions).toEqual([
      { label: TPV_OPTIONS[0], value: 0 },
      { label: TPV_OPTIONS[1], value: 1 },
    ]);
  });

  test('should return tpv options array of same length as the passed parameter length', () => {
    expect(TPVOptions).toHaveLength(2);
  });

  test('should return empty string label if tpv option doesnt support that number array', () => {
    const TPVOptions = getTPVOptions([3]);

    expect(TPVOptions).toEqual([{ label: '', value: 3 }]);
  });
});
