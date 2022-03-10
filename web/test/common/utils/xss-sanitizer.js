import sanitizer from 'common/utils/xss-sanitizer';
require('it-each')();
const expect = require('chai').expect;

describe('common/utils/xss-sanitizer', function testCase() {
  it('sanitizer("<b>abcd</b>") should be equal to "<b>abcd</b>");', function test() {
    expect(sanitizer('<b>abcd</b>')).to.eql('<b>abcd</b>');
  });

  it('should not contain style attribute', function test() {
    expect(sanitizer('<b style=`color: green`>abcd</b>')).to.eql('<b>abcd</b>');
  });

  it('should not contain script tag', function test() {
    expect(sanitizer("<b>abcd</b><script>console.log('You are hacked')</script>")).to.eql(
      '<b>abcd</b>',
    );
  });

  it('should not contain any click handler', function test() {
    expect(sanitizer("<div onclick='console.log('You are hacked')'>Click Here</div>")).to.eql(
      '<div>Click Here</div>',
    );
  });

  it('should not contain alerts', function test() {
    expect(sanitizer('<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>')).to.eql('<img src>');
  });

  it('should not contain button tag', function test() {
    expect(sanitizer('<button>Click Here</button>')).to.eql('');
  });

  it('should not contain comments', function test() {
    expect(sanitizer('<!-- hello -->')).to.eql('');
  });
  it('image tag can have width and height attributes', function test() {
    expect(sanitizer(`<img width = 100    height     =200 title="xxx">`)).to.eql(
      `<img width="100" height="200" title="xxx">`,
    );
  });
});
