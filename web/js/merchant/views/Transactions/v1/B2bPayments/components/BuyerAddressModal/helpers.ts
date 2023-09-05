export const handleFieldOnChange = (
  fieldConfig: Record<string, string | boolean | undefined>,
  callback: (arg: { target: { value?: string; values?: string[] } }) => void,
): ((arg: { name?: string; value?: string; values?: string[] }) => void) => {
  return ({ value, values }) => {
    callback({
      target: {
        ...fieldConfig,
        value: Array.isArray(values) ? values[0] : value,
      },
    });
  };
};
