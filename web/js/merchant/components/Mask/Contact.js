import { connect } from 'react-redux';

import { getMaskedContact } from 'merchant/components/Mask/utils/masking';

function MaskedContact({ contact = '', user }) {
  return user.isHidePIDetails ? getMaskedContact(contact) : contact;
}

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
  };
};

export default connect(mapStateToProps)(MaskedContact);
