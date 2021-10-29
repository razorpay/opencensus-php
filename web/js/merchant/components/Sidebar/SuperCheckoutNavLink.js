import { useEffect } from 'react';
import { connect } from 'react-redux';
import { sendToLumberjack } from 'common/utils/analytics';

const objectName = 'SuperCheckoutNavTab';
const screen = 'Dashboard';

const SuperCheckoutNavLink = ({ children, user }) => {
  useEffect(() => {
    sendToLumberjack({
      eventName: `${objectName}Displayed`,
      properties: { screen },
    });
  }, []);

  const onClick = () => {
    sendToLumberjack({
      eventName: `${objectName}Clicked`,
      properties: { screen },
    });
  };

  if (!user.isSuperCheckoutEnabled) return null;

  return children(onClick);
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(SuperCheckoutNavLink);
