import React, { ReactNode, ReactElement } from 'react';

/**
 * Checks if the given child is of the same type as the provided type.
 * 
 * @param {ReactNode} child - The React child element to check.
 * @param {React.ComponentType<any>} type - The expected React component type.
 * @returns {boolean} - Returns true if the child is of the same type, false otherwise.
 */
export const isChildSameType = (child: ReactNode, type: React.ComponentType<any>): boolean => {
  return !!child && (child as ReactElement).type === type;
};


/**
 * Checks if all children are of the specified types.
 * 
 * @param {ReactNode} children - The React children elements to validate.
 * @param {Array<React.ComponentType<any>>} types - Array of valid React component types.
 * @returns {Error | undefined} - Returns an error if any child is of an invalid type, or undefined if all children are valid.
 */
export const checkChildrenType = (
  children: ReactNode,
  types: Array<React.ComponentType<any>>,
): Error | undefined => {
  if (!children || !Array.isArray(types)) {
    return;
  }

  let error: Error | undefined;

  React.Children.forEach(children, (child) => {
    if (error) {
      return;
    }

    let isSameType = false;

    types.forEach((type) => {
      if (!isSameType) {
        isSameType = isChildSameType(child, type);
      }
    });

    if (!isSameType) {
      const classNames = types.map((type) => type.name);
      error = new Error(`Children should be one of ${classNames.join(' or ')}`);
    }
  });

  return error;
};
