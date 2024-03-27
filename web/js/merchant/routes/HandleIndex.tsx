import { useEffect } from 'react';
import { connect } from 'react-redux';
import { useLocation, useNavigate } from 'react-router-dom';

import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { matchFullPageView } from 'merchant/routes';

type HandleIndexProps = {
  user: User;
};
const HandleIndex = ({ user }: HandleIndexProps) => {
  const location = useLocation();
  const navigate = useNavigate();
  const i18 = useI18Service();
  // creating an abstraction of just consuming splitz experiment, rest service should not be accessed via RouteGuard
  const { abExperiments } = useSplitzService();

  useEffect(() => {
    const matchView = matchFullPageView(location.pathname, {
      i18,
      splitz: { abExperiments },
    });
    if (matchView && matchView.match) {
      return;
    }
    if (location.hash) {
      const path = location.hash.replace(/#\/?app\/?/, '') || 'dashboard';

      const newRoute = location.pathname + path;
      navigate(newRoute);
    } else if (user.isPartnerAgentRole) {
      navigate('/partners/submerchants/pos');
    } else {
      navigate('/dashboard');
    }
  }, []);

  return null;
};

export default connect((state) => ({ user: state.session.user }), null)(HandleIndex);
