require('it-each')();
const expect = require('chai').expect;

import { dotStringToObj } from 'common/util';
import { invalid } from 'moment';

describe('common/util: doStringToObj', () => {
  describe('dot-notation', () => {
    it('should insert value in passed object with passed path', () => {
      const sampleObj = {};
      const samplePath = 'path.to.key';
      const sampleValue = 'value';

      dotStringToObj(samplePath, sampleValue, sampleObj);
      const expected = { path: { to: { key: 'value' } } };
      expect(sampleObj).to.deep.equal(expected);
    });

    it('should insert value in array for numbers in path', () => {
      const sampleObj = {};
      const samplePath = 'path.0.key';
      const sampleValue = 'value';

      dotStringToObj(samplePath, sampleValue, sampleObj);
      const expected = { path: [{ key: 'value' }] };
      expect(sampleObj).to.deep.equal(expected);
    });

    const invalidArgumentSets = [
      [undefined, undefined, undefined],
      [undefined, undefined, {}],
      [undefined, 'value', undefined],
      ['some.path', undefined, undefined],
      ['some.path', 'value', undefined],
      [{}, 'value', {}],
      ['some.path', 'value', 'random'],
    ];

    it.each(invalidArgumentSets, 'Should fail', (argumentSet, next) => {
      const toBeFailedFn = () => {
        dotStringToObj(...argumentSet);
      };
      expect(toBeFailedFn).to.throw();
      next();
    });
  });

  describe('square-bracket notation', () => {
    it('should insert value in passed object with passed path', () => {
      const sampleObj = {};
      const samplePath = 'path[to][key]';
      const sampleValue = 'value';

      dotStringToObj(samplePath, sampleValue, sampleObj);
      const expected = { path: { to: { key: 'value' } } };
      expect(sampleObj).to.deep.equal(expected);
    });

    it('should insert value in array for numbers in path', () => {
      const sampleObj = {};
      const samplePath = 'path[0][key]';
      const sampleValue = 'value';

      dotStringToObj(samplePath, sampleValue, sampleObj);
      const expected = { path: [{ key: 'value' }] };
      expect(sampleObj).to.deep.equal(expected);
    });

    const invalidArgumentSets = [
      [undefined, undefined, undefined],
      [undefined, undefined, {}],
      [undefined, 'value', undefined],
      ['some[path]', undefined, undefined],
      ['some[path]', 'value', undefined],
      [{}, 'value', {}],
      ['some[path]', 'value', 'random'],
    ];

    it.each(invalidArgumentSets, 'Should Fail', (argumentSet, next) => {
      const toBeFailedFn = () => {
        dotStringToObj(...argumentSet);
      };
      expect(toBeFailedFn).to.throw();
      next();
    });
  });
});
