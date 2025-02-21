export function nonNullable<T>(value: T | null): value is T {
  return value !== null;
}
