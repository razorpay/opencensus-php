import { useState, useEffect } from 'react';
import ResetPasswordComp from '@razorpay/commander-shield/src/bootstrap/ResetPasswordWrapper';
import CommanderShieldThemeWrapper from 'newAuth/commanderShieldThemeWrapper';
import { fetchOrg, transformFetchOrgData } from 'newAuth/apis';

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

  return (
    <CommanderShieldThemeWrapper>
      <ResetPasswordComp logo={orgLogoUrl} />
    </CommanderShieldThemeWrapper>
  );
};

export default ResetPassword;
