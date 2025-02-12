import { classList } from 'common/utils/rzp-utils';

export default function({ type = 'primary', ...props }) {
  const children = props.children;
  return (
    <div className={classList('FeeBreakup', type)}>
      {children.map((child, index) => (
        <React.Fragment key={index}>
          {index > 1 ? <div className="FeeBreakup--add-symbol">+</div> : null}
          <div className="FeeBreakup--item">{child}</div>
        </React.Fragment>
      ))}
    </div>
  );
}
