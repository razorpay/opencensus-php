import React from 'react';
import { connect } from 'react-redux';

import CreateEditV1 from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg';
import CreateEditV2 from '.';

interface IProps {
  user: {
    isPaymentPageStorefrontEnabled: boolean;
  };
}

// if razorx is off, show old PP flow. Else show new flow for PP/storefront
const CreateEditPaymentPageWrapper = (props: IProps) => {
  if (props.user.isPaymentPageStorefrontEnabled) {
    return <CreateEditV2 {...props} />;
  }
  return <CreateEditV1 {...props} />;
};
export default connect((state) => ({ user: state.session.user }))(CreateEditPaymentPageWrapper);
