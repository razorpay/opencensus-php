export default function(props) {
  const children = props.children;
  return (
    <div class="FeeBreakup">
      {children.map(child => <div class="FeeBreakup--item">{child}</div>)}
    </div>
  );
}
