import React, { useEffect } from 'react';
import { Box } from '@razorpay/blade/components';
import { useNavigate, useParams, useLocation } from 'react-router-dom';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import { Loader } from 'merchant/views/Reconciliations/commonComponents';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useReconTracking } from 'merchant/views/Reconciliations/hooks';

import About from './About';
import NewReconciliation from './NewReconciliation';
import ReconConfig from './ReconConfig';
import ReconInitiatedSuccess from './ReconInitiatedSuccess';
import SelectProduct from './SelectProduct';

const ReconOnboarding = () => {
  const location = useLocation();
  const [step, setStep] = React.useState(location.state ? 3 : 1);
  const [userSelections, setUserSelections] = React.useState({});
  const [merchantData, setMerchantData] = React.useState({});

  const products = merchantData?.products;
  const navigate = useNavigate();
  const { step: urlStep, type } = useParams();
  const isConfigCreation = type === 'create-config';

  const selectProduct = (product) => {
    setUserSelections({ product });
    navigate('/reconciliations/create-config/3');
    analyticsTrackWithUserInfo({
      screen: ReconScreens.NewConfiguration,
      objectName: 'recon confirm product',
      actionName: 'click',
      properties: {
        productId: product,
      },
    });
  };

  const clickGetStarted = () => {
    if (products && Object.keys(products).length === 1) {
      setUserSelections({ product: Object.keys(products)[0] });
      navigate('/reconciliations/create-config/2');
      setStep(2);
    } else if (products) {
      navigate('/reconciliations/create-config/2');
      setStep(1);
    }
    analyticsTrackWithUserInfo({
      screen: ReconScreens.NewConfiguration,
      objectName: 'recon get started',
      actionName: 'click',
    });
  };

  const fetchMerchantMeta = async () => {
    const res = await merchantFetch({
      url: `recon-saas/merchant_config`,
      mode: 'live',
      method: 'get',
    });
    if (res?.status_code === 200) {
      setMerchantData(res.data);
    }
  };

  const selectReconType = (reconType) => {
    setUserSelections({ ...userSelections, reconType });
    navigate('/reconciliations/create-config/4');
    analyticsTrackWithUserInfo({
      screen: ReconScreens.NewConfiguration,
      objectName: 'recon confirm config type',
      actionName: 'click',
      properties: {
        configType: reconType,
      },
    });
  };

  useReconTracking({
    objectName: 'new configuration',
    screen: ReconScreens.NewConfiguration,
  });

  useEffect(() => {
    fetchMerchantMeta();
  }, []);

  useEffect(() => {
    if (!userSelections.product && isConfigCreation && urlStep !== '1') {
      navigate(`/reconciliations/create-config/2`);
    }
    setStep(Number(urlStep));
  }, [urlStep]);

  return (
    <Box paddingTop="spacing.1">
      <Box />
      {products ? (
        <>
          {step === 1 && <About handleCtaClick={clickGetStarted} />}
          {step === 2 && (
            <SelectProduct selectProduct={selectProduct} merchantMeta={merchantData} />
          )}
          {step === 3 &&
            (userSelections.product ? (
              <ReconConfig
                product={merchantData?.products?.[userSelections?.product]}
                handleCtaClick={selectReconType}
                isConfigCreation={isConfigCreation}
              />
            ) : null)}
          {step === 4 && (
            <NewReconciliation
              fileConfigs={
                products[userSelections.product].reconTypes[userSelections.reconType].file_config
              }
              reconType={products[userSelections.product].reconTypes[userSelections.reconType]}
              handleCtaClick={() => navigate('/reconciliations/create-config/5')}
            />
          )}
          {step === 5 && (
            <ReconInitiatedSuccess
              handleCtaClick={() => navigate('/reconciliations/create-config/5')}
              isConfigCreation={true}
            />
          )}
        </>
      ) : (
        <Loader />
      )}
    </Box>
  );
};

export default ReconOnboarding;
