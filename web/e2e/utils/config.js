function getBaseUrl() {
  const baseUrl = process.env.E2E_BASE_URL || 'https://dashboard.dev.razorpay.in';
  return baseUrl;
}

module.exports = {
  getBaseUrl,
};
