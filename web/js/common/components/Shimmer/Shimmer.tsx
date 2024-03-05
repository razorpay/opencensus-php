import { classList } from 'common/utils/rzp-utils';
import React from 'react';
import { ShimmerBar } from './styled';

export interface ShimmerPropsInterface {
  height?: string;
  width?: string;
  styles?: Record<string, string>;
  classes?: string | string[];
  variant?: 'rounded' | 'circular';
  borderRadius?: string;
}

const Shimmer = (props: ShimmerPropsInterface): JSX.Element => {
  const { height, width, styles = {}, classes, ...rest } = props;
  return (
    <ShimmerBar
      className={classList('shimmer-flex', classes)}
      style={{
        height,
        width,
        ...styles,
      }}
      {...rest}
    />
  );
};

export default Shimmer;

Shimmer.defaultProps = {
  height: '20px',
  variant: 'rounded',
  borderRadius: '4px',
};
