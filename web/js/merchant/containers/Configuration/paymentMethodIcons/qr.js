export default (foregroundColor, backgroundColor) => (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <g strokeWidth="2">
      <path fill="#fff" stroke={foregroundColor} d="M2 7v16h16V7H2z" />
      <g fill={foregroundColor} transform="translate(4.6 9.4)">
        <rect width="3.6" height="3.6" rx="1" />
        <path d="M8 0h1.6c.6 0 1 .4 1 1v1.7c0 .5-.4.9-1 .9H8a1 1 0 0 1-1-1V1c0-.6.5-1 1-1zM1 7.2h1.7c.5 0 .9.4.9 1v1.7c0 .5-.4 1-1 1H1a1 1 0 0 1-1-1V8.2c0-.6.4-1 1-1zM8 7.2h1.6c.6 0 1 .4 1 1v1.7c0 .5-.4 1-1 1H8a1 1 0 0 1-1-1V8.2c0-.6.5-1 1-1z" />
        <path d="M4.6 4.6V1a.8.8 0 1 1 1.6 0v4.5c0 .4-.4.8-.8.8H1a.8.8 0 1 1 0-1.6h3.7zM9.3 4.6a.8.8 0 0 1 0 1.6h-2a.8.8 0 1 1 0-1.6h2zM4.6 8.1a.8.8 0 1 1 1.6 0v1.2a.8.8 0 0 1-1.6 0V8z" />
      </g>
      <g transform="translate(8 1)">
        <path
          fill="#fff"
          stroke={backgroundColor}
          d="M5.3 1h7.5c.8 0 1.4.7 1.4 1.5V15c0 1-.6 1.6-1.4 1.6H5.2c-.8 0-1.5-.7-1.5-1.6V2.6c0-1 .7-1.6 1.5-1.6z"
        />
        <circle fill={backgroundColor} cx="9" cy="12" r="1.5" />
      </g>
    </g>
  </svg>
);
