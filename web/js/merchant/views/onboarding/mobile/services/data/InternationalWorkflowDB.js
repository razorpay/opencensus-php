const initialData = {
  payment_gateway: 'no_action_received',
  payment_links: 'no_action_received',
  payment_pages: 'no_action_received',
  invoices: 'no_action_received',
};

let CurrentInternationalWorkflowData = { ...initialData };

function read() {
  return CurrentInternationalWorkflowData;
}

function update(data) {
  CurrentInternationalWorkflowData = { ...CurrentInternationalWorkflowData, ...data };
  return CurrentInternationalWorkflowData;
}

function reset() {
  CurrentInternationalWorkflowData = { ...initialData };
}

export { read, update, reset };
