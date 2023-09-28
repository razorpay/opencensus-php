import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';

const ProductWrapper = ({ children, extra, isMobile, tabsData }) => {
  return (
    <tabbed-container class="updated">
      <header id="link-header">
        <div className="header-left">
          {tabsData.map(
            (tab) =>
              !tab.hidden && (
                <NavLink end to={tab.url} key={tab.title}>
                  {tab.title}
                </NavLink>
              ),
          )}
        </div>
        <div className={`header-right ${isMobile ? 'mobile' : ''}`}>{extra}</div>
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
