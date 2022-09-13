import NavItem from 'merchant/views/MagicCheckout/OrderStatusUpload/components/NavItem';

const SideNav = (props) => {
  const { extraClass, tabs, onTabClick, activeNav } = props;

  return (
    <div className={`nav-sidebar col-sm-2 no-padding ${extraClass ?? ''}`}>
      {tabs.map((tab) => {
        const active = tab.id === activeNav;
        return (
          <NavItem
            title={tab.title}
            key={tab.id}
            onTabClick={() => onTabClick(tab.id)}
            active={active}
          />
        );
      })}
    </div>
  );
};

export default SideNav;
