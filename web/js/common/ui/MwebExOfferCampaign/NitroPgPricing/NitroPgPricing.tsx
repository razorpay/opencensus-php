import React, { useState } from 'react';
import { AsyncBtn } from 'common/new-ui/Button';
import { sendDataToSalesForce } from 'common/utils/common-api';
import { getUser } from 'merchant/store';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';
import { compose } from 'redux';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';
import isEmpty from '@universe/utils/isEmpty';
import './NitroPgPricing.styl';

const NitroImage = {
  true: {
    image:
      'https://cdn.razorpay.com/static/assets/growth-assets/mweb-exclusive-offer/nitro-pg-pricing.svg',
    alt: 'Nitro PG Pricing',
  },
  false: {
    image:
      'https://cdn.razorpay.com/static/assets/growth-assets/mweb-exclusive-offer/current-account-mweb.svg',
    alt: 'Current Account with RazorpayX',
  },
};

//TODO: remove "nitroCampaignId" function from this file when exclusive offer for m-web support is enable for nitro-card segment which is already have support in Desktop
export const nitroCampaignId = () => {
  const map = {
    // Beta Nitro
    GwPth7nhHNdMND: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA',
    },
    H7361l13HhrBgO: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
      target_product_feature: 'XCA',
    },
    H75RfvQFecKHsT: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
      target_product_feature: 'XCA',
    },
    H75Qu5SInWp3SQ: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
      target_product_feature: 'XCA',
    },
    H75Q0JHjnUd5xs: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
      target_product_feature: 'XCA',
    },
    HF0Ml2IU6gH9rt: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
      target_product_feature: 'XCA',
    },
    HF0NZThSDtgNGB: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
      target_product_feature: 'XCA',
    },
    HF0OIJAqllZPRu: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
      target_product_feature: 'XCA',
    },
    HF0Ox4LNEgYHbV: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
      target_product_feature: 'XCA',
    },
    HZ76WCrNYDOyy9: {
      version: 'project-nitro-appswitcher',
      version_description: 'Nitro for appswitcher merchants',
      target_product_feature: 'XCA',
    },

    // Prod Nitro
    GxtSf8y77iWw9e: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA',
    },
    H6qGPCBPduY7Gl: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
      target_product_feature: 'XCA',
    },
    H6qJ2X77dqHG9I: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
      target_product_feature: 'XCA',
    },
    H6qIJWTzrqt54X: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
      target_product_feature: 'XCA',
    },
    HExafLb492K7LU: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
      target_product_feature: 'XCA',
    },
    H6qHJJnYOtwfoc: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
      target_product_feature: 'XCA',
    },
    HExehMbAqYqlWF: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
      target_product_feature: 'XCA',
    },
    HExiHP6GBUEVcu: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
      target_product_feature: 'XCA',
    },
    HExnzHcFfimA6u: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
      target_product_feature: 'XCA',
    },
    HPc6GXsuboNXiS: {
      version: 'project-nitro-delhi-v1',
      version_description: 'Nitro for delhi',
      target_product_feature: 'XCA',
    },
    HPc7OB0N3kh5BR: {
      version: 'project-nitro-mumbai-v1',
      version_description: 'Nitro for mumbai',
      target_product_feature: 'XCA',
    },
    HPc8DrLeWZc76W: {
      version: 'project-nitro-pune-v1',
      version_description: 'Nitro for pune',
      target_product_feature: 'XCA',
    },
    HPc9cMyPKKeAAX: {
      version: 'project-nitro-gurgaon-v1',
      version_description: 'Nitro for gurgaon',
      target_product_feature: 'XCA',
    },
    HPcAKrn53GP41d: {
      version: 'project-nitro-nagpur-v1',
      version_description: 'Nitro for nagpur',
      target_product_feature: 'XCA',
    },
    HPcBJQw2E0BzpZ: {
      version: 'project-nitro-kolhapur-v1',
      version_description: 'Nitro for kolhapur',
      target_product_feature: 'XCA',
    },
    Hrt8rX7v1tehY1: {
      version: 'project-nitro-coimbatore-v1',
      version_description: 'Nitro for coimbatore',
      target_product_feature: 'XCA',
    },
    HzaFJoqZsRDu0c: {
      version: 'nitro-othercities-v1',
      version_description: 'Nitro for others cities v1',
      target_product_feature: 'XCA',
    },
    HYlnMMJoE1RjFf: {
      version: 'project-nitro-appswitcher',
      version_description: 'Nitro for appswitcher merchants',
      target_product_feature: 'XCA',
    },

    // Beta nitro corporate cards
    HVcXIX8S1cokqB: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA+CCC',
    },

    // Prod nitro corporate cards
    HVdaH5ipHEzj6x: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA+CCC',
    },
    HW1KsF0APg55vP: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
      target_product_feature: 'XCA+CCC',
    },
    // Test account for prod
    HWP22TCyDAfcRG: {
      version: 'test_nitro_kolkata_v1',
      version_description: 'Testing Nitro for kolkata',
      target_product_feature: 'XCA+CCC',
    },
    HW1HhztGJNiaYA: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
      target_product_feature: 'XCA+CCC',
    },
    HW1IZP67ejfclG: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
      target_product_feature: 'XCA+CCC',
    },
    HW14FoCLRKdABS: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
      target_product_feature: 'XCA+CCC',
    },
    HW1382Z5BUYrDV: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
      target_product_feature: 'XCA+CCC',
    },
    HW15QtkHIilowU: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
      target_product_feature: 'XCA+CCC',
    },
    HW16dcasfI78sW: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
      target_product_feature: 'XCA+CCC',
    },
    HW17UktB0YY7X7: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
      target_product_feature: 'XCA+CCC',
    },
    HW18PSOqi56mMN: {
      version: 'project-nitro-delhi-v1',
      version_description: 'Nitro for delhi',
      target_product_feature: 'XCA+CCC',
    },
    HW19AUgSRz2frR: {
      version: 'project-nitro-mumbai-v1',
      version_description: 'Nitro for mumbai',
      target_product_feature: 'XCA+CCC',
    },
    HW19wimb0Bhu8N: {
      version: 'project-nitro-pune-v1',
      version_description: 'Nitro for pune',
      target_product_feature: 'XCA+CCC',
    },
    HW1Al5SNojQepQ: {
      version: 'project-nitro-gurgaon-v1',
      version_description: 'Nitro for gurgaon',
      target_product_feature: 'XCA+CCC',
    },
    HW1Bb4TphEGzuU: {
      version: 'project-nitro-nagpur-v1',
      version_description: 'Nitro for nagpur',
      target_product_feature: 'XCA+CCC',
    },
    HW1CwITu2o0hdO: {
      version: 'project-nitro-kolhapur-v1',
      version_description: 'Nitro for kolhapur',
      target_product_feature: 'XCA+CCC',
    },
    HrtIYOAiX2ipgI: {
      version: 'project-nitro-coimabtore-v1',
      version_description: 'Nitro for coimabtore',
      target_product_feature: 'XCA+CCC',
    },
    HzaHdQAoyYFlFJ: {
      version: 'nitro-othercities-v1',
      version_description: 'Nitro for others cities v1',
      target_product_feature: 'XCA+CCC',
    },
  };

  const getExpStatus = (name, experimentNameInAbExperimentsMap) => {
    const splitzExperiment = window?.rzp_user?.splitz_experiments[name];
    if (
      abExperimentsMap[experimentNameInAbExperimentsMap].includes(name) &&
      !isEmpty(splitzExperiment)
    ) {
      return splitzExperiment?.variables?.result === 'on';
    }
    return false;
  };

  const featureId =
    Object.keys(map).find((feature) => getExpStatus(feature, 'project_nitro')) || '';

  return {
    ...map[featureId],
    campaign: 'nitro',
    target_metric: 'MTU',
  };
};
const NitroPgPricing = ({ showNotification }): JSX.Element => {
  const [isToolTip, setIsToolTip] = useState(false);
  const [isNitro, setIsNitro] = useState(true);
  const user = getUser();

  const triggerApiCall = (): any => {
    if (!isNitro) {
      window.open('https://razorpay.com/docs/razorpayx/current-account/');
      return;
    }

    // eslint-disable-next-line consistent-return
    return sendDataToSalesForce(
      {
        Campaign_ID: nitroCampaignId()?.version,
        product_name: 'Current_Account',
      },
      user,
    )
      .then(() => {
        setIsToolTip(false);
        setIsNitro(false);
      })
      .catch((_) => {
        showNotification({
          type: 'error',
          message: 'An error occurred in connecting to the server',
          hidePrevious: true,
        });
      });
  };
  return (
    <div className="nitroPgPricing" id="nitroPgPricing">
      {isToolTip && (
        <div className="tooltip-ui">
          <img
            src="https://cdn.razorpay.com/static/assets/growth-assets/mweb-exclusive-offer/nitro-tooltip.svg"
            alt="Nitro Campaign tooltip"
          />
        </div>
      )}
      <div className="header-icons">
        <img
          src="https://cdn.razorpay.com/static/assets/neostone-exclusive-offer/xBanking.png"
          alt="Nitro Campaign XBanking Logo"
        />
        {isNitro && (
          <img
            src="https://cdn.razorpay.com/static/assets/neostone-exclusive-offer/toolTipAlert.svg"
            alt="Nitro Campaign tooltip"
            onClick={() => setIsToolTip(!isToolTip)}
            className="nitro-tooltip"
          />
        )}
      </div>
      <img
        src={NitroImage[`${isNitro}`]?.image}
        alt={NitroImage[`${isNitro}`]?.alt}
        className={`nitro-image ${!isNitro ? 'addMarginNitro' : ''}`}
      />
      <div className="cta-wrapper">
        <AsyncBtn.Primary className="btn" onClick={triggerApiCall}>
          {isNitro ? 'I am Interested ✨' : 'View Documents Required'}
        </AsyncBtn.Primary>
      </div>
    </div>
  );
};

export default compose(
  connect(null, {
    showNotification: showNotificationProp,
  }),
)(NitroPgPricing);
