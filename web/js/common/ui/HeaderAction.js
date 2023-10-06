import { connect } from 'react-redux';
import TetherComponent from 'react-tether';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

const FallbackComponent = ({ eventId }) => {
  return (
    <div className="inline-fallback">
      <div>There was an issue, please try later!</div>
      <div>Error code : {eventId}</div>
    </div>
  );
};

const HeaderAction = ({
  children,
  org,
  responsive,
  isMobile,
  target = 'tabbed-container > header, .tabbed-container > header',
}) => {
  /*
    we want to check if the components have props responsive true
    and its mobileview then we want to render header actions in div instread
    of TetherComponent
  */
  const mweb = responsive && isMobile;
  return mweb ? (
    <div className={`tabbed-header-actions${org.custom_code ? ` ${org.custom_code}` : ''}`}>
      <ErrorBoundary FallbackComponent={FallbackComponent}>{children}</ErrorBoundary>
    </div>
  ) : (
    // ErrorBoundary on the potential breaking TetherComponent ensures atleast its children renders on the screen
    // so that the user is not blocked from his actions and viewing other parts of the section
    <ErrorBoundary FallbackComponent={() => children}>
      <TetherComponent
        target={target}
        attachment="top right"
        targetAttachment="top right"
        offset="-10px 12px"
        class={org.custom_code}
      >
        <div />
        <ErrorBoundary FallbackComponent={FallbackComponent}>{children}</ErrorBoundary>
      </TetherComponent>
    </ErrorBoundary>
  );
};

const mapStateToProps = (state) => {
  return {
    org: state.session.org,
    isMobile: state.app.isMobileResolution,
  };
};

export default connect(mapStateToProps, null)(HeaderAction);
