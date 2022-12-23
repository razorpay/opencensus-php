const fs = require('fs');

const STORAGE_STATE_FILE_PREFIX = 'state';
const STORAGE_STATE_PATH = './e2e/storageState';
const EXPIRES_IN_MINUTES = 60;

// returns file path for storage state to maintain user's login state (eg: cookies)
const getStorageStatePath = () => {
  const lastFile = fs
    .readdirSync(STORAGE_STATE_PATH)
    .filter((name) => name.startsWith(STORAGE_STATE_FILE_PREFIX))
    .pop();
  const currentTime = Date.now();
  if (lastFile) {
    const [, lastTimestamp] = lastFile.split('.');
    // eslint-disable-next-line radix
    const dateDiffInMinutes = Math.floor((currentTime - parseInt(lastTimestamp)) / 1000 / 60);
    const decTime = dateDiffInMinutes > EXPIRES_IN_MINUTES ? currentTime : lastTimestamp;
    return `${STORAGE_STATE_PATH}/state.${decTime}.json`;
  }
  return `${STORAGE_STATE_PATH}/state.${currentTime}.json`;
};

module.exports = {
  getStorageStatePath,
};
