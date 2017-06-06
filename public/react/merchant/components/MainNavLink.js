import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

export default ({
  myRole,
  notMyRole,
  featureEnabled,
  icon,
  label,
  beta = false,
  ...linkProps
}) => {
  return (
    <ShowWhen
      notMyRole={notMyRole}
      myRole={myRole}
      featureEnabled={featureEnabled}
    >
      <NavLink {...linkProps}>
        <i class={icon} />
        {label}
        {beta
          ? <span class="badge bg-success pull-right hidden-xs">beta</span>
          : null}
      </NavLink>
    </ShowWhen>
  );
};
