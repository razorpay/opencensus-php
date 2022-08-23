import { useState, useEffect } from 'react';
import ResetPasswordComp from '@razorpay/commander-shield/src/bootstrap/ResetPasswordWrapper';
import { fetchOrg, transformFetchOrgData } from '../apis';

const defaultLogoPath = 'img/logo_black.png';

const ResetPassword = () => {
  const [orgLogoUrl, setOrgLogoUrl] = useState('');

  useEffect(() => {
    fetchOrg()
      .then((res) => {
        const response = transformFetchOrgData(res.data, defaultLogoPath);
        setOrgLogoUrl(response.logo);
      })
      .catch(() => {
        setOrgLogoUrl(defaultLogoPath);
      });
  }, []);

  return <ResetPasswordComp logo={orgLogoUrl} />;
};

export default ResetPassword;
