#!/bin/sh
user=$1
get_github_user_value()
{
  URI="https://api.github.com"
  API_HEADER="Accept: application/vnd.github.v3+json"
  AUTH_HEADER="Authorization: token ${GITHUB_TOKEN}"
  payments_bu_first=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/payments-bu-dev/members?page=1&per_page=100")
  payments_bu_second=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/payments-bu-dev/members?page=2&per_page=100")
  payments_bu_third=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/payments-bu-dev/members?page=3&per_page=100")
  paymentsbufirst=$(echo "$payments_bu_first" | jq --raw-output '.[].login')
  paymentsbusecond=$(echo "$payments_bu_second" | jq --raw-output '.[].login')
  paymentsbuthird=$(echo "$payments_bu_third" | jq --raw-output '.[].login')
  c="${paymentsbufirst} ${paymentsbusecond} ${paymentsbuthird}"
  for item in $c
  do
      if [ "$user" = "$item" ]; then
          echo "success"
          exit 0
      fi
  done
  echo "fail"
  exit 0
}
get_github_user_value
