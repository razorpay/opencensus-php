import { NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

export default ({
  myRole,
  notMyRole,
  featureEnabled,
  icon,
  label,
  ...linkProps
}) => {
  return (
    <ShowWhen
      notMyRole={notMyRole}
      myRole={myRole}
      featureEnabled={featureEnabled}
    >
      <NavLink {...linkProps}>
        <i class={`icon ${icon}`} />
        {label}
      </NavLink>
    </ShowWhen>
  );
};
