import {
  transfeeRuleToApiFormat,
  transfeeRuleToNormalFormat,
} from 'merchant/views/PaymentPages/PaymentPages/helpers';

describe('transfeeRuleToApiFormat', () => {
  it('should convert flat fee from rupee to paise', () => {
    const rule = {
      rule_type: 'flat',
      flat: 10,
      slabs: [],
    };
    const expected = {
      rule_type: 'flat',
      flat: 1000,
      slabs: [],
    };
    expect(transfeeRuleToApiFormat(rule)).toStrictEqual(expected);
  });

  it('should convert slabs fee from rupee to paise', () => {
    const rule = {
      rule_type: 'slabs',
      flat: 0,
      slabs: [
        { gte: 0, lte: 100, fee: 10 },
        { gte: 101, lte: 200, fee: 20 },
      ],
    };
    const expected = {
      rule_type: 'slabs',
      flat: 0,
      slabs: [
        { gte: 0, lte: 10000, fee: 1000 },
        { gte: 10100, lte: 20000, fee: 2000 },
      ],
    };
    expect(transfeeRuleToApiFormat(rule)).toEqual(expected);
  });
});

describe('transfeeRuleToNormalFormat', () => {
  it('should convert flat fee from paise to rupee', () => {
    const rule = {
      rule_type: 'flat',
      flat: 1000,
      slabs: [],
    };
    const expected = {
      rule_type: 'flat',
      flat: 10,
      slabs: [],
    };
    expect(transfeeRuleToNormalFormat(rule)).toEqual(expected);
  });

  it('should convert slabs fee from paise to rupee', () => {
    const rule = {
      rule_type: 'slabs',
      flat: 0,
      slabs: [
        { gte: 0, lte: 10000, fee: 1000 },
        { gte: 10100, lte: 20000, fee: 2000 },
      ],
    };
    const expected = {
      rule_type: 'slabs',
      flat: 0,
      slabs: [
        { gte: 0, lte: 100, fee: 10 },
        { gte: 101, lte: 200, fee: 20 },
      ],
    };
    expect(transfeeRuleToNormalFormat(rule)).toEqual(expected);
  });
});
