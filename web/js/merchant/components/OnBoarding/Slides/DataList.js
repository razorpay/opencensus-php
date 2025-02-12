import { classList } from 'common/utils/rzp-utils';

export default ({ className, children, horizontalDivider }) => (
  <div
    className={classList(
      'DataList',
      className && `DataList--${className}`,
      horizontalDivider && 'horizontal-divider'
    )}
  >
    {children.map((child, idx) => (
      <div className="DataList-Item" key={idx}>
        {child}
      </div>
    ))}
  </div>
);
