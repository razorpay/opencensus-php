const assert = require('node:assert');
const { describe, test } = require('node:test');

const { parseYaml } = require('./parseYaml');

describe('parseYaml', () => {
  test('parses simple key-value pairs', () => {
    const yaml = `
      name: John Doe
      age: 30
    `;
    assert.deepEqual(parseYaml(yaml), {
      name: 'John Doe',
      age: '30',
    });
  });

  test('parses lists', () => {
    const yaml = `
        fruits:
          - apple
          - banana
          - cherry
      `;

    assert.deepEqual(parseYaml(yaml), {
      fruits: ['apple', 'banana', 'cherry'],
    });
  });

  test('parses mixed key-value pairs and lists', () => {
    const yaml = `
        name: John Doe
        age: 30
        hobbies:
          - reading
          - swimming
          - coding
      `;

    assert.deepEqual(parseYaml(yaml), {
      name: 'John Doe',
      age: '30',
      hobbies: ['reading', 'swimming', 'coding'],
    });
  });

  test('parses key-value pairs and lists with empty lines', () => {
    const yaml = `
        name: John Doe

        age: 30
        hobbies:
        
          - reading

          - swimming
          - coding

      `;

    assert.deepEqual(parseYaml(yaml), {
      name: 'John Doe',
      age: '30',
      hobbies: ['reading', 'swimming', 'coding'],
    });
  });

  test('handles empty values', () => {
    const yaml = `
        name: John Doe
        email:
        phone:
      `;

    assert.deepEqual(parseYaml(yaml), {
      name: 'John Doe',
      email: {},
      phone: {},
    });
  });

  test('handles empty input', () => {
    assert.deepEqual(parseYaml(''), {});
  });

  test('ignores comments', () => {
    const yaml = `
        # This is a comment
        name: John Doe
        # Another comment
        age: 30
      `;

    assert.deepEqual(parseYaml(yaml), {
      name: 'John Doe',
      age: '30',
    });
  });
});
