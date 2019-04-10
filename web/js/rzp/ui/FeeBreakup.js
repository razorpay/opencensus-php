export default function(props) {
  const children = props.children;
  return (
    <div class="FeeBreakup">
      {children.map((child, index) => (
        <React.Fragment key={index}>
          {index > 1 ? <div class="FeeBreakup--add-symbol">+</div> : null}
          <div class="FeeBreakup--item">{child}</div>
        </React.Fragment>
      ))}
    </div>
  );
}
