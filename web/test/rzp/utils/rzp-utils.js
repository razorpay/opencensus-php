require('it-each')();
const expect = require('chai').expect;
import { isFunction } from 'rzp/utils/rzp-utils';

describe('rzp/utils/rzp-utils Fn: isFunction', function() {
  const falseValues = [
    2,
    2.3,
    true,
    false,
    0,
    '0',
    [],
    {},
    '',
    null,
    undefined,
  ];

  it('should be function', function() {
    const value = function() {};
    const result = isFunction(value);

    expect(result).to.eql(true);
  });

  it.each(falseValues, 'all values should not be function.', function(
    value,
    next
  ) {
    const result = isFunction(value);
    expect(result).to.eql(false);

    next();
  });
});
