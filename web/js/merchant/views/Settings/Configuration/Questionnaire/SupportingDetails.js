import React from 'react';
import MultiSelect from './MultiSelect';
import { useFormikContext } from 'formik';
import { riskChecksOptionsV2, riskChecksOptions } from './utils';

const SupportingDetails = ({ disabled, isRevampFlow }) => {
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
        options={isRevampFlow ? riskChecksOptionsV2 : riskChecksOptions}
        additionalFieldMaxLength={500}
        error={getError('existing_risk_checks')}
        showSpecifyOthersOption={!isRevampFlow}
      />
    </div>
  );
};

export default SupportingDetails;
