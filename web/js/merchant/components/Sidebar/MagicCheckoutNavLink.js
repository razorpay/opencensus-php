import { useEffect } from 'react';
import { connect } from 'react-redux';
import { sendToLumberjack } from 'common/utils/analytics';

const objectName = 'super_checkout_nav_tab';
const screen = 'Dashboard';

const MagicCheckoutNavLink = ({ children, user }) => {
  useEffect(() => {
    if (user.isMagicCheckoutEnabled) {
      sendToLumberjack({
        eventName: `${objectName}_displayed`,
        properties: {
          screen,
          merchant_id: user.current,
        },
      });
    }
  }, []);

  const onClick = () => {
    sendToLumberjack({
      eventName: `${objectName}_clicked`,
      properties: {
        screen,
        merchant_id: user.current,
      },
    });
  };

  if (!user.isMagicCheckoutEnabled) return null;

  return children(onClick);
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(MagicCheckoutNavLink);
