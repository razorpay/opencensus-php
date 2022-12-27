import { useState, useEffect } from 'react';
import EmailUpdateComp from '@razorpay/commander-shield/src/bootstrap/EmailUpdateWrapper';
import CommanderShieldThemeWrapper from 'newAuth/commanderShieldThemeWrapper';
import { fetchOrg, transformFetchOrgData } from 'newAuth/apis';

const defaultLogoPath = 'img/logo_black.png';

const EmailUpdate = () => {
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
      <EmailUpdateComp logo={orgLogoUrl} />
    </CommanderShieldThemeWrapper>
  );
};

export default EmailUpdate;
