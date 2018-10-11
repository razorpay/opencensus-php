export default ({ children, ...rest }) => (
  <div class="wysiwyg-edit-layer" {...rest}>
    {children}
  </div>
);
