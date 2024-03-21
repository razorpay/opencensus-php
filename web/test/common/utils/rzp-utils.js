import {
  isFunction,
  stringToObj,
  getFormattedAmountByParts,
  getFormattedAmountNew,
  formatAmount,
} from 'common/utils/rzp-utils';

require('it-each')();
const expect = require('chai').expect;

describe('common/utils/rzp-utils Fn: isFunction', () => {
  const falseValues = [2, 2.3, true, false, 0, '0', [], {}, '', null, undefined];

  it('should be function', () => {
    const value = () => {};
    const result = isFunction(value);

    expect(result).to.eql(true);
  });

  it.each(falseValues, 'all values should not be function.', (value, next) => {
    const result = isFunction(value);
    expect(result).to.eql(false);

    next();
  });
});

describe('common/utils/rzp-utils: getFormattedAmountByParts', () => {
  it('getFormattedAmountByParts should return subparts of formatted amount in different currencies', () => {
    const formattedAmountINR = getFormattedAmountByParts(125672.8767, 'INR');
    const formattedAmountUSD = getFormattedAmountByParts(125672.8767, 'USD');
    const formattedAmountKWD = getFormattedAmountByParts(125672.8767, 'KWD');
    const formattedAmountBHD = getFormattedAmountByParts(125672.8767, 'BHD');
    const formattedAmountOMR = getFormattedAmountByParts(125672.8767, 'OMR');
    const formattedAmountJPY = getFormattedAmountByParts(125672.8767, 'JPY');

    const expectedAmountINR = {
      currency: '₹',
      fraction: '73',
      integer: '1,256',
      decimal: '.',
      isPrefixSymbol: true,
      rawParts: [
        {
          type: 'currency',
          value: '₹',
        },
        {
          type: 'integer',
          value: '1',
        },
        {
          type: 'group',
          value: ',',
        },
        {
          type: 'integer',
          value: '256',
        },
        {
          type: 'decimal',
          value: '.',
        },
        {
          type: 'fraction',
          value: '73',
        },
      ],
    };

    const expectedAmountUSD = {
      currency: '$',
      fraction: '73',
      integer: '1,256',
      decimal: '.',
      isPrefixSymbol: true,
      rawParts: [
        {
          type: 'currency',
          value: '$',
        },
        {
          type: 'integer',
          value: '1',
        },
        {
          type: 'group',
          value: ',',
        },
        {
          type: 'integer',
          value: '256',
        },
        {
          type: 'decimal',
          value: '.',
        },
        {
          type: 'fraction',
          value: '73',
        },
      ],
    };

    const expectedAmountKWD = {
      currency: 'KWD',
      integer: '125',
      decimal: '.',
      fraction: '673',
      isPrefixSymbol: true,
      rawParts: [
        { type: 'currency', value: 'KWD' },
        { type: 'literal', value: String.fromCharCode(160) },
        { type: 'integer', value: '125' },
        { type: 'decimal', value: '.' },
        { type: 'fraction', value: '673' },
      ],
    };

    const expectedAmountBHD = {
      currency: 'BHD',
      integer: '125',
      decimal: '.',
      fraction: '673',
      isPrefixSymbol: true,
      rawParts: [
        { type: 'currency', value: 'BHD' },
        { type: 'literal', value: String.fromCharCode(160) },
        { type: 'integer', value: '125' },
        { type: 'decimal', value: '.' },
        { type: 'fraction', value: '673' },
      ],
    };

    const expectedAmountOMR = {
      currency: 'OMR',
      integer: '125',
      decimal: '.',
      fraction: '673',
      isPrefixSymbol: true,
      rawParts: [
        { type: 'currency', value: 'OMR' },
        { type: 'literal', value: String.fromCharCode(160) },
        { type: 'integer', value: '125' },
        { type: 'decimal', value: '.' },
        { type: 'fraction', value: '673' },
      ],
    };

    const expectedAmountJPY = {
      currency: 'JP¥',
      integer: '1,25,673',
      isPrefixSymbol: true,
      rawParts: [
        { type: 'currency', value: 'JP¥' },
        { type: 'integer', value: '1' },
        { type: 'group', value: ',' },
        { type: 'integer', value: '25' },
        { type: 'group', value: ',' },
        { type: 'integer', value: '673' },
      ],
    };

    expect(formattedAmountINR).to.deep.eql(expectedAmountINR);
    expect(formattedAmountUSD).to.deep.eql(expectedAmountUSD);
    expect(formattedAmountKWD).to.deep.eql(expectedAmountKWD);
    expect(formattedAmountBHD).to.deep.eql(expectedAmountBHD);
    expect(formattedAmountOMR).to.deep.eql(expectedAmountOMR);
    expect(formattedAmountJPY).to.deep.eql(expectedAmountJPY);
  });
});

