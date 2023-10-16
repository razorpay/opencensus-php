import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';

import { matchFullPageView } from 'merchant/routes';

export default function HandleIndex() {
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
    } else {
      navigate('/dashboard');
    }
  }, []);

  return null;
}
