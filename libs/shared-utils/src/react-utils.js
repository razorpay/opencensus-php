/* eslint-disable consistent-return */
import React from 'react';

export const isChildSameType = (child, type) => {
  return child && child.type && child.type === type;
};

export const checkChildrenType = (children, types) => {
  if (!children || !Array.isArray(types)) {
    return;
  }

  let error = null;

  React.Children.forEach(children, (child) => {
    if (error) {
      return error;
    }

    let isSameType = false;

    types.forEach((type) => {
      if (isSameType) {
        return;
      }

      isSameType = isChildSameType(child, type);
    });

    if (!isSameType) {
      const classNames = types.map((classObj) => classObj.name);

      error = new Error(`Children should be one of ${classNames.join(' or ')}`);
    }
  });

  return error;
};
