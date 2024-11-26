const checkReconSaasEnabled = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { recon_saas_flag: undefined } };
  return abExperiments?.recon_sass_flag?.variables?.turned === 'on';
};

const checkCustomReportingEnabled = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { enable_custom_reporting: undefined } };
  return abExperiments?.enable_custom_reporting?.variables?.turned === 'on';
};

export { checkReconSaasEnabled, checkCustomReportingEnabled };
