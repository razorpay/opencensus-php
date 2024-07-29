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
