/**
 * Determines whether a given key `K` is optional in the type `T`.
 *
 * This utility checks if an empty object `{}` can extend `Pick<T, K>`:
 * - If `true`, then `K` was optional in `T`.
 * - If `false`, then `K` was required in `T`.
 *
 * @template T - The object type to check
 * @template K - A key of `T` to test for optionality
 */
export type IsOptional<T, K extends keyof T> = {} extends Pick<T, K> ? true : false;

/**
 * Strictly merges two object types `T` and `U` according to the following rules:
 *
 * 1. **Shared Keys (`keyof T & keyof U`)**:
 *    - If the key is optional in either type, the resulting key is optional and is a union of both property types.
 *    - If the key is required in both, the resulting key is required and is an intersection of both property types.
 *
 * 2. **Unique Keys in `T`**:
 *    - These keys become optional in the merged type, preserving the property type from `T`.
 *
 * 3. **Unique Keys in `U`**:
 *    - These keys become optional in the merged type, preserving the property type from `U`.
 *
 * @template T - The first object type
 * @template U - The second object type
 */
export type StrictMerge<T, U> = {
  [K in keyof T & keyof U]: IsOptional<T, K> extends true
    ? T[K] | U[K]
    : IsOptional<U, K> extends true
    ? T[K] | U[K]
    : T[K] & U[K];
} & {
  [K in Exclude<keyof T, keyof U>]?: T[K];
} & {
  [K in Exclude<keyof U, keyof T>]?: U[K];
};
