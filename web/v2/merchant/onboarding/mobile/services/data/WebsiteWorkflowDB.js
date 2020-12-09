const initialData = { data: false };

let CurrentWebsiteWorkflow = { ...initialData };

function read() {
  return CurrentWebsiteWorkflow;
}

function update(data) {
  CurrentWebsiteWorkflow = { ...CurrentWebsiteWorkflow, ...data };
  return CurrentWebsiteWorkflow;
}

function reset() {
  CurrentWebsiteWorkflow = { ...initialData };
}

export { read, update, reset };
