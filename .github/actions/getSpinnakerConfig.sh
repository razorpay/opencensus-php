#!/bin/sh
get_spinnaker_config_value() {
  defaultInstanceValue="bvt-1"
  count=0
  statusCode=$(curl -c /tmp/cookies -o -s -w "%{http_code}" --location --request GET 'https://deploy-api.razorpay.com/login' \
    --header "Authorization: Bearer ${GIT_TOKEN}")
  cookies="$(cat /tmp/cookies | awk '/SESSION/ { print $NF }')"
  SPINNAKER_HEADER="Cookie: SESSION=$cookies"
  if [ "$statusCode" = 200 ]; then
     apiInstanceFromSpinnaker=$(curl --location --request GET "https://deploy-api.razorpay.com/executions?pipelineConfigIds=842e0854-3a08-4e67-9881-a9ea1d005b31&limit=1" \
      -H "${SPINNAKER_HEADER}" | jq --raw-output '.[].stages[0].outputs.instance')
  fi
  # Condition to check the current instance values tag
  if [ $apiInstanceFromSpinnaker = $defaultInstanceValue ]; then
    defaultInstanceValue="bvt-2"
  fi
}
get_spinnaker_config_value
echo $defaultInstanceValue