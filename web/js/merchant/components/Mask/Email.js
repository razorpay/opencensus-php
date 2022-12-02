import { connect } from 'react-redux';

import { getMaskedEmail } from 'merchant/components/Mask/utils/masking';

function MaskedEmail({ email = '', user }) {
  return user.isHidePIDetails ? getMaskedEmail(email) : email;
}

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
  };
};

export default connect(mapStateToProps)(MaskedEmail);
