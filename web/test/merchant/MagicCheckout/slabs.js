import {
  transformToApiFormat,
  transformToComponentFormat,
} from 'merchant/reducers/magicCheckout/magicSettings/utils';

require('it-each')();
const expect = require('chai').expect;

/**
 * Test Case Scenario
 *
 * For amount Rs 0-5000, Fee: Rs 50
 * For amount > 5000, Fee: Rs 0
 */

const SLABS_API_EXAMPLE = [
  { amount: 0, fee: 5000 },
  { amount: 500100, fee: 0 },
];

const SLABS_COMPONENT_EXAMPLE = {
  rule_type: 'slabs',
  flat: 0,
  slabs: [
    { gte: 0, lte: 5000, fee: 50 },
    { gte: 5001, lte: Infinity, fee: 0 },
  ],
};

describe('MagicCheckout - Slabs - Utils, transformToApiFormat', function testCase() {
  it('should transform to api format', function test() {
    const result = transformToApiFormat(SLABS_COMPONENT_EXAMPLE);
    expect(result).to.eql(SLABS_API_EXAMPLE);
  });
});

describe('MagicCheckout - Slabs - Utils, transformToComponentFormat', function testCase() {
  it('should transform to component format', function test() {
    const result = transformToComponentFormat(SLABS_API_EXAMPLE);
    expect(result).to.eql(SLABS_COMPONENT_EXAMPLE);
  });
});
