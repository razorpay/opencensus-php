import * as React from 'react';

function ArrowDownIcon({ fill = '#199467' }) {
  return (
    <svg width={32} height={32} viewBox="0 0 32 32" fill="none">
      <path
        d="M16 5.333v21.333M24 18.667l-8 8-8-8"
        stroke={fill}
        strokeWidth={1.791}
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

export default ArrowDownIcon;
