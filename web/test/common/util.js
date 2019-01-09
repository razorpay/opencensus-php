require('it-each')();
const expect = require('chai').expect;

import { dotStringToObj } from 'common/util';

describe('common/util: doStringToObj', () => {
  describe('dot-notation', () => {
    it('should insert value in passed object with passed path', () => {
      const argumentSet = ['path.to.key', 'value', {}];
      const expected = { path: { to: { key: 'value' } } };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
    });

    it('should insert value in array for numbers in path', () => {
      const argumentSet = ['path.0.key', 'value', {}];
      const expected = { path: [{ key: 'value' }] };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input', () => {
      const argumentSet = ['path.to.key', undefined, {}];
      const expected = { path: { to: { key: undefined } } };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input:with array', () => {
      const argumentSet = ['path.0.key', undefined, {}];
      const expected = { path: [{ key: undefined }] };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
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
        dotStringToObj(...argumentSet);
      };
      expect(toBeFailedFn).to.throw();

      next();
    });
  });

  describe('square-bracket notation', () => {
    it('should insert value in passed object with passed path', () => {
      const argumentSet = ['path[to][key]', 'value', {}];
      const expected = { path: { to: { key: 'value' } } };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
    });

    it('should insert value in array for numbers in path', () => {
      const argumentSet = ['path[0][key]', 'value', {}];
      const expected = { path: [{ key: 'value' }] };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input', () => {
      const argumentSet = ['path[to][key]', undefined, {}];
      const expected = { path: { to: { key: undefined } } };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
    });

    it('should insert undefined as the value when passed as input:with array', () => {
      const argumentSet = ['path[0][key]', undefined, {}];
      const expected = { path: [{ key: undefined }] };

      dotStringToObj(...argumentSet);
      expect(argumentSet[2]).to.deep.equal(expected);
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
        dotStringToObj(...argumentSet);
      };
      expect(toBeFailedFn).to.throw();

      next();
    });
  });
});
