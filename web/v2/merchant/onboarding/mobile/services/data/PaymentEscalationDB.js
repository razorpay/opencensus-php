const initialData = {
  id: 'IU6Y4xr4Ms',
  amount: 1200000,
  threshold: 1500000,
  limit: { payment: 1500000, settlement: 100000000 },
  type: 'payment_breach',
  milestone: 'L1',
  action: {
    description: 'put merchant to FOH and send communication',
    status: 'success/failed',
  },
  next: {
    threshold: '10000000',
    milestone: 'L2',
  },
};

let CurrentEscalationData = { ...initialData };

function read() {
  return CurrentEscalationData;
}

function update(data) {
  CurrentEscalationData = { ...CurrentEscalationData, ...data };
  return CurrentEscalationData;
}

function reset() {
  CurrentEscalationData = { ...initialData };
}

export { read, update, reset };
