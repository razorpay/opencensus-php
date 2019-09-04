import { snakeToTitleCase } from 'common/util';

const statusPillClasses = {
  terminated: 'label-danger',
  activated: 'label-success',
};

export const statusPill = (status, emptyValue = '--') => {
  return status ? (
    <span class={`pill ${statusPillClasses[status] || 'label-semi-muted'}`}>
      {snakeToTitleCase(status)}
    </span>
  ) : (
    emptyValue
  );
};
