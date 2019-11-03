import User from 'merchant/models/User';

import { pickProps } from 'rzp/utils/rzp-utils';

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
export function handlePANTryAgain() {
  const { session } = this.props;
  const data = pickProps(this.props.user, L1FormFields);
  this.props
    .submitL1Form({ data })
    .then(response => {
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

      if (poi_verification_status == 'verified') {
        this.props.showPANStatusModal();
      } else if (poi_verification_status == 'incorrect_details') {
        const error = {
          errors: ['Incorrect PAN Details Provided'],
        };
        throw error;
      } else if (poi_verification_status == 'not_matched') {
        const error = {
          errors: ['Provided details does not match any records.'],
        };
        throw error;
      }
    })
    .catch(err => {
      if (err.errors.length && err.errors[0]) {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      }
      return err;
    });
}
