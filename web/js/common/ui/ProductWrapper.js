import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';

const ProductWrapper = ({ children, extra, isMobile, tabsData, customHeaderRightClass }) => {
  const headerRightClass = classList(
    'header-right',
    isMobile ? 'mobile' : '',
    customHeaderRightClass,
  );

  return (
    <tabbed-container class="updated">
      <header id="link-header">
        <div className="header-left">
          {tabsData.map(
            (tab) =>
              !tab.hidden && (
                <NavLink
                  end={!tab.isMatchStartsWith}
                  to={tab.url}
                  key={tab.title}
                  onClick={() => {
                    if (tab.onTabClick) tab.onTabClick();
                  }}
                >
                  {tab.title}
                </NavLink>
              ),
          )}
        </div>
        <div className={headerRightClass}>{extra}</div>
      </header>
      {children}
    </tabbed-container>
  );
};

const mapStateToProps = (state) => {
  return {
    isMobile: state.app.isMobileResolution,
  };
};

export default connect(mapStateToProps, null)(ProductWrapper);
