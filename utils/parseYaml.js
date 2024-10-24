/**
 * Parses a YAML string into a JavaScript object.
 *
 * @param {string} yamlStr - The YAML string to parse.
 * @returns {Object} The parsed JavaScript object.
 */
function parseYaml(yamlStr) {
  const lines = yamlStr.trim().split('\n');
  const data = {};
  let currentKey = null;

  lines.forEach((line) => {
    line = line.trim();
    if (line.startsWith('-')) {
      // Handle list items
      if (Array.isArray(data[currentKey])) {
        data[currentKey].push(line.slice(1).trim());
      } else {
        data[currentKey] = [line.slice(1).trim()];
      }
    } else if (line.includes(':')) {
      // Handle key-value pairs
      const [key, value] = line.split(':').map((s) => s.trim());
      if (value) {
        data[key] = value;
      } else {
        currentKey = key;
        data[key] = {};
      }
    }
  });

  return data;
}

module.exports = {
  parseYaml,
};
