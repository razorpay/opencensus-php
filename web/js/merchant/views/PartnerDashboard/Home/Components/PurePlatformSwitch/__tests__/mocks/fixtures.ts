import { trackingExperimentsTestProp } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';

export const stepTestProps = {
  setIsOpen: jest.fn(),
  setStep: jest.fn(),
  trackingExperiments: trackingExperimentsTestProp,
  user: {},
  closeModal: () => {},
};
