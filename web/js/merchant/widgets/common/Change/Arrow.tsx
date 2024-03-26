import React from 'react';
import { ArrowProps } from './types';

const direction = {
  increase: {
    transform: 'rotateX(180deg)',
  },
  // by default its facing downwards
  decrease: {},
};

export const Arrow = ({ variant, fill }: ArrowProps) => {
  return (
    <svg
      width="16"
      height="16"
      viewBox="0 0 16 16"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      style={direction[variant]}
    >
      <g id=" arrow tag">
        <path id="Vector" d="M8 12.8008L16 4.26745H0L8 12.8008Z" fill={fill} />
      </g>
    </svg>
  );
};
