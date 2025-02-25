import path from 'path';

export default {
  process(_: string, sourcePath: string) {
    return {
      code: `module.exports = ${JSON.stringify(path.basename(sourcePath))};`,
    };
  },
};
