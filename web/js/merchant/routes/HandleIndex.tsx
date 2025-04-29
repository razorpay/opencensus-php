import { useEffect } from 'react';
import { connect } from 'react-redux';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';

import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { matchFullPageView } from 'merchant/routes';
import { ONE_NAV_MOBILE_PATH } from 'merchant/components/NavigationLayout/constants';
import { isMobileResolution } from 'common/utils/rzp-utils';
import { isBillMeOnlyMerchant } from 'merchant/utils/omniUtils';

type HandleIndexProps = {
  user: User;
  isConnectedNavigation: boolean;
};
// TODO Fix this, Partner route is showing up as default view

const SALES_ASSISTED_ONBOARDING = 'sales_assisted_onboarding';

const HandleIndex = ({ user, isConnectedNavigation }: HandleIndexProps) => {
  const location = useLocation();
  const navigate = useNavigate();

  const [searchParams] = useSearchParams();
  const i18 = useI18Service();
  // creating an abstraction of just consuming splitz experiment, rest service should not be accessed via RouteGuard
  const splitz = useSplitzService();
  const { abExperiments } = splitz;
  const { isPosSalesAgent, isPosEkycAgent } = checkIfPosSalesAgent({ user, abExperiments });

  const isMobile = isMobileResolution();

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
        if (isPosEkycAgent && !user?.user?.name) {
          navigationInfo = { pathname: '/pos-sales/basic-info', search: '' };
        }
        navigate(navigationInfo);
        return;
      }
      navigate('/partners/submerchants/pos');
    } else if (Boolean(window?.IS_ONE_HOME_ENABLED)) {
      navigate('/home');
    } else if (user.isPartner()) {
      if (isConnectedNavigation && isMobile) {
        navigate(ONE_NAV_MOBILE_PATH);
        return;
      }
      navigate('/partners');
    } else if (isBillMeOnlyMerchant(splitz)) {
      navigate('/billme');
    } else {
      if (isConnectedNavigation && isMobile) {
        navigate(ONE_NAV_MOBILE_PATH);
        return;
      }
      navigate('/dashboard');
    }
  }, []);

  return null;
};

export default connect((state) => ({ user: state.session.user }), null)(HandleIndex);
