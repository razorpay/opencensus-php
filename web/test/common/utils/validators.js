require('it-each')();
const expect = require('chai').expect;
import { isInteger } from 'common/utils/validators';

describe('common/utils/validators Fn: isInteger', function () {
  const trueValues = [0, 5, '0', '5'];
  const falseValues = [
    5.3,
    '5.3',
    -5,
    '-5',
    -5.3,
    '-5.3',
    true,
    false,
    [],
    {},
    '',
    null,
    undefined,
  ];

  it.each(trueValues, 'should be integer', function (value, next) {
    const result = isInteger(value);
    expect(result).to.eql(true);

    next();
  });

  it.each(falseValues, 'all values should not be integer.', function (value, next) {
    const result = isInteger(value);
    expect(result).to.eql(false);

    next();
  });
});
