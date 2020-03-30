import { classList } from 'common/utils/rzp-utils';

export default ({ className, children, horizontalDivider }) => (
  <div
    class={classList(
      'DataList',
      className && `DataList--${className}`,
      horizontalDivider && 'horizontal-divider'
    )}
  >
    {children.map((child, idx) => (
      <div class="DataList-Item" key={idx}>
        {child}
      </div>
    ))}
  </div>
);
