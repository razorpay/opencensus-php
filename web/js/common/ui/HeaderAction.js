import { connect } from 'react-redux';
import TetherComponent from 'react-tether';
import { isMobileDevice } from 'merchant/components/Home/data';

const HeaderAction = ({ children, org, responsive }) => {
  /* 
    we want to check if the components have props responsive true 
    and its mobileview then we want to render header actions in div instread 
    of TetherComponent
  */
  const mweb = responsive && isMobileDevice();
  return mweb ? (
    <div className={`tabbed-header-actions${org.custom_code ? ` ${org.custom_code}` : ''}`}>
      {children}
    </div>
  ) : (
    <TetherComponent
      target="tabbed-container > header"
      attachment="top right"
      targetAttachment="top right"
      offset="-10px 12px"
      class={org.custom_code}
    >
      <div />
      {children}
    </TetherComponent>
  );
};

const mapStateToProps = (state) => {
  return {
    org: state.session.org,
  };
};

export default connect(mapStateToProps, null)(HeaderAction);
