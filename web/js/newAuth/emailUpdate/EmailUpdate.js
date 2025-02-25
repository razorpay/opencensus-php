import { useState, useEffect } from 'react';
import EmailUpdateComp from 'newAuth/@commander-shield/bootstrap/EmailUpdateWrapper';
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

  return <EmailUpdateComp logo={orgLogoUrl} />;
};

export default EmailUpdate;
