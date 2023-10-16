import React, { ComponentType } from 'react';
import { useI18Service } from './useI18Service';
import { I18ContextStateType } from './types';

export const withI18Service =
  <
    T extends {
      i18: I18ContextStateType;
    },
  >(
    Component: ComponentType<T>,
  ) =>
  (props: T): JSX.Element => {
    const i18 = useI18Service();
    return <Component {...props} i18={i18} />;
  };
