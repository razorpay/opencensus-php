/**
 * Joins the provided class names into a single string.
 * Accepts multiple arguments, which can be strings or arrays of strings.
 *
 * @param {...(string | string[])} args - Class names or arrays of class names.
 * @returns {string} - A single string of class names joined by spaces.
 */
export function classList(...args: (string | string[])[]): string {
  const classes: string[] = [];

  for (let i = 0; i < args.length; i++) {
    if (args[i]) {
      if (Array.isArray(args[i])) {
        classes.push((args[i] as string[]).join(' ')); 
      } else {
        classes.push(args[i] as string);
      }
    }
  }

  return classes.join(' ');
}
