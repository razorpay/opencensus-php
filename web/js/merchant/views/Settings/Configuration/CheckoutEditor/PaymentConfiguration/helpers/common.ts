function isNonNullObject(obj: any) {
  return obj !== null && typeof obj === 'object';
}

export function hasOwn(obj: any, prop: string) {
  return isNonNullObject(obj) ? obj.hasOwnProperty(prop) : false;
}

export function get(object: Record<string, any>, path: string, defaultValue: any = null): any {
  if (!isNonNullObject(object)) {
    return defaultValue;
  }

  let result = object;
  const pathSplit = path.split('.');
  for (let i = 0; i < pathSplit.length; i++) {
    const pathFragment = pathSplit[i];
    if (hasOwn(result, pathFragment)) {
      result = result[pathFragment];
    } else {
      return defaultValue;
    }
  }
  return result;
}
