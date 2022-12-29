import { connect } from 'react-redux';

import { getMaskedEmail } from 'merchant/components/Mask/utils/masking';

const RAZORPAY_DUMMY_EMAIL = 'void@razorpay.com';

function MaskedEmail({ email = '', user }) {
  if (email === RAZORPAY_DUMMY_EMAIL) {
    return 'NA';
  }
  return user.isHidePIDetails ? getMaskedEmail(email) : email;
}

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
  };
};

export default connect(mapStateToProps)(MaskedEmail);
