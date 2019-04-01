export default (foregroundColor, backgroundColor) => (
  <svg viewBox="0 0 25 23" xmlns="http://www.w3.org/2000/svg">
    <path
      d="M2 7v14h19v-5h2.011v-6H21V7H2zm0-2h19a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm15 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"
      fill={foregroundColor}
    />
    <path
      d="M7.326 5L16 2.11V5h2V2.11c0-.643-.308-1.352-.83-1.728a2 2 0 0 0-1.802-.276L2 5h5.326zM15 12v4h8v-4h-8zm0-2h8a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2z"
      fill={backgroundColor}
    />
  </svg>
);
