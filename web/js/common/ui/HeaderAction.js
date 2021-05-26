import { connect } from 'react-redux';
import TetherComponent from 'react-tether';

const HeaderAction = ({ children, org }) => {
  return (
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
