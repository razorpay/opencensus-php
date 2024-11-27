export const toString: (json: any) => string = (json = {}) => {
  return JSON.stringify(json);
};

export const toJSON = (jsonString: string) => {
  try {
    JSON.parse(jsonString);
  } catch (err) {
    return null;
  }
};
