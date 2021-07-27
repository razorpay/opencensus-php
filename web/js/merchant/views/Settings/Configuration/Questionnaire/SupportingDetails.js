import React, { useState } from 'react';
import MultiSelect from './MultiSelect';
import { useFormikContext } from 'formik';

const SupportingDetails = ({ disabled }) => {
  const formikProps = useFormikContext();
  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');
  return (
    <div class="supporting-details">
      <div class="main-title">SUPPORTING DETAILS AND BEST PRACTISES</div>
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

      <MultiSelect
        required
        disabled={disabled}
        label="Customer Info Collected"
        name="customer_info_collected"
        additionalFieldMaxLength={300}
        options={['None', 'Name', 'Billing Address', 'Shipping Address', 'Phone Address']}
        error={getError('customer_info_collected')}
      />

      <MultiSelect
        required
        disabled={disabled}
        additionalFieldMaxLength={300}
        label="Partner Details-Plugins Used"
        name="partner_details_plugins"
        options={['Shopify', 'WooCommerce', 'Shiprocket', 'Zoho Books', 'WHMCS', 'Magento', 'Wix']}
        error={getError('partner_details_plugins')}
      />
    </div>
  );
};

export default SupportingDetails;
