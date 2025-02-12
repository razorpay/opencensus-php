import React from 'react';
import MultiSelect from './MultiSelect';
import { useFormikContext } from 'formik';
import { riskChecksOptionsV2, riskChecksOptions } from './utils';
import Input from 'common/new-ui/Input';

const SupportingDetails = ({ disabled, isRevampFlow, saveFormData }) => {
  const formikProps = useFormikContext();
  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');

  const handleRiskChecksChange = (evt) => {
    const isRiskCheckOtherThanNone = evt.target.value === 'true';
    formikProps.setFieldValue('risk_checks', isRiskCheckOtherThanNone);
    let existingRiskChecks = [];
    if (!isRiskCheckOtherThanNone) {
      existingRiskChecks = riskChecksOptionsV2.slice(2);
    }
    formikProps.setFieldValue('existing_risk_checks', existingRiskChecks);
    formikProps.values.existing_risk_checks = existingRiskChecks;
    saveFormData(formikProps, true);
  };

  const isRiskCheckOtherThanNone = !formikProps.values.existing_risk_checks?.includes('None');

  return (
    <div className="supporting-details">
      <div className="main-title">SUPPORTING DETAILS AND BEST PRACTICES</div>
      <div className="sub-title">
        Unlike Domestic transactions, International transactions are not protected by 3D secure
        systems. Hence setting up internal checks by businesses to prevent frauds are highly
        recommended.
      </div>

      {isRevampFlow ? (
        <div>
          <Input.Radio
            required
            name="risk_checks"
            label="Do you have any risk checks in place?"
            onChange={handleRiskChecksChange}
            options={[
              {
                label: 'Yes',
                value: true,
              },
              {
                label: 'No',
                value: false,
              },
            ]}
            defaultValue={isRiskCheckOtherThanNone}
            disabled={disabled}
            className="Input--vTop"
            value={isRiskCheckOtherThanNone}
            autoRender
          />
          {isRiskCheckOtherThanNone && (
            <MultiSelect
              required
              disabled={disabled}
              label="Risk checks currently in place"
              name="existing_risk_checks"
              options={riskChecksOptionsV2.slice(0, 2)}
              error={getError('existing_risk_checks')}
              showSpecifyOthersOption={false}
            />
          )}
        </div>
      ) : (
        <MultiSelect
          required
          disabled={disabled}
          label="Risk Checks Currently in Place"
          name="existing_risk_checks"
          options={riskChecksOptions}
          additionalFieldMaxLength={500}
          error={getError('existing_risk_checks')}
        />
      )}
    </div>
  );
};

export default SupportingDetails;
