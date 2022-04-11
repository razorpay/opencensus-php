#!/bin/bash

eslint web/js/merchant/views/Settlements \
  web/js/merchant/views/Transactions \
  web/js/merchant/views/Settings \
  web/js/merchant/views/TermsAndCondition \
  web/js/merchant/views/referral \
  web/js/merchant/views/onboarding \
  web/js/common/hooks \
  web/js/common/context \
  web/js/common/components \
  web/js/common/services --ext .js,.jsx,.ts,.tsx

if [[ $? != 0 ]] ; then
    echo "eslint check failed"
    exit 1
fi

echo "eslint check successful"

stylelint web/js/merchant/views/TermsAndCondition/**/*.{js,jsx,ts,tsx} \
  web/js/merchant/views/referral/**/*.{js,jsx,ts,tsx} \
  web/js/merchant/views/onboarding/**/*.{js,jsx,ts,tsx} \
  web/js/merchant/views/PartnerDashboard/Home/**/*.{js,jsx,ts,tsx} \
  web/js/common/hooks/**/*.{js,jsx,ts,tsx} \
  web/js/common/context/**/*.{js,jsx,ts,tsx} \
  web/js/common/components/**/*.{js,jsx,ts,tsx} \
  web/js/common/services/**/*.{js,jsx,ts,tsx} \
  web/js/merchant/views/Settlements/**/*.{js,jsx}

if [[ $? != 0 ]] ; then
    echo "stylelint check failed"
    exit 1
fi

echo "stylelint check successful"
