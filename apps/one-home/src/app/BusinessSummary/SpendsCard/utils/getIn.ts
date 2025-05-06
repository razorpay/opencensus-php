const getIn = (obj: any, path: string | string[], defaultValue?: any): any => {
  if (!obj || typeof obj !== 'object') return defaultValue;

  // Normalize path into an array (if given as a string, split by '.' while handling array notation)
  const keys = Array.isArray(path) ? path : path.replace(/\[(\d+)\]/g, '.$1').split('.');

  return (
    keys.reduce((acc, key) => (acc && acc[key] !== undefined ? acc[key] : undefined), obj) ??
    defaultValue
  );
};

export default getIn;
