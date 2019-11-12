import { titleCase } from 'common/utils/rzp-utils';

const statusPillClasses = {
  terminated: 'label-danger',
  activated: 'label-success',
};

export const statusPill = (status, emptyValue = '--') => {
  return status ? (
    <span class={`pill ${statusPillClasses[status] || 'label-semi-muted'}`}>
      {titleCase(status)}
    </span>
  ) : (
    emptyValue
  );
};
