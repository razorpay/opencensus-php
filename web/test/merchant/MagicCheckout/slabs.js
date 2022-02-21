import {
  transformToApiFormat,
  transformToComponentFormat,
} from 'merchant/reducers/magicCheckout/magicSettings/utils';

require('it-each')();
const expect = require('chai').expect;

const FREE_CHARGE_API_EXAMPLE = [{ amount: 0, fee: 0 }];
const FLAT_CHARGE_API_EXAMPLE = [{ amount: 0, fee: 2000 }];
const SLABS_CHARGE_API_EXAMPLE_ORDERED = [
  { amount: 0, fee: 5000 },
  { amount: 500100, fee: 2500 },
  { amount: 1000100, fee: 0 },
];
const SLABS_CHARGE_API_EXAMPLE_UNORDERED = [
  { amount: 1000100, fee: 0 },
  { amount: 0, fee: 5000 },
  { amount: 500100, fee: 2500 },
];
const SLABS_CHARGE_API_WITH_INFINITY_EXAMPLE = [
  { amount: 0, fee: 5000 },
  { amount: 500100, fee: 2500 },
  { amount: 1000100, fee: 1000 },
];

const FREE_CHARGE_COMPONENT_EXAMPLE = {
  rule_type: 'free',
  flat: 0,
  slabs: [{ gte: 0, lte: 0, fee: 0 }],
};
const FLAT_CHARGE_COMPONENT_EXAMPLE = {
  rule_type: 'flat',
  flat: 20,
  slabs: [{ gte: 0, lte: 0, fee: 0 }],
};
const SLABS_CHARGE_COMPONENT_EXAMPLE = {
  rule_type: 'slabs',
  flat: 0,
  slabs: [
    { gte: 0, lte: 5000, fee: 50 },
    { gte: 5001, lte: 10000, fee: 25 },
    { gte: 10001, lte: Infinity, fee: 0 },
  ],
};
const SLABS_CHARGE_WITH_INFINITY_COMPONENT_EXAMPLE = {
  rule_type: 'slabs',
  flat: 0,
  slabs: [
    { gte: 0, lte: 5000, fee: 50 },
    { gte: 5001, lte: 10000, fee: 25 },
    { gte: 10001, lte: Infinity, fee: 10 },
  ],
};
const SLABS_CHARGE_WITHOUT_INFINITY_COMPONENT_EXAMPLE = {
  rule_type: 'slabs',
  flat: 0,
  slabs: [
    { gte: 0, lte: 5000, fee: 50 },
    { gte: 5001, lte: 10000, fee: 25 },
  ],
};

describe('MagicCheckout - Slabs - Utils, transformToApiFormat', function testCase() {
  it('free rule should transform to api format', function test() {
    const freeResult = transformToApiFormat(FREE_CHARGE_COMPONENT_EXAMPLE);

    expect(freeResult).to.eql(FREE_CHARGE_API_EXAMPLE);
  });

  it('flat rule should transform to api format', function test() {
    const flatResult = transformToApiFormat(FLAT_CHARGE_COMPONENT_EXAMPLE);

    expect(flatResult).to.eql(FLAT_CHARGE_API_EXAMPLE);
  });

  it('slab rule should transform to api format', function test() {
    const slabsResult = transformToApiFormat(SLABS_CHARGE_COMPONENT_EXAMPLE);
    const slabsResultWithoutInfinity = transformToApiFormat(
      SLABS_CHARGE_WITHOUT_INFINITY_COMPONENT_EXAMPLE,
    );
    const slabsResultWithInfinity = transformToApiFormat(
      SLABS_CHARGE_WITH_INFINITY_COMPONENT_EXAMPLE,
    );

    expect(slabsResult).to.eql(SLABS_CHARGE_API_EXAMPLE_ORDERED);
    expect(slabsResultWithoutInfinity).to.eql(SLABS_CHARGE_API_EXAMPLE_ORDERED);
    expect(slabsResultWithInfinity).to.eql(SLABS_CHARGE_API_WITH_INFINITY_EXAMPLE);
  });
});

describe('MagicCheckout - Slabs - Utils, transformToComponentFormat', function testCase() {
  it('free rule should transform to component format', function test() {
    const freeResult = transformToComponentFormat(FREE_CHARGE_API_EXAMPLE);

    expect(freeResult).to.eql(FREE_CHARGE_COMPONENT_EXAMPLE);
  });

  it('flat rule should transform to component format', function test() {
    const flatResult = transformToComponentFormat(FLAT_CHARGE_API_EXAMPLE);

    expect(flatResult).to.eql(FLAT_CHARGE_COMPONENT_EXAMPLE);
  });

  it('slab rule should transform to component format', function test() {
    const orderedSlabsResult = transformToComponentFormat(SLABS_CHARGE_API_EXAMPLE_ORDERED);
    const unorderedSlabsResult = transformToComponentFormat(SLABS_CHARGE_API_EXAMPLE_UNORDERED);
    const slabsWithInifityResult = transformToComponentFormat(
      SLABS_CHARGE_API_WITH_INFINITY_EXAMPLE,
    );

    expect(orderedSlabsResult).to.eql(SLABS_CHARGE_COMPONENT_EXAMPLE);
    expect(unorderedSlabsResult).to.eql(SLABS_CHARGE_COMPONENT_EXAMPLE);
    expect(slabsWithInifityResult).to.eql(SLABS_CHARGE_WITH_INFINITY_COMPONENT_EXAMPLE);
  });
});
