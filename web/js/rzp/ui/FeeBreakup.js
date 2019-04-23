import { classList } from 'common/util';

export default function({ type = 'primary', ...props }) {
  const children = props.children;
  return (
    <div class={classList('FeeBreakup', type)}>
      {children.map((child, index) => (
        <React.Fragment key={index}>
          {index > 1 ? <div class="FeeBreakup--add-symbol">+</div> : null}
          <div class="FeeBreakup--item">{child}</div>
        </React.Fragment>
      ))}
    </div>
  );
}
