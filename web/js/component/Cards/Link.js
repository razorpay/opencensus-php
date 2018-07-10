import { classList } from 'common/util';
import { Link } from 'react-router-dom';

export default ({ to, icon, title, description, className }) => (
  <Link class={classList('LinkCard', className)} to={to}>
    {icon && <i class={classList('i', icon, 'icon-card')} />}
    <i class="i i-chevron-right" />
    <div>
      <span>{title}</span>
      <p>{description}</p>
    </div>
  </Link>
);
