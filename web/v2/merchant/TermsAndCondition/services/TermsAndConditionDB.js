const initialData = {
  link: 'tnc.razorpay.com/Gmfaslj9AUIOH',
  merchant_id: '4VUhFiV6029ARY',
  deliverable_type: 'services',
  support_email: 'abc@gmail.com',
  shipping_period: '1-2 days',
  refund_request_period: '3-5 days',
  refund_process_period: '9-15 days',
  warranty_period: 'NA',
};

let CurrentTncData = { ...initialData };

function read() {
  return CurrentTncData;
}

function update(data) {
  CurrentTncData = { ...CurrentTncData, ...data };
  return CurrentTncData;
}

function reset() {
  CurrentTncData = { ...initialData };
}

export { read, update, reset, initialData };
