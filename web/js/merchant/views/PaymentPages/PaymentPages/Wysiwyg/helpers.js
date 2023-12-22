import { isFormItemOfTypeLateFee } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';

import { FIXED_FIELDS } from './FormSection/UDF/helpers/preAddedFields';

export function formatFormItems(formItems) {
  // Pick out late fee field.
  const lateFeeField = formItems.find((fi) => isFormItemOfTypeLateFee(fi));

  // Pick out late fee due date field.
  const lateFeeDueDateField = formItems.find(
    (fi) => fi.name === FIXED_FIELDS?.lateFeeDueDate?.name,
  );

  return formItems.reduce((acc, fi) => {
    const isLateFeeField = isFormItemOfTypeLateFee(fi);
    const isLateFeeDueDateField = fi.name === FIXED_FIELDS?.lateFeeDueDate?.name;

    // Check is late fee field is present and if late due date field is not present as to manually add due date field.
    if (isLateFeeField && !lateFeeDueDateField) {
      // Make late fee due date field mandatory according to the late fee field.
      acc.push(fi, { ...FIXED_FIELDS?.lateFeeDueDate, required: fi?.mandatory });
    } else if (isLateFeeDueDateField) {
      // Make late fee due date field mandatory according to the late fee field.
      acc.push({ ...fi, required: lateFeeField?.mandatory });
    } else {
      acc.push(fi);
    }

    return acc;
  }, []);
}
