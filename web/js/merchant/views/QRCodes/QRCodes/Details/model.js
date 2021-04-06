import QRCode from 'merchant/models/QRCode';

export const fetchQRCodeDetails = (id) => {
  const qrCode = new QRCode();
  return qrCode.fetch(id);
};

export const fetchPayments = (id) => {
  const qrCode = new QRCode({ id });
  return qrCode.fetchPayments();
};
