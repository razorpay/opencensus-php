import { parseHtmlString } from 'apps/pos/src/app/utils/paymentsAndServices';

describe('parseHtmlString', () => {
  test('should return empty values when input is undefined', () => {
    expect(parseHtmlString(undefined)).toEqual({ from: '', comment: '' });
  });

  test('should return empty values when input is an empty string', () => {
    expect(parseHtmlString('')).toEqual({ from: '', comment: '' });
  });

  test('should extract from and comment correctly', () => {
    const input = 'John Doe: <div>Hello World</div>';
    expect(parseHtmlString(input)).toEqual({ from: 'John Doe', comment: 'Hello World' });
  });

  test('should handle missing colon and return full string as comment', () => {
    const input = '<div>Hello World</div>';
    expect(parseHtmlString(input)).toEqual({ from: '', comment: 'Hello World' });
  });

  test('should handle HTML entities', () => {
    const input = 'User: <div>&lt;b&gt;Bold&lt;/b&gt; text</div>';
    expect(parseHtmlString(input)).toEqual({ from: 'User', comment: '<b>Bold</b> text' });
  });

  test('should trim spaces around from and comment', () => {
    const input = '   Alice   :   <p>  Trimmed Text  </p>   ';
    expect(parseHtmlString(input)).toEqual({ from: 'Alice', comment: 'Trimmed Text' });
  });

  test('should handle deeply nested HTML elements', () => {
    const input = 'Bob: <div><p><span>Nested Content</span></p></div>';
    expect(parseHtmlString(input)).toEqual({ from: 'Bob', comment: 'Nested Content' });
  });

  test('should return empty comment if HTML contains no text', () => {
    const input = 'Bot: <div><p></p></div>';
    expect(parseHtmlString(input)).toEqual({ from: 'Bot', comment: '' });
  });

  test('should handle multiple colons in the input', () => {
    const input = 'John: Doe: <p>Nested colons</p>';
    expect(parseHtmlString(input)).toEqual({ from: 'John', comment: 'Doe: Nested colons' });
  });
});
