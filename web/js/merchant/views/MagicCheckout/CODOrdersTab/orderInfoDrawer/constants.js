export const MAGIC_INTELLIGENCE_RECOMMENDATION = {
  address: {
    high:
      'There looks to be a problem with the address. We recommend contacting the customer to verify the address.',
    medium:
      'There looks to be a problem with the address. We recommend contacting the customer to verify the address.',
  },
  behavior: {
    high: 'Suspicious customer behaviour detected. We recommend cancelling the order.',
    medium: 'Suspicious customer behaviour detected. We recommend cancelling the order.',
  },
  cod: {
    high:
      'We have noticed that the customer is prone to placing more COD orders than prepaid orders.',
    medium:
      'We have noticed that the customer is prone to placing more COD orders than prepaid orders.',
  },
  delivery: {
    high:
      'We have noticed low delivery rate associated with this order. We recommend cancelling the order.',
    medium:
      'We have noticed low delivery rate associated with this order. We recommend cancelling the order.',
  },
  email: {
    high:
      'Email address seems incorrect. We recommend contacting the customer to verify the email.',
    medium:
      'Email address seems incorrect. We recommend contacting the customer to verify the email.',
  },
  return: {
    high:
      'We have noticed high return behaviour associated with this order. We recommend cancelling the order.',
    medium:
      'We have noticed high return behaviour associated with this order. We recommend cancelling the order.',
  },
  rto: {
    high:
      'We have noticed high RTO behaviour associated with this order. We recommend cancelling the order.',
    medium:
      'We have noticed high RTO behaviour associated with this order. We recommend cancelling the order.',
  },
  phone: {
    high:
      'Phone number seems incorrect. We recommend emailing the customer to verify the phone number.',
    medium:
      'Phone number seems incorrect. We recommend emailing the customer to verify the phone number.',
  },
  blocklist: {
    high: 'You have blocklisted this customer for COD orders. We recommend cancelling the order.',
    medium: 'You have blocklisted this customer for COD orders. We recommend cancelling the order.',
  },
};
