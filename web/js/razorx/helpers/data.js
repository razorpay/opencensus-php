import { titleCase } from 'common/utils/rzp-utils';

const statusPillClasses = {
  terminated: 'label-danger',
  activated: 'label-success',
};

export const statusPill = (status, emptyValue = '--') => {
  return status ? (
    <span className={`pill ${statusPillClasses[status] || 'label-semi-muted'}`}>
      {titleCase(status)}
    </span>
  ) : (
    emptyValue
  );
};

// TODO: All below mappings exists in 'entity-resources.js' as well. "Check if they've exactly same data". Merge Accordingly.
export const methods = {
  card: 'Card',
  emi: 'EMI',
  netbanking: 'Netbanking',
  wallet: 'Wallet',
  upi: 'UPI',
};

export const cardSteps = {
  authorisation: 'Authorisation',
  authentication: 'Authentication',
};

export const authTypes = {
  _3DS: '3ds',
  HEADLESS_OTP: 'headless_otp',
  IVR: 'ivr',
  OTP: 'otp',
};

export const authGateway = {
  mpi_blade: 'Blade',
  mpi_enstage: 'Endstage',
};
