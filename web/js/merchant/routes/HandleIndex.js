import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

import { matchFullPageView } from 'merchant/routes';

export default function HandleIndex() {
  const location = useLocation();
  const navigate = useNavigate();

  useEffect(() => {
    const matchView = matchFullPageView(location.pathname);
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
