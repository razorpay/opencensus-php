#!/bin/sh
user=$1
get_github_user_value()
{
  URI="https://api.github.com"
  API_HEADER="Accept: application/vnd.github.v3+json"
  AUTH_HEADER="Authorization: token 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"
  payment_links=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/payment-links-devs/members")
  payment_cards=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/cards/members")
  nb_plus=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/nbplus/members")
  payments_growth=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/payments_growth/members")
  payments_core=$(curl -sSL -f -H "${AUTH_HEADER}" -H "${API_HEADER}" "${URI}/orgs/razorpay/teams/techpaymentscore/members")
  paymentlinks=$(echo "$payment_links" | jq --raw-output '.[].login')
  paymentcards=$(echo "$payment_cards" | jq --raw-output '.[].login')
  nbplus=$(echo "$nb_plus" | jq --raw-output '.[].login')
  paymentsgrowth=$(echo "$payments_growth" | jq --raw-output '.[].login')
  paymentscore=$(echo "$payments_core" | jq --raw-output '.[].login')
  c="${paymentlinks} ${paymentcards} ${nbplus} ${paymentsgrowth} ${paymentscore}"
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
