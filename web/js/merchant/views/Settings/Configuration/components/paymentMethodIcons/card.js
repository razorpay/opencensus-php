export default (foregroundColor, backgroundColor) => (
  <svg
    viewBox="0 0 27 22"
    xmlns="http://www.w3.org/2000/svg"
    xmlnsXlink="http://www.w3.org/1999/xlink"
  >
    <path
      d="M2 7v13h18v-5H7V7H2zm0-2h5v10h15v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"
      fill={backgroundColor}
    />
    <path
      d="M10.004 13.003a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2h-2zM7 0h18a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm0 2v13h18V2H7zm-.282 5.005a1 1 0 1 1 0-2h19a1 1 0 0 1 0 2h-19z"
      fill={foregroundColor}
    />
  </svg>
);
