import PaymentsData from './payments-data.json';

let CurrentPaymentsData = { ...PaymentsData };

function read() {
  return CurrentPaymentsData;
}

function update(data) {
  CurrentPaymentsData = { ...CurrentPaymentsData, ...data };
  return CurrentPaymentsData;
}

function reset() {
  CurrentPaymentsData = { ...PaymentsData };
}

export { read, update, reset };
