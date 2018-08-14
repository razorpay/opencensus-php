export default ({ title, value, children, className }) => (
  <div class={`stats-card ${className || ''}`}>
    <div class="title">{title}</div>
    <div class="value">{value ? <h1>{value}</h1> : children}</div>
  </div>
);
