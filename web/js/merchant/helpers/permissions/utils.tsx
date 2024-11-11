import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import React from 'react';
import { useStore } from 'shell/commonStore';

export type PermissionsList = Array<string>;

interface ValidatePermissions {
  permissions: PermissionsList;
  operator?: 'AND' | 'OR';
}

/**
 * Check if RBAC is enabled based on Splitz
 * User object is passed to have exclusion of users
 * */

export const isRBACExperimentEnabled = (user, rbacExperiment) => {
  const result = rbacExperiment?.variables?.result === 'variant';
  return result;
};

/**
 * Base function used to validate if a user has the required permissions
 * Perfer to use the HOC or hook instead of using this utility directly
 * */
const initIsActionAllowed =
  (user: User, rbacExperiment) =>
  ({ permissions, operator = 'AND' }: ValidatePermissions): boolean => {
    const isRBACEnabled = isRBACExperimentEnabled(user, rbacExperiment);
    if (!isRBACEnabled) return true;

    const userPermissions = user.permissions || [];

    if (!permissions) throw new Error('Permissions are required');

    if (!Array.isArray(permissions)) {
      throw new Error('Permissions must be an array');
    }

    if (permissions.length === 0) {
      throw new Error('At least one permission is required');
    }

    if (operator !== 'AND' && operator !== 'OR') {
      throw new Error('Invalid permissions operator');
    }

    if (operator === 'AND') {
      return permissions.every((permission) => userPermissions.includes(permission));
    }

    if (operator === 'OR') {
      return permissions.some((permission) => userPermissions.includes(permission));
    }

    return false;
  };

const ValidatePermissionsHOC = (WrappedComponent) => (props) => {
  const user = useStore((state) => state.session.user);

  const { abExperiments: { rbacEnabled } = {} } = useSplitzService();

  const isActionAllowed = initIsActionAllowed(user, rbacEnabled);
  const isRBACEnabled = isRBACExperimentEnabled(user, rbacEnabled);

  return (
    <WrappedComponent {...props} isActionAllowed={isActionAllowed} isRBACEnabled={isRBACEnabled} />
  );
};

const withValidatePermissions = (WrappedComponent) => ValidatePermissionsHOC(WrappedComponent);

// Evaluate permissions within functional component, and any reusable hook
const useValidatePermissions = () => {
  const user = useStore((state) => state.session.user);

  const { abExperiments: { rbacEnabled } = {} } = useSplitzService();

  const isActionAllowed = initIsActionAllowed(user, rbacEnabled);
  const isRBACEnabled = isRBACExperimentEnabled(user, rbacEnabled);

  return { isActionAllowed, isRBACEnabled };
};

// Evaluate permissions with components, as a wrapper component
const ValidatePermissions = withValidatePermissions(
  ({ children, permissions, operator, isActionAllowed }) => {
    const isAllowed = isActionAllowed({ permissions, operator });
    return isAllowed ? children : null;
  },
);

export {
  withValidatePermissions,
  useValidatePermissions,
  ValidatePermissions,
  initIsActionAllowed,
};
