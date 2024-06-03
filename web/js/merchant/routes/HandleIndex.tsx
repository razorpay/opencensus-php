import { useEffect } from 'react';
import { connect } from 'react-redux';
import { useLocation, useNavigate } from 'react-router-dom';

import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { matchFullPageView } from 'merchant/routes';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';

type HandleIndexProps = {
  user: User;
};
const HandleIndex = ({ user }: HandleIndexProps) => {
  const location = useLocation();
  const navigate = useNavigate();
  const i18 = useI18Service();
  // creating an abstraction of just consuming splitz experiment, rest service should not be accessed via RouteGuard
  const splitz = useSplitzService();
  const { abExperiments } = splitz;
  const { isPosSalesAgent } = checkIfPosSalesAgent({ user, abExperiments });

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
      if (isPosSalesAgent) {
        navigate('/pos-sales');
        return;
      }
      navigate('/partners/submerchants/pos');
    } else if (user.isPartner()) {
      navigate('/partners');
    } else {
      navigate('/dashboard');
    }
  }, []);

  return null;
};

export default connect((state) => ({ user: state.session.user }), null)(HandleIndex);
