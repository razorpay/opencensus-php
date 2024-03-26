export const checkReconSaasEnabled = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { recon_saas_flag: undefined } };
  return abExperiments?.recon_sass_flag?.variables?.turned === 'on';
};
