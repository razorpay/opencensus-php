import ActivationData from './activation-data.json';

let CurrentActivationData = { ...ActivationData };

function read() {
  return CurrentActivationData;
}

function update(data) {
  CurrentActivationData = { ...CurrentActivationData, ...data };
  return CurrentActivationData;
}

function reset() {
  CurrentActivationData = { ...ActivationData };
}

export { read, update, reset };
