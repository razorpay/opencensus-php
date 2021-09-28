import React from 'react';
import MultiSelect from './MultiSelect';
import { useFormikContext } from 'formik';

const SupportingDetails = ({ disabled }) => {
  const formikProps = useFormikContext();
  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');
  return (
    <div class="supporting-details">
      <div class="main-title">SUPPORTING DETAILS AND BEST PRACTICES</div>
      <div class="sub-title">
        Unlike Domestic transactions, International transactions are not protected by 3D secure
        systems. Hence setting up internal checks by businesses to prevent frauds are highly
        recommended.
      </div>

      <MultiSelect
        required
        disabled={disabled}
        label="Risk Checks Currently in Place"
        name="existing_risk_checks"
        options={[
          'None',
          'We differentiate between domestic and international customers',
          'We have set up an upper threshold on transactions / cart value',
          'We maintain a blacklist for the suspicious /  confirmed fraud orders',
        ]}
        additionalFieldMaxLength={500}
        error={getError('existing_risk_checks')}
      />
    </div>
  );
};

export default SupportingDetails;
