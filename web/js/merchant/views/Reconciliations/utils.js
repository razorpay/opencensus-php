const checkReconSaasEnabled = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { recon_saas_flag: undefined } };
  return abExperiments?.recon_sass_flag?.variables?.turned === 'on';
};

const checkCustomReportingEnabled = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { enable_custom_reporting: undefined } };
  return abExperiments?.enable_custom_reporting?.variables?.turned === 'on';
};

const checkAllowedMerchantToDeleteReconRun = (splitz) => {
  const { abExperiments } = splitz || {
    abExperiments: { allow_merchant_to_delete_recon_run: undefined },
  };
  return abExperiments?.allow_merchant_to_delete_recon_run?.variables?.turned === 'on';
};

export { checkReconSaasEnabled, checkCustomReportingEnabled, checkAllowedMerchantToDeleteReconRun };
