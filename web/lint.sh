#!/bin/bash

eslint js/merchant/views/Settlements \
  js/merchant/views/Transactions \
  js/merchant/views/Settings \
  js/merchant/views/TermsAndCondition \
  js/merchant/views/onboarding \
  js/common/hooks \
  js/common/context \
  js/common/components \
  js/common/services --ext .js,.jsx,.ts,.tsx --quiet

if [[ $? != 0 ]] ; then
    echo "eslint check failed"
    exit 1
fi

echo "eslint check successful"

stylelint js/merchant/views/TermsAndCondition/**/*.{js,jsx,ts,tsx} \
  js/merchant/views/onboarding/**/*.{js,jsx,ts,tsx} \
  js/merchant/views/PartnerDashboard/Home/**/*.{js,jsx,ts,tsx} \
  js/common/hooks/**/*.{js,jsx,ts,tsx} \
  js/common/context/**/*.{js,jsx,ts,tsx} \
  js/common/components/**/*.{js,jsx,ts,tsx} \
  js/common/services/**/*.{js,jsx,ts,tsx} \
  js/merchant/views/Settlements/**/*.{js,jsx}

if [[ $? != 0 ]] ; then
    echo "stylelint check failed"
    exit 1
fi

echo "stylelint check successful"
