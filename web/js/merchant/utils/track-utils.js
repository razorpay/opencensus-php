import { pickProps } from 'common/utils/rzp-utils';

export function trackDiffInFormFields(oldValues, newValues) {
  const changedFields = Object.keys(newValues);
  const existingValues = pickProps(oldValues, changedFields);
  const fieldEvents = Object.keys(newValues).map(key => {
    const newValue = newValues[key],
      oldValue = existingValues[key] || '';
    const modified = oldValue !== '' && oldValue !== newValue;
    const eventProps = {
      name: key,
      newValue,
      oldValue,
      modified,
    };
    return eventProps;
  });
  return fieldEvents;
}
