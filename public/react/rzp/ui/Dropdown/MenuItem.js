export default props => {
  let { children, ...otherProps } = props;
  return (
    <li {...otherProps}>
      <a>
        {props.children}
      </a>
    </li>
  );
};