describe('common/utils/rzp-utils: getFormattedAmountNew', () => {
  it('should correctly format a number without currency symbol', () => {
    const result = getFormattedAmountNew(123456, false);
    expect(result).to.equal('1,234.56');
  });

  it('should correctly format a number with default currency (INR)', () => {
    const result = getFormattedAmountNew(123456, true);
    expect(result).to.include('₹'); // Check for presence of Rupee symbol
    expect(result).to.match(/₹\s?1,234.56/); // Check for correct formatting with currency
  });

  it('should correctly format a number with specified currency (USD)', () => {
    const result = getFormattedAmountNew(123456, true, 'USD');
    // Check for correct formatting with specified currency
    expect(result).to.include('$'); // Check for presence of Dollar symbol
    expect(result).to.match(/\$\s?1,234.56/); // Assuming 'en-IN' formatting rules apply
  });

  it('should correctly format a number with specified currency (OMR)', () => {
    const result = getFormattedAmountNew(123456, true, 'OMR');
    expect(result).to.equal(`OMR${String.fromCharCode(160)}123.456`);
  });

  it('should handle zero amount correctly', () => {
    const result = getFormattedAmountNew(0, true, 'INR');
    // Check for correct formatting of zero amount
    expect(result).to.equal('₹0.00');
  });

  it('should handle negative amounts correctly', () => {
    const result = getFormattedAmountNew(-123456, true, 'INR');
    // Check for correct formatting of negative amounts
    expect(result).to.match(/-₹\s?1,234.56/);
  });

  it('should return the formatted amount with two decimal places', () => {
    const result = getFormattedAmountNew(123400, true, 'INR');
    // Ensure the function always formats the amount with two decimal places
    expect(result).to.equal('₹1,234.00');
  });
});

describe('common/utils/rzp-utils: formatAmount', () => {
  it('should correctly format a number without currency', () => {
    const result = formatAmount(1234.56, false);
    expect(result).to.equal('1,234.56');
  });

  it('should correctly format a number with currency', () => {
    const result = formatAmount(1234.56, true, 'USD');
    expect(result).to.equal('$1,234.56'); // for locale: en-MY
  });

  it('should correctly round numbers based on currency rules', () => {
    const result = formatAmount(1234.567, true, 'USD');
    expect(result).to.equal('$1,234.57');
  });
});

describe('common/utils/rzp-utils: doStringToObj', () => {
  describe('dot-notation', () => {
    it('should insert value in passed object with passed path', () => {
      const argumentSet = ['path.to.key', 'value', {}];
      const expected = { path: { to: { key: 'value' } } };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    it('should insert value in array for numbers in path', () => {
      const argumentSet = ['path.0.key', 'value', {}];
      const expected = { path: [{ key: 'value' }] };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input', () => {
      const argumentSet = ['path.to.key', undefined, {}];
      const expected = { path: { to: { key: undefined } } };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input:with array', () => {
      const argumentSet = ['path.0.key', undefined, {}];
      const expected = { path: [{ key: undefined }] };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    const invalidArgumentSets = [
      [undefined, undefined, undefined],
      [undefined, undefined, {}],
      [undefined, 'value', undefined],
      ['some.path', undefined, undefined],
      ['some.path', 'value', undefined],
      [{}, 'value', {}],
      ['some.path', 'value', 'random'],
      [undefined, {}, undefined],
    ];

    it.each(invalidArgumentSets, 'Should fail', (argumentSet, next) => {
      const toBeFailedFn = () => {
        stringToObj(...argumentSet);
      };
      expect(toBeFailedFn).to.throw();

      next();
    });
  });

  describe('square-bracket notation', () => {
    it('should insert value in passed object with passed path', () => {
      const argumentSet = ['path[to][key]', 'value', {}];
      const expected = { path: { to: { key: 'value' } } };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    it('should insert value in array for numbers in path', () => {
      const argumentSet = ['path[0][key]', 'value', {}];
      const expected = { path: [{ key: 'value' }] };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input', () => {
      const argumentSet = ['path[to][key]', undefined, {}];
      const expected = { path: { to: { key: undefined } } };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input:with array', () => {
      const argumentSet = ['path[0][key]', undefined, {}];
      const expected = { path: [{ key: undefined }] };

      const actual = stringToObj(...argumentSet);
      expect(actual).to.deep.equal(expected);
    });

    const invalidArgumentSets = [
      [undefined, undefined, undefined],
      [undefined, undefined, {}],
      [undefined, 'value', undefined],
      ['some[path]', undefined, undefined],
      ['some[path]', 'value', undefined],
      [{}, 'value', {}],
      ['some[path]', 'value', 'random'],
      [undefined, {}, undefined],
    ];

    it.each(invalidArgumentSets, 'Should Fail', (argumentSet, next) => {
      const toBeFailedFn = () => {
        stringToObj(...argumentSet);
      };
      expect(toBeFailedFn).to.throw();

      next();
    });
  });
});
