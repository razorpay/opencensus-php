import * as React from 'react';

function SuccessTickIcon({ fill = '#24A832' }) {
  return (
    <svg width={18} height={18} viewBox="0 0 18 18" fill="none">
      <circle cx={9} cy={9} r={9} fill={fill} />
      <path
        d="M12.706 6.353L7.61 11.647 5.294 9.24"
        stroke="#fff"
        strokeWidth={1.6}
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

export default SuccessTickIcon;
