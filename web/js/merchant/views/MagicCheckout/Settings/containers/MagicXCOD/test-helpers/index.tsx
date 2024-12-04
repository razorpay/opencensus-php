import * as React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render } from '@testing-library/react';

export const renderComponent = (component: React.FC<any> = () => null, props: object = {}) => {
  const Component = component;

  return render(
    <BladeProvider themeTokens={bladeTheme}>
      <Component {...props} />
    </BladeProvider>,
  );
};

export const lazyRenderComponent =
  (component: React.FC<any>, initialProps: object = {}) =>
  (props: object = {}) => {
    const _props = { ...initialProps, ...props };

    return renderComponent(component, _props);
  };
