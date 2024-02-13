import React, { ComponentType } from 'react';
import { useI18nContext } from '@razorpay/i18nify-react';
import { WithI18nifyStateProps } from './types';

export const withI18nifyState =
  <T extends WithI18nifyStateProps>(Component: ComponentType<T>) =>
  (props: T): JSX.Element => {
    const { setI18nState } = useI18nContext();

    return <Component {...props} setI18nState={setI18nState} />;
  };
