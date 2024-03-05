import 'regenerator-runtime/runtime';
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { render } from 'react-dom';
import { ThemeProvider, createGlobalStyle } from 'styled-components';

import TncPages from 'merchant/views/TermsAndCondition/Pages';
import { states } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

const GloblatStyle = createGlobalStyle`
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 300;
  src: local('Lato Light'), local('Lato-Light'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh7USSwaPGQ3q5d0N7w.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 300;
  src: local('Lato Light'), local('Lato-Light'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh7USSwiPGQ3q5d0.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
  font-family: 'Lato-Light';
  font-style: normal;
  font-weight: 400;
  src: local('Lato Light'), local('Lato-Light'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh7USSwaPGQ3q5d0N7w.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Lato-Light';
  font-style: normal;
  font-weight: 400;
  src: local('Lato Light'), local('Lato-Light'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh7USSwiPGQ3q5d0.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
  font-family: 'Lato-Regular';
  font-style: normal;
  font-weight: 700;
  src: local('Lato Regular'), local('Lato-Regular'), url(https://fonts.gstatic.com/s/lato/v14/S6uyw4BMUTPHjxAwXiWtFCfQ7A.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Lato-Regular';
  font-style: normal;
  font-weight: 700;
  src: local('Lato Regular'), local('Lato-Regular'), url(https://fonts.gstatic.com/s/lato/v14/S6uyw4BMUTPHjx4wXiWtFCc.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 400;
  src: local('Lato Regular'), local('Lato-Regular'), url(https://fonts.gstatic.com/s/lato/v14/S6uyw4BMUTPHjxAwXiWtFCfQ7A.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 400;
  src: local('Lato Regular'), local('Lato-Regular'), url(https://fonts.gstatic.com/s/lato/v14/S6uyw4BMUTPHjx4wXiWtFCc.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 700;
  src: local('Lato Bold'), local('Lato-Bold'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh6UVSwaPGQ3q5d0N7w.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 700;
  src: local('Lato Bold'), local('Lato-Bold'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh6UVSwiPGQ3q5d0.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 700;
  src: local('Lato Bold'), local('Lato-Bold'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh6UVSwaPGQ3q5d0N7w.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Lato-Bold';
  font-style: normal;
  font-weight: 300;
  src: local('Lato Bold'), local('Lato-Bold'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh6UVSwiPGQ3q5d0.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
  font-family: 'Lato-Bold';
  font-style: normal;
  font-weight: 300;
  src: local('Lato Black'), local('Lato-Black'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh50XSwaPGQ3q5d0N7w.woff2) format('woff2');
  unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 900;
  src: local('Lato Black'), local('Lato-Black'), url(https://fonts.gstatic.com/s/lato/v14/S6u9w4BMUTPHh50XSwiPGQ3q5d0.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
body {
  margin: 0 !important;
}
`;

const App = () => {
  const [data, setData] = React.useState(null);
  const tncId = location.pathname.split('/app').join('').split('/tnc/')[1];
  let address = 'H-23, first block, koramangala Banglore, Karnataka Pin-560047';
  let state = 'Karnataka';

  const { status, refetch } = useQuery({
    queryKey: ['tncPage', tncId],
    queryFn: async () => {
      const fetchData = await axios({
        url: `${window.api_host}merchant/tnc/${tncId}`,
      });
      return fetchData;
    },
    retry: false,
    refetchOnWindowFocus: false,
    staleTime: Infinity,
    onSuccess: (res) => {
      setData(res.data);
      removeSplashLoader();
    },
    onError: () => {
      removeSplashLoader();
    },
  });

  React.useEffect(() => {
    if (!!window.api_host) {
      refetch();
    }
  }, []);

  if (status === 'loading') {
    return null;
  }

  // if user enter wrong dummy url, show 404 not found.
  if (!data && !['000000services', '000000000goods'].includes(tncId)) {
    return (
      <>
        <center>
          <h1>404 Not Found</h1>
        </center>
        <hr />
        <center>nginx</center>
      </>
    );
  }
  if (data) {
    state = states[data.business_registered_state];
    address = `${data.business_registered_address}, ${data.business_registered_city} ${state}, Pin-${data.business_registered_pin}`;
  }
  let isAxisOrg = false;
  if (window.org) {
    isAxisOrg = window.org?.custom_code === 'axis';
  }

  const TnCProps = {
    // set some default value for dummy url content
    businessName: data?.business_name || 'ABC Corp L.T.D',
    updatedAt: Number(data?.updated_at) || 1620388270,
    deliverableType: data?.deliverable_type,
    state,
    address,
    tncLink: data?.link || `https://${isAxisOrg ? 'axis' : 'tnc'}.razorpay.com/tnc/HFO0JH8G98`,
    subcategory: data?.business_subcategory || 'Horizontal Commerce/Marketplace',
    category: data?.business_category || 'Ecommerce',
    businessModel: data?.business_model || 'My business is used for ecommerce online plateform',
    warrantyPeriod: data?.warranty_period || '3 months',
    email: data?.email || 'abc.support@gmail.com',
    refundRequestPeriod: data?.refund_request_period || '3-5 days',
    refundProcessPeriod: data?.refund_process_period?.replace(' days', '') || '5-8',
    unAuthorizePage: true,
    isOrgAxis: window.org ? window.org?.custom_code === 'axis' : false,
  };

  return (
    <ThemeProvider theme={theme}>
      <GloblatStyle />
      <TncPages {...TnCProps} />
    </ThemeProvider>
  );
};

function removeSplashLoader() {
  const $splash = document.getElementById('splash');
  if ($splash) {
    $splash.parentElement.removeChild($splash);
  }
}

render(<App />, document.getElementById('react-root'));
