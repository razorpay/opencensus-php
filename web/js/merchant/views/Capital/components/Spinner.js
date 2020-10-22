import * as React from 'react';

function Spinner(props) {
  return (
    <svg width={40} height={40} viewBox="0 0 50 50" {...props}>
      <path
        fill="#528ff0"
        d="M28.1 43.68c10.166-1.767 16.975-11.44 15.207-21.607C41.54 11.907 31.866 5.099 21.7 6.866l.697 4.008c7.952-1.382 15.52 3.943 16.902 11.896 1.383 7.952-3.943 15.52-11.895 16.902l.696 4.008z"
      >
        <animateTransform
          attributeType="xml"
          attributeName="transform"
          type="rotate"
          from="0 25 25"
          to="360 25 25"
          dur="0.6s"
          repeatCount="indefinite"
        />
      </path>
    </svg>
  );
}

export default Spinner;
