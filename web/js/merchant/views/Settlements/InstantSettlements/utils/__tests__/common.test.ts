import {
  getIsGlobalLimitBreached,
  getIsMerchantLimitBreached,
  getHasMerchantLevelLimit,
  getIsMerchantLimitBreachedNew,
  getIsGlobalLimitBreachedNew,
} from 'merchant/views/Settlements/InstantSettlements/utils/common';

describe('IS/utils/common', () => {
  test('getHasMerchantLevelLimit', () => {
    expect(getHasMerchantLevelLimit(undefined)).toBe(false);
    expect(getHasMerchantLevelLimit(null)).toBe(false);
    expect(getHasMerchantLevelLimit(0)).toBe(true);
    expect(getHasMerchantLevelLimit(100)).toBe(true);
  });

  test('getIsMerchantLimitBreached', () => {
    expect(getIsMerchantLimitBreached(undefined)).toBe(false);
    expect(getIsMerchantLimitBreached({ disable: false, blocked: false })).toBe(false);
    // disable must be true along with valid available_limit and max_limit values
    expect(
      getIsMerchantLimitBreached({
        disable: false,
        blocked: false,
        available_limit: 0,
        max_limit: 100,
      }),
    ).toBe(false);
    expect(
      getIsMerchantLimitBreached({
        disable: true,
        blocked: false,
        available_limit: 90,
        max_limit: 100,
      }),
    ).toBe(false);
    expect(
      getIsMerchantLimitBreached({
        disable: true,
        blocked: false,
        available_limit: 0,
        max_limit: 100,
      }),
    ).toBe(true);
    expect(getIsMerchantLimitBreached({ disable: true, blocked: false, available_limit: 0 })).toBe(
      true,
    );
  });

  test('getIsMerchantLimitBreachedNew', () => {
    expect(getIsMerchantLimitBreachedNew(undefined)).toBe(false);
    // expect(getIsMerchantLimitBreachedNew({ limit_breached: false, blocked: false })).toBe(false);
    // limit_breached must be true along with valid available_limit and max_limit values
    expect(
      getIsMerchantLimitBreachedNew({
        limit_breached: false,
        blocked: false,
        available_limit: 0,
        max_limit_per_working_day: 100,
      }),
    ).toBe(false);
    expect(
      getIsMerchantLimitBreachedNew({
        limit_breached: true,
        blocked: false,
        available_limit: 90,
        max_limit_per_working_day: 100,
      }),
    ).toBe(false);
    expect(
      getIsMerchantLimitBreachedNew({
        limit_breached: true,
        blocked: false,
        available_limit: 0,
        max_limit_per_working_day: 100,
      }),
    ).toBe(true);
    expect(
      getIsMerchantLimitBreachedNew({ limit_breached: true, blocked: false, available_limit: 0 }),
    ).toBe(true);
  });

  test('getIsGlobalLimitBreached', () => {
    expect(getIsGlobalLimitBreached(undefined)).toBe(false);
    expect(getIsGlobalLimitBreached({ disable: false, blocked: false })).toBe(false);
    expect(getIsGlobalLimitBreached({ disable: true, blocked: false })).toBe(true);
    // disable must be true along with valid available_limit and max_limit values
    expect(
      getIsGlobalLimitBreached({
        disable: false,
        blocked: false,
        available_limit: 0,
        max_limit: 100,
      }),
    ).toBe(false);
    expect(
      getIsGlobalLimitBreached({
        disable: true,
        blocked: false,
        available_limit: 90,
        max_limit: 100,
      }),
    ).toBe(true);
    // false incase of mid limit breached
    expect(
      getIsGlobalLimitBreached({
        disable: true,
        blocked: false,
        available_limit: 0,
        max_limit: 100,
      }),
    ).toBe(false);
  });

  test('getIsGlobalLimitBreachedNew', () => {
    expect(getIsGlobalLimitBreachedNew(undefined)).toBe(false);
    expect(getIsGlobalLimitBreachedNew({ limit_breached: false, blocked: false })).toBe(false);
    expect(getIsGlobalLimitBreachedNew({ limit_breached: true, blocked: false })).toBe(true);
    // limit_breached must be true along with valid available_limit and max_limit values
    expect(
      getIsGlobalLimitBreachedNew({
        limit_breached: false,
        blocked: false,
        available_limit: 0,
        max_limit_per_working_day: 100,
      }),
    ).toBe(false);
    expect(
      getIsGlobalLimitBreachedNew({
        limit_breached: true,
        blocked: false,
        available_limit: 90,
        max_limit_per_working_day: 100,
      }),
    ).toBe(true);
    // false incase of mid limit breached
    expect(
      getIsGlobalLimitBreachedNew({
        limit_breached: true,
        blocked: false,
        available_limit: 0,
        max_limit_per_working_day: 100,
      }),
    ).toBe(false);
  });
});
