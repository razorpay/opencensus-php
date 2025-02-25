import React from 'react';

const WhatsAppIcon = () => {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none">
      <g filter="url(#A)">
        <path
          d="M15.75 9A6.75 6.75 0 0 1 9 15.75a6.72 6.72 0 0 1-3.829-1.19l-2.307.577.605-2.267A6.72 6.72 0 0 1 2.25 9a6.75 6.75 0 1 1 13.5 0z"
          fill="url(#B)"
        />
        <path
          fillRule="evenodd"
          d="M9 16.875c4.35 0 7.875-3.526 7.875-7.875S13.35 1.125 9 1.125 1.125 4.65 1.125 9a7.84 7.84 0 0 0 1.023 3.884l-1.023 3.99 4.115-.954a7.84 7.84 0 0 0 3.76.954zm0-1.21c3.68 0 6.664-2.983 6.664-6.664S12.68 2.337 9 2.337 2.337 5.32 2.337 9c0 1.42.445 2.738 1.203 3.82l-.597 2.238 2.278-.57c1.074.74 2.376 1.175 3.78 1.175z"
          fill="#fff"
        />
        <path
          d="M7.03 5.344C6.844 4.968 6.557 5 6.267 5c-.52 0-1.327.62-1.327 1.777 0 .947.417 1.985 1.824 3.536 1.358 1.497 3.142 2.272 4.622 2.245s1.786-1.3 1.786-1.73c0-.19-.118-.286-.2-.312l-1.647-.778c-.212-.085-.322.03-.39.092-.192.183-.573.722-.703.844s-.325.06-.406.014c-.298-.12-1.105-.478-1.748-1.102-.795-.77-.842-1.036-.992-1.273-.12-.19-.032-.305.012-.355l.514-.655c.106-.152.022-.382-.03-.526l-.55-1.434z"
          fill="#fff"
        />
      </g>
      <defs>
        <filter
          id="A"
          x=".125"
          y=".125"
          width="17.75"
          height="17.75"
          filterUnits="userSpaceOnUse"
          colorInterpolationFilters="sRGB"
        >
          <feFlood floodOpacity="0" result="A" />
          <feColorMatrix in="SourceAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" />
          <feOffset />
          <feGaussianBlur stdDeviation=".5" />
          <feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0" />
          <feBlend in2="A" />
          <feBlend in="SourceGraphic" />
        </filter>
        <linearGradient
          id="B"
          x1="14.906"
          y1="3.938"
          x2="2.25"
          y2="15.75"
          gradientUnits="userSpaceOnUse"
        >
          <stop stopColor="#5bd066" />
          <stop offset="1" stopColor="#27b43e" />
        </linearGradient>
      </defs>
    </svg>
  );
};

export default WhatsAppIcon;
