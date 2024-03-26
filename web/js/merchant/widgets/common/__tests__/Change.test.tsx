import React from 'react';

import { Change } from 'merchant/widgets/common/Change';
import { render, screen } from 'test-utils';
import { ChangeProps } from 'merchant/widgets/common/Change/types';
import { COLORS } from 'merchant/containers/Home/RTUX/colors';

const text = '14% increase';

// type RenderApp = ({props}?: ) => any;
const renderApp = ({ props }: { props?: Partial<ChangeProps> }) => {
  return render(
    <Change text={text} variant="increase" type="number" isInverted={true} {...props} />,
  );
};

describe('Widget->common->Change', () => {
  test('should render increase flow correctly', () => {
    renderApp({ props: { variant: 'increase' } });
    expect(screen.getByText(text)).toBeVisible();
    const changeWrapperElement = screen.getByTestId('change-component');

    expect(changeWrapperElement.querySelector('svg')).toHaveStyle('transform: rotateX(180deg)');
  });

  test('should render decrease flow correctly', () => {
    renderApp({ props: { variant: 'decrease' } });
    expect(screen.getByText(text)).toBeVisible();
    const changeWrapperElement = screen.getByTestId('change-component');

    expect(changeWrapperElement.querySelector('svg')).not.toHaveStyle('transform: rotateX(180deg)');
  });

  test('should render increase flow correctly when inverted', () => {
    renderApp({ props: { variant: 'increase', isInverted: true } });
    expect(screen.getByText(text)).toBeVisible();
    const changeWrapperElement = screen.getByTestId('change-component');

    // arrow direction is unchanged, color changes
    expect(changeWrapperElement.querySelector('svg')).toHaveStyle('transform: rotateX(180deg)');
    expect(changeWrapperElement.querySelector('svg path')).toHaveAttribute('fill', COLORS.red);
  });
  test('should render decrease flow correctly when inverted', () => {
    renderApp({ props: { variant: 'decrease', isInverted: true } });
    expect(screen.getByText(text)).toBeVisible();
    const changeWrapperElement = screen.getByTestId('change-component');

    // arrow direction is unchanged, color changes
    expect(changeWrapperElement.querySelector('svg')).not.toHaveStyle('transform: rotateX(180deg)');
    expect(changeWrapperElement.querySelector('svg path')).toHaveAttribute('fill', COLORS.green);
  });
});
