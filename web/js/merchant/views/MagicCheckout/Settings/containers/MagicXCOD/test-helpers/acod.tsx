import * as React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render } from '@testing-library/react';

import { ACODProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context';
import { ConfirmationModalProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

export const mockedACODProviderProps = {
  merchantId: 'm001',
  notify: jest.fn(),
  storeActions: {
    addRule: jest.fn(),
    updateRule: jest.fn(),
    removeRule: jest.fn(),
  },
} as any;

type LazyRenderACODComponent = (
  component: React.FC<any>,
  options?: {
    initialProps?: Record<string, any>;
    acodProviderProps?: Partial<typeof mockedACODProviderProps>;
  },
) => (props?: object) => ReturnType<typeof render>;
export const lazyRenderACODComponent: LazyRenderACODComponent =
  (
    component: React.FC<any>,
    { initialProps = {}, acodProviderProps = mockedACODProviderProps } = {},
  ) =>
  (props: object = {}) => {
    const Component = component;
    const _props = { ...initialProps, ...props };

    return render(
      <BladeProvider themeTokens={bladeTheme}>
        <ConfirmationModalProvider>
          <ACODProvider {...acodProviderProps}>
            <Component {..._props} />
          </ACODProvider>
        </ConfirmationModalProvider>
      </BladeProvider>,
    );
  };
