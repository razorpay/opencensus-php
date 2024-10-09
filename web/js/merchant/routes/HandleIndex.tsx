import { useEffect } from 'react';
import { connect } from 'react-redux';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';

import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { matchFullPageView } from 'merchant/routes';

type HandleIndexProps = {
  user: User;
};

const SALES_ASSISTED_ONBOARDING = 'sales_assisted_onboarding';

const HandleIndex = ({ user }: HandleIndexProps) => {
  const location = useLocation();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const i18 = useI18Service();
  // creating an abstraction of just consuming splitz experiment, rest service should not be accessed via RouteGuard
  const splitz = useSplitzService();
  const { abExperiments } = splitz;
  const { isPosSalesAgent, isPosEkycAgent } = checkIfPosSalesAgent({ user, abExperiments });

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
      if (isPosSalesAgent || isPosEkycAgent) {
        const isKYCOngoing = searchParams.get('source') === SALES_ASSISTED_ONBOARDING;
        const rzpSalesMid = searchParams.get('rzp_sales_mid');
        let navigationInfo = { pathname: '/pos-sales', search: '' };
        if (isKYCOngoing && rzpSalesMid) {
          navigate(navigationInfo, { replace: true });
          navigationInfo = {
            pathname: `/pos-sales/onboarding/${rzpSalesMid}`,
            search: `?source=${SALES_ASSISTED_ONBOARDING}`,
          };
        }
        navigate(navigationInfo);
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
