import User from 'merchant/models/User';
import { pickProps } from 'rzp/utils/rzp-utils';

import {
  L1FormSuccess,
  L1FormError,
} from 'component/merchant/Activation/ActivationUtils';

const L1FormFields = [
  'business_category',
  'business_dba',
  'business_model',
  'business_name',
  'business_operation_address',
  'business_operation_city',
  'business_operation_pin',
  'business_operation_state',
  'business_registered_address',
  'business_registered_city',
  'business_registered_pin',
  'business_registered_state',
  'business_subcategory',
  'business_type',
  'business_website',
  'promoter_pan',
  'promoter_pan_name',
];

// `this` context is binded to index.js in this directory
export async function handlePANTryAgain() {
  const { session } = this.props;
  const data = pickProps(this.props.user, L1FormFields);
  try {
    const response = await this.props.submitL1Form({ data });

    this.props.submitL1FormSuccess({ data: response.data });
    const {
      activation_progress,
      activated,
      activation_status,
      activation_flow,
      submitted,
      international,
      poi_verification_status,
    } = response.data;

    // Updating % activation_progress (side bar) and other important activation fields
    const user = (this.user = new User({
      ...session.user,
      activation_progress,
      activated,
      activation_status,
      activation_flow,
      international,
      poi_verification_status,
      submitted: +submitted,
    }));

    this.props.updateSession({
      user,
      mode: session.mode,
    });

    const {
      showPANStatusModal,
      showKYCDetailsModal,
      showInstantActivationSuccessModal,
      tracking,
    } = this.props;

    const props = {
      activation_flow: this.user.activation_flow,
      instantActivation: this.user.instantActivation,
      business_type: this.user.business_type,
      poi_verification_status: this.user.poi_verification_status,
      showKYCDetailsModal,
      showPANStatusModal,
      showInstantActivationSuccessModal,
      tracking,
    };

    L1FormSuccess(props);
  } catch (err) {
    if (err.errors && err.errors.length && err.errors[0]) {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    }

    L1FormError();

    return err;
  }
}
