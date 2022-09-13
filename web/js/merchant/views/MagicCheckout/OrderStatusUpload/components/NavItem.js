const NavItem = (props) => {
  const { title, onTabClick, active } = props;

  return (
    <div className={`nav-item${active ? ' active' : ''}`} onClick={onTabClick}>
      {title}
    </div>
  );
};

export default NavItem;
